<?php

namespace App\Console\Commands\Admin\Mail;

use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailMessageAttemptStatus;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\MailChannelTester;
use App\Services\Admin\Mail\OutgoingEmailQueueService;
use App\Services\Admin\Mail\OutgoingMailFailoverService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VerifyOutgoingFailoverCommand extends Command
{
    protected $signature = 'simpledesk:mail:verify-outgoing-failover
        {mailbox : Mailbox ID}
        {primaryChannel : Primary SMTP channel ID that will be forced to fail}
        {fallbackChannel : Fallback SMTP channel ID expected to send the message}
        {recipient : Recipient email address}
        {--timeout=2 : Temporary timeout for the intentionally broken SMTP channel}';

    protected $description = 'Verify outgoing SMTP failover, failed-channel cooldown, and channel recovery';

    public function handle(
        OutgoingEmailQueueService $queue,
        OutgoingMailFailoverService $sender,
        MailChannelTester $tester,
    ): int {
        $mailbox = null;
        $primary = null;
        $channelSnapshots = collect();
        $primaryConfiguration = null;

        try {
            $mailbox = $this->mailbox();

            $primary = $this->channel(
                argument: 'primaryChannel',
                mailbox: $mailbox,
                role: 'Primary',
            );

            $fallback = $this->channel(
                argument: 'fallbackChannel',
                mailbox: $mailbox,
                role: 'Fallback',
            );

            if ($primary->id === $fallback->id) {
                throw new RuntimeException(
                    'Primary and fallback channel IDs must be different.'
                );
            }

            $recipient = trim(
                (string) $this->argument('recipient')
            );

            if (
                filter_var(
                    $recipient,
                    FILTER_VALIDATE_EMAIL
                ) === false
            ) {
                throw new RuntimeException(
                    'Recipient must be a valid email address.'
                );
            }

            $timeout = max(
                1,
                min(
                    30,
                    (int) $this->option('timeout')
                )
            );

            $channels = $mailbox
                ->channels()
                ->where(
                    'direction',
                    MailboxChannelDirection::Outgoing->value
                )
                ->get();

            $channelSnapshots = $channels->mapWithKeys(
                fn (MailboxChannel $channel): array => [
                    $channel->id => [
                        'is_enabled' => $channel->is_enabled,
                        'is_primary' => $channel->is_primary,
                        'failover_order' => $channel->failover_order,
                    ],
                ]
            );

            $primaryConfiguration =
                $primary->configuration ?? [];

            $this->prepareTopology(
                channels: $channels,
                primary: $primary,
                fallback: $fallback,
            );

            $this->components->info(
                'Phase 1: primary SMTP failure and fallback delivery.'
            );

            $brokenConfiguration = array_replace(
                $primaryConfiguration,
                [
                    'host' => '127.0.0.1',
                    'port' => 1,
                    'encryption' => 'none',
                    'timeout' => $timeout,
                    'verify_peer' => false,
                ]
            );

            $primary->forceFill([
                'configuration' => $brokenConfiguration,
            ])->save();

            $phaseOne = $this->sendMessage(
                queue: $queue,
                sender: $sender,
                mailbox: $mailbox,
                recipient: $recipient,
                phase: 'failover',
            );

            $this->assertFailoverMessage(
                message: $phaseOne,
                primaryChannelId: $primary->id,
                fallbackChannelId: $fallback->id,
            );

            $this->printMessageResult(
                title: 'Phase 1 result',
                message: $phaseOne,
            );

            $primary->forceFill([
                'configuration' => $primaryConfiguration,
            ])->save();

            $primary->refresh();
            $fallback->refresh();

            $this->components->info(
                'Phase 2: failed primary is skipped during cooldown.'
            );

            $phaseTwo = $this->sendMessage(
                queue: $queue,
                sender: $sender,
                mailbox: $mailbox,
                recipient: $recipient,
                phase: 'cooldown',
            );

            $this->assertSingleChannelMessage(
                message: $phaseTwo,
                expectedChannelId: $fallback->id,
                context: 'cooldown',
            );

            $this->printMessageResult(
                title: 'Phase 2 result',
                message: $phaseTwo,
            );

            $this->components->info(
                'Phase 3: recover the primary channel and use it again.'
            );

            $connectionTest = $tester->test(
                $primary->fresh([
                    'providerConnection',
                ])
            );

            if (! $connectionTest->successful) {
                throw new RuntimeException(
                    'Primary channel recovery connection test failed: '
                    .$connectionTest->message
                );
            }

            $phaseThree = $this->sendMessage(
                queue: $queue,
                sender: $sender,
                mailbox: $mailbox,
                recipient: $recipient,
                phase: 'recovery',
            );

            $this->assertSingleChannelMessage(
                message: $phaseThree,
                expectedChannelId: $primary->id,
                context: 'recovery',
            );

            $this->printMessageResult(
                title: 'Phase 3 result',
                message: $phaseThree,
            );

            $primary->refresh();
            $fallback->refresh();

            $this->newLine();

            $this->components->info(
                'Outgoing SMTP failover verification passed.'
            );

            $this->table(
                [
                    'Check',
                    'Value',
                ],
                [
                    [
                        'Mailbox',
                        (string) $mailbox->id,
                    ],
                    [
                        'Primary channel',
                        "{$primary->id} ({$primary->name})",
                    ],
                    [
                        'Fallback channel',
                        "{$fallback->id} ({$fallback->name})",
                    ],
                    [
                        'Failover message',
                        (string) $phaseOne->id,
                    ],
                    [
                        'Cooldown message',
                        (string) $phaseTwo->id,
                    ],
                    [
                        'Recovery message',
                        (string) $phaseThree->id,
                    ],
                    [
                        'Primary health',
                        $primary->health_status->value,
                    ],
                    [
                        'Fallback health',
                        $fallback->health_status->value,
                    ],
                    [
                        'Cooldown seconds',
                        (string) config(
                            'simpledesk-mail.failover.failed_channel_cooldown_seconds',
                            300
                        ),
                    ],
                ]
            );

            return self::SUCCESS;
        } catch (Throwable $exception) {
            $this->newLine();

            $this->components->error(
                $exception->getMessage()
            );

            return self::FAILURE;
        } finally {
            if (
                $primary !== null
                && $primaryConfiguration !== null
            ) {
                $primary->forceFill([
                    'configuration' => $primaryConfiguration,
                ])->save();
            }

            if ($channelSnapshots->isNotEmpty()) {
                $this->restoreTopology(
                    $channelSnapshots
                );
            }
        }
    }

    private function mailbox(): Mailbox
    {
        $mailboxId = filter_var(
            $this->argument('mailbox'),
            FILTER_VALIDATE_INT
        );

        if ($mailboxId === false) {
            throw new RuntimeException(
                'Mailbox ID must be an integer.'
            );
        }

        $mailbox = Mailbox::query()->find(
            (int) $mailboxId
        );

        if ($mailbox === null) {
            throw new RuntimeException(
                "Mailbox [{$mailboxId}] was not found."
            );
        }

        if (! $mailbox->is_active) {
            throw new RuntimeException(
                "Mailbox [{$mailbox->id}] is disabled."
            );
        }

        return $mailbox;
    }

    private function channel(
        string $argument,
        Mailbox $mailbox,
        string $role,
    ): MailboxChannel {
        $channelId = filter_var(
            $this->argument($argument),
            FILTER_VALIDATE_INT
        );

        if ($channelId === false) {
            throw new RuntimeException(
                "{$role} channel ID must be an integer."
            );
        }

        $channel = MailboxChannel::query()
            ->with('providerConnection')
            ->find(
                (int) $channelId
            );

        if ($channel === null) {
            throw new RuntimeException(
                "{$role} channel [{$channelId}] was not found."
            );
        }

        if ($channel->mailbox_id !== $mailbox->id) {
            throw new RuntimeException(
                "{$role} channel [{$channel->id}] does not belong to mailbox [{$mailbox->id}]."
            );
        }

        if (
            $channel->direction
            !== MailboxChannelDirection::Outgoing
        ) {
            throw new RuntimeException(
                "{$role} channel [{$channel->id}] is not outgoing."
            );
        }

        if ($channel->driver !== MailboxDriver::Smtp) {
            throw new RuntimeException(
                "{$role} channel [{$channel->id}] is not SMTP."
            );
        }

        if (
            $channel->provider_connection_id !== null
            && (
                $channel->providerConnection === null
                || ! $channel->providerConnection->is_active
            )
        ) {
            throw new RuntimeException(
                "{$role} channel [{$channel->id}] has an inactive provider connection."
            );
        }

        return $channel;
    }

    private function prepareTopology(
        Collection $channels,
        MailboxChannel $primary,
        MailboxChannel $fallback,
    ): void {
        foreach ($channels as $channel) {
            $isPrimary =
                $channel->id === $primary->id;

            $isFallback =
                $channel->id === $fallback->id;

            $values = [
                'is_enabled' => $isPrimary || $isFallback,

                'is_primary' => $isPrimary,
            ];

            if ($isPrimary) {
                $values['failover_order'] = 0;

                $values['health_status'] =
                    MailboxHealthStatus::Healthy;

                $values['last_error_at'] = null;
                $values['last_error_code'] = null;
                $values['last_error_message'] = null;
            }

            if ($isFallback) {
                $values['failover_order'] = 1;

                $values['health_status'] =
                    MailboxHealthStatus::Healthy;

                $values['last_error_at'] = null;
                $values['last_error_code'] = null;
                $values['last_error_message'] = null;
            }

            $channel->forceFill(
                $values
            )->save();
        }
    }

    private function restoreTopology(
        Collection $snapshots
    ): void {
        foreach (
            $snapshots as $channelId => $snapshot
        ) {
            MailboxChannel::query()
                ->whereKey(
                    (int) $channelId
                )
                ->update(
                    $snapshot
                );
        }
    }

    private function sendMessage(
        OutgoingEmailQueueService $queue,
        OutgoingMailFailoverService $sender,
        Mailbox $mailbox,
        string $recipient,
        string $phase,
    ): EmailMessage {
        $token = (string) Str::uuid();

        $message = $queue->queue(
            mailbox: $mailbox,

            message: new OutgoingEmailMessageData(
                idempotencyKey: "smtp-failover-verification:{$phase}:{$token}",

                from: null,

                to: [
                    new MailAddressData(
                        address: $recipient
                    ),
                ],

                cc: [],
                bcc: [],
                replyTo: [],

                subject: "[SimpleDesk SMTP failover {$phase}] {$token}",

                textBody: "SimpleDesk SMTP failover verification.\n"
                ."Phase: {$phase}\n"
                ."Token: {$token}",

                htmlBody: null,

                headers: [
                    'X-SimpleDesk-Integration-Test' => $token,

                    'X-SimpleDesk-Failover-Phase' => $phase,
                ],

                metadata: [
                    'source' => 'outgoing_failover_verification',

                    'phase' => $phase,

                    'verification_token' => $token,
                ],
            ),

            dispatch: false,
        );

        $sender->send(
            $message
        );

        return $message->fresh([
            'attempts.mailboxChannel',
        ]);
    }

    private function assertFailoverMessage(
        EmailMessage $message,
        int $primaryChannelId,
        int $fallbackChannelId,
    ): void {
        if (
            $message->status
            !== EmailMessageStatus::Sent
        ) {
            throw new RuntimeException(
                "Failover message [{$message->id}] was not sent."
            );
        }

        if (
            $message->mailbox_channel_id
            !== $fallbackChannelId
        ) {
            throw new RuntimeException(
                "Failover message [{$message->id}] used channel [{$message->mailbox_channel_id}] instead of fallback channel [{$fallbackChannelId}]."
            );
        }

        $attempts = $message
            ->attempts
            ->sortBy('attempt_number')
            ->values();

        if ($attempts->count() !== 2) {
            throw new RuntimeException(
                "Failover message [{$message->id}] expected 2 attempts, got {$attempts->count()}."
            );
        }

        $first = $attempts[0];
        $second = $attempts[1];

        if (
            $first->mailbox_channel_id
            !== $primaryChannelId
            || $first->status
            !== EmailMessageAttemptStatus::Failed
            || $first->failover_allowed !== true
        ) {
            throw new RuntimeException(
                "Primary attempt for message [{$message->id}] did not fail with failover allowed."
            );
        }

        if (
            $second->mailbox_channel_id
            !== $fallbackChannelId
            || $second->status
            !== EmailMessageAttemptStatus::Succeeded
        ) {
            throw new RuntimeException(
                "Fallback attempt for message [{$message->id}] did not succeed."
            );
        }
    }

    private function assertSingleChannelMessage(
        EmailMessage $message,
        int $expectedChannelId,
        string $context,
    ): void {
        if (
            $message->status
            !== EmailMessageStatus::Sent
        ) {
            throw new RuntimeException(
                ucfirst($context)
                ." message [{$message->id}] was not sent."
            );
        }

        if (
            $message->mailbox_channel_id
            !== $expectedChannelId
        ) {
            throw new RuntimeException(
                ucfirst($context)
                ." message [{$message->id}] used channel [{$message->mailbox_channel_id}] instead of [{$expectedChannelId}]."
            );
        }

        $attempts = $message
            ->attempts
            ->sortBy('attempt_number')
            ->values();

        if (
            $attempts->count() !== 1
            || $attempts[0]->mailbox_channel_id
            !== $expectedChannelId
            || $attempts[0]->status
            !== EmailMessageAttemptStatus::Succeeded
        ) {
            throw new RuntimeException(
                ucfirst($context)
                ." message [{$message->id}] expected one successful attempt through channel [{$expectedChannelId}]."
            );
        }
    }

    private function printMessageResult(
        string $title,
        EmailMessage $message,
    ): void {
        $this->newLine();

        $this->line(
            $title.':'
        );

        $this->table(
            [
                'Attempt',
                'Channel',
                'Status',
                'Error code',
                'Failover',
            ],

            $message
                ->attempts
                ->sortBy('attempt_number')
                ->map(
                    fn ($attempt): array => [
                        (string) $attempt->attempt_number,

                        $attempt->mailboxChannel !== null
                            ? "{$attempt->mailbox_channel_id} ({$attempt->mailboxChannel->name})"
                            : (string) $attempt->mailbox_channel_id,

                        $attempt->status->value,

                        $attempt->error_code
                        ?? '-',

                        $attempt->failover_allowed === null
                            ? '-'
                            : (
                                $attempt->failover_allowed
                                    ? 'yes'
                                    : 'no'
                            ),
                    ]
                )
                ->all()
        );

        $this->line(
            "Message [{$message->id}] "
            ."status={$message->status->value}, "
            ."selected_channel={$message->mailbox_channel_id}."
        );
    }
}
