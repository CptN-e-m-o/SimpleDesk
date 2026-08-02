<?php

namespace App\Console\Commands\Admin\Mail;

use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\MailChannelTester;
use Illuminate\Console\Command;
use Throwable;

class TestMailChannelCommand extends Command
{
    protected $signature = 'simpledesk:mail:test-channel
        {channel : Mailbox channel ID}';

    protected $description =
        'Test a configured incoming or outgoing mail channel';

    public function handle(
        MailChannelTester $tester
    ): int {
        $channel = MailboxChannel::query()
            ->with([
                'mailbox',
                'providerConnection',
            ])
            ->find(
                $this->argument('channel')
            );

        if ($channel === null) {
            $this->error(
                'Mailbox channel was not found.'
            );

            return self::FAILURE;
        }

        try {
            $result = $tester->test(
                $channel
            );

            if (! $result->successful) {
                $this->error(
                    $result->message
                );

                return self::FAILURE;
            }

            $this->info(
                $result->message
            );

            if (
                $result->latencyMilliseconds
                !== null
            ) {
                $this->line(
                    'Latency: '
                    .$result->latencyMilliseconds
                    .' ms'
                );
            }

            if ($result->details !== []) {
                $this->line(
                    json_encode(
                        $result->details,
                        JSON_PRETTY_PRINT
                        | JSON_UNESCAPED_SLASHES,
                    )
                );
            }

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        }
    }
}
