<?php

namespace App\Console\Commands\Admin\Mail;

use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\MailChannelHealthRecorder;
use App\Services\Admin\Mail\MailChannelTester;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Throwable;

class CheckMailChannelsHealthCommand extends Command
{
    protected $signature = 'simpledesk:mail:check-health
        {--mailbox=* : Check only channels of specified mailbox IDs}
        {--channel=* : Check only specified channel IDs}
        {--direction= : Filter by incoming or outgoing direction}
        {--limit= : Maximum number of channels}';

    protected $description = 'Check enabled mail channels and update their health status';

    public function handle(
        MailChannelTester $tester,
        MailChannelHealthRecorder $health,
    ): int {
        $mailboxIds = $this->positiveIds(
            (array) $this->option('mailbox')
        );

        $channelIds = $this->positiveIds(
            (array) $this->option('channel')
        );

        $direction = trim(
            (string) $this->option('direction')
        );

        if (
            $direction !== ''
            && ! in_array(
                $direction,
                array_map(
                    static fn (MailboxChannelDirection $case): string => $case->value,
                    MailboxChannelDirection::cases()
                ),
                true
            )
        ) {
            $this->error(
                'The --direction option must be incoming or outgoing.'
            );

            return self::FAILURE;
        }

        $limitOption = $this->option('limit');

        $limit = is_numeric($limitOption)
            ? (int) $limitOption
            : (int) config(
                'simpledesk-mail-automation.health.batch_size',
                100
            );

        $limit = max(
            1,
            min(1000, $limit)
        );

        $channels = MailboxChannel::query()
            ->with([
                'mailbox',
                'providerConnection',
            ])
            ->where('is_enabled', true)
            ->whereHas(
                'mailbox',
                fn (Builder $query): Builder => $query->where(
                    'is_active',
                    true
                )
            )
            ->when(
                $mailboxIds !== [],
                fn (Builder $query): Builder => $query->whereIn(
                    'mailbox_id',
                    $mailboxIds
                )
            )
            ->when(
                $channelIds !== [],
                fn (Builder $query): Builder => $query->whereIn(
                    'id',
                    $channelIds
                )
            )
            ->when(
                $direction !== '',
                fn (Builder $query): Builder => $query->where(
                    'direction',
                    $direction
                )
            )
            ->orderBy('mailbox_id')
            ->orderBy('direction')
            ->orderBy('failover_order')
            ->orderBy('id')
            ->limit($limit)
            ->get();

        if ($channels->isEmpty()) {
            $this->info(
                'No enabled mail channels matched the health-check filters.'
            );

            return self::SUCCESS;
        }

        $rows = [];
        $successful = 0;
        $failed = 0;

        foreach ($channels as $channel) {
            try {
                $result = $tester->test(
                    $channel
                );

                $channel->refresh();

                if ($result->successful) {
                    $successful++;
                } else {
                    $failed++;
                }

                $rows[] = [
                    (string) $channel->id,
                    $channel->mailbox !== null
                        ? "{$channel->mailbox_id} ({$channel->mailbox->name})"
                        : (string) $channel->mailbox_id,
                    $channel->direction->value,
                    $channel->driver->value,
                    $result->successful ? 'success' : 'failed',
                    $result->latencyMilliseconds !== null
                        ? "{$result->latencyMilliseconds} ms"
                        : '-',
                    $channel->health_status->value,
                    mb_substr(
                        $result->message,
                        0,
                        180
                    ),
                ];
            } catch (MailDriverException $exception) {
                $channel->refresh();
                $failed++;

                $rows[] = [
                    (string) $channel->id,
                    $channel->mailbox !== null
                        ? "{$channel->mailbox_id} ({$channel->mailbox->name})"
                        : (string) $channel->mailbox_id,
                    $channel->direction->value,
                    $channel->driver->value,
                    'failed',
                    '-',
                    $channel->health_status->value,
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        180
                    ),
                ];
            } catch (Throwable $exception) {
                $health->markFailure(
                    channel: $channel,
                    errorCode: 'scheduled_health_check_failed',
                    errorMessage: mb_substr(
                        $exception->getMessage(),
                        0,
                        10000
                    ),
                );

                $channel->refresh();
                $failed++;

                report($exception);

                $rows[] = [
                    (string) $channel->id,
                    $channel->mailbox !== null
                        ? "{$channel->mailbox_id} ({$channel->mailbox->name})"
                        : (string) $channel->mailbox_id,
                    $channel->direction->value,
                    $channel->driver->value,
                    'error',
                    '-',
                    $channel->health_status->value,
                    mb_substr(
                        $exception->getMessage(),
                        0,
                        180
                    ),
                ];
            }
        }

        $this->table(
            [
                'Channel',
                'Mailbox',
                'Direction',
                'Driver',
                'Result',
                'Latency',
                'Health',
                'Message',
            ],
            $rows
        );

        $this->newLine();
        $this->line("Checked: {$channels->count()}");
        $this->line("Successful: {$successful}");
        $this->line("Failed: {$failed}");

        return $failed === 0
            ? self::SUCCESS
            : self::FAILURE;
    }

    private function positiveIds(
        array $values
    ): array {
        return array_values(
            array_unique(
                array_filter(
                    array_map(
                        static fn (mixed $value): int => (int) $value,
                        $values
                    ),
                    static fn (int $value): bool => $value > 0
                )
            )
        );
    }
}
