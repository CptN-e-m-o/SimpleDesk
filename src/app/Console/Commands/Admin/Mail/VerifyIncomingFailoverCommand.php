<?php

namespace App\Console\Commands\Admin\Mail;

use App\Data\Admin\Mail\IncomingMailboxSyncResultData;
use App\Data\Admin\Mail\MailAddressData;
use App\Data\Admin\Mail\OutgoingEmailMessageData;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxDriver;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\IncomingMailboxSyncService;
use App\Services\Admin\Mail\MailChannelTester;
use App\Services\Admin\Mail\MailDriverRegistry;
use App\Services\Admin\Mail\MailInternetMessageIdFactory;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class VerifyIncomingFailoverCommand extends Command
{
    protected $signature = 'simpledesk:mail:verify-incoming-failover
        {targetMailbox : Mailbox ID whose IMAP failover will be tested}
        {primaryChannel : Primary IMAP channel ID}
        {senderMailbox : Mailbox ID used to inject test messages}
        {senderChannel : SMTP channel ID used to inject test messages}
        {--timeout=60 : Maximum seconds to wait for each injected message}';

    protected $description = 'Verify IMAP failover, cooldown, recovery, and cross-channel deduplication';

    public function handle(
        IncomingMailboxSyncService $synchronizer,
        MailChannelTester $tester,
        MailDriverRegistry $drivers,
        MailInternetMessageIdFactory $messageIds,
    ): int {
        $primary = null;
        $fallback = null;
        $primaryConfiguration = null;
        $snapshots = collect();

        try {
            $targetMailbox = $this->mailbox('targetMailbox', 'Target');
            $senderMailbox = $this->mailbox('senderMailbox', 'Sender');
            $primary = $this->incomingChannel(
                $this->positiveInt('primaryChannel'),
                $targetMailbox
            );
            $senderChannel = $this->outgoingChannel(
                $this->positiveInt('senderChannel'),
                $senderMailbox
            );

            $timeout = max(
                5,
                min(
                    300,
                    (int) $this->option('timeout')
                )
            );

            $primaryConfiguration = $primary->configuration ?? [];
            $snapshots = $this->snapshotTopology($targetMailbox);

            $this->setPrimaryOnly(
                $targetMailbox,
                $primary
            );

            $this->components->info(
                'Preparing the primary IMAP cursor before the failover test.'
            );

            $baseline = $synchronizer->synchronize(
                $targetMailbox->fresh()
            );

            $this->assertSelectedChannel(
                result: $baseline,
                expectedChannelId: $primary->id,
                phase: 'Baseline',
            );

            $fallback = $this->createTemporaryFallback(
                $primary->fresh()
            );

            $this->copyCursor(
                source: $primary,
                target: $fallback,
            );

            $this->setFailoverTopology(
                mailbox: $targetMailbox,
                primary: $primary,
                fallback: $fallback,
            );

            $this->components->info(
                'Phase 1: primary IMAP failure and fallback synchronization.'
            );

            $primary->forceFill([
                'configuration' => array_replace(
                    $primaryConfiguration,
                    [
                        'host' => '127.0.0.1',
                        'port' => 1,
                        'encryption' => 'none',
                        'validate_cert' => false,
                    ]
                ),
            ])->save();

            [
                $phaseOneResult,
                $phaseOneMessage,
            ] = $this->sendAndSynchronize(
                phase: 'failover',
                expectedChannel: $fallback,
                targetMailbox: $targetMailbox,
                senderMailbox: $senderMailbox,
                senderChannel: $senderChannel,
                synchronizer: $synchronizer,
                drivers: $drivers,
                messageIds: $messageIds,
                timeout: $timeout,
            );

            $primary->refresh();

            if (
                $primary->health_status
                !== MailboxHealthStatus::Failed
            ) {
                throw new RuntimeException(
                    "Primary channel [{$primary->id}] was not marked failed."
                );
            }

            $this->printPhase(
                title: 'Phase 1 result',
                result: $phaseOneResult,
                message: $phaseOneMessage,
                primary: $primary,
                fallback: $fallback,
            );

            $primary->forceFill([
                'configuration' => $primaryConfiguration,
            ])->save();

            $this->components->info(
                'Phase 2: failed primary is skipped during cooldown.'
            );

            [
                $phaseTwoResult,
                $phaseTwoMessage,
            ] = $this->sendAndSynchronize(
                phase: 'cooldown',
                expectedChannel: $fallback,
                targetMailbox: $targetMailbox,
                senderMailbox: $senderMailbox,
                senderChannel: $senderChannel,
                synchronizer: $synchronizer,
                drivers: $drivers,
                messageIds: $messageIds,
                timeout: $timeout,
            );

            $this->printPhase(
                title: 'Phase 2 result',
                result: $phaseTwoResult,
                message: $phaseTwoMessage,
                primary: $primary->fresh(),
                fallback: $fallback->fresh(),
            );

            $this->components->info(
                'Phase 3: recover the primary and verify cross-channel deduplication.'
            );

            $connectionTest = $tester->test(
                $primary->fresh([
                    'providerConnection',
                ])
            );

            if (! $connectionTest->successful) {
                throw new RuntimeException(
                    'Primary IMAP recovery connection test failed: '
                    .$connectionTest->message
                );
            }

            [
                $phaseThreeResult,
                $phaseThreeMessage,
            ] = $this->sendAndSynchronize(
                phase: 'recovery',
                expectedChannel: $primary,
                targetMailbox: $targetMailbox,
                senderMailbox: $senderMailbox,
                senderChannel: $senderChannel,
                synchronizer: $synchronizer,
                drivers: $drivers,
                messageIds: $messageIds,
                timeout: $timeout,
            );

            if ($phaseThreeResult->duplicates < 2) {
                throw new RuntimeException(
                    'Phase 3 expected at least 2 duplicates '
                    .'already stored through fallback, got '
                    ."{$phaseThreeResult->duplicates}."
                );
            }

            $this->printPhase(
                title: 'Phase 3 result',
                result: $phaseThreeResult,
                message: $phaseThreeMessage,
                primary: $primary->fresh(),
                fallback: $fallback->fresh(),
            );

            $primary->refresh();
            $fallback->refresh();

            $this->newLine();

            $this->components->info(
                'Incoming IMAP failover verification passed.'
            );

            $this->table(
                [
                    'Check',
                    'Value',
                ],
                [
                    [
                        'Target mailbox',
                        (string) $targetMailbox->id,
                    ],
                    [
                        'Primary channel',
                        "{$primary->id} ({$primary->name})",
                    ],
                    [
                        'Temporary fallback',
                        "{$fallback->id} ({$fallback->name})",
                    ],
                    [
                        'Failover message',
                        (string) $phaseOneMessage->id,
                    ],
                    [
                        'Cooldown message',
                        (string) $phaseTwoMessage->id,
                    ],
                    [
                        'Recovery message',
                        (string) $phaseThreeMessage->id,
                    ],
                    [
                        'Recovery duplicates',
                        (string) $phaseThreeResult->duplicates,
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

            if ($snapshots->isNotEmpty()) {
                $this->restoreTopology(
                    $snapshots
                );
            }

            $fallback?->delete();
        }
    }

    private function sendAndSynchronize(
        string $phase,
        MailboxChannel $expectedChannel,
        Mailbox $targetMailbox,
        Mailbox $senderMailbox,
        MailboxChannel $senderChannel,
        IncomingMailboxSyncService $synchronizer,
        MailDriverRegistry $drivers,
        MailInternetMessageIdFactory $messageIds,
        int $timeout,
    ): array {
        $subject = $this->injectMessage(
            phase: $phase,
            targetMailbox: $targetMailbox,
            senderMailbox: $senderMailbox,
            senderChannel: $senderChannel,
            drivers: $drivers,
            messageIds: $messageIds,
        );

        [
            $result,
            $message,
        ] = $this->waitForMessage(
            synchronizer: $synchronizer,
            mailbox: $targetMailbox,
            subject: $subject,
            timeout: $timeout,
        );

        $this->assertSelectedChannel(
            result: $result,
            expectedChannelId: $expectedChannel->id,
            phase: ucfirst($phase),
        );

        if (
            $message->mailbox_channel_id
            !== $expectedChannel->id
        ) {
            throw new RuntimeException(
                ucfirst($phase)
                ." message [{$message->id}] was stored "
                ."through channel [{$message->mailbox_channel_id}] "
                ."instead of [{$expectedChannel->id}]."
            );
        }

        $count = EmailMessage::query()
            ->where(
                'mailbox_id',
                $targetMailbox->id
            )
            ->where(
                'direction',
                EmailMessageDirection::Incoming->value
            )
            ->where(
                'subject',
                $subject
            )
            ->count();

        if ($count !== 1) {
            throw new RuntimeException(
                'Expected exactly one stored message '
                ."with subject [{$subject}], got {$count}."
            );
        }

        return [
            $result,
            $message,
        ];
    }

    private function injectMessage(
        string $phase,
        Mailbox $targetMailbox,
        Mailbox $senderMailbox,
        MailboxChannel $senderChannel,
        MailDriverRegistry $drivers,
        MailInternetMessageIdFactory $messageIds,
    ): string {
        $token = (string) Str::uuid();

        $idempotencyKey =
            "imap-failover-verification:{$phase}:{$token}";

        $subject =
            "[SimpleDesk IMAP failover {$phase}] {$token}";

        $message = new OutgoingEmailMessageData(
            idempotencyKey: $idempotencyKey,

            from: new MailAddressData(
                address: $senderMailbox->email_address,
                name: $senderMailbox->display_name
                ?? $senderMailbox->name,
            ),

            to: [
                new MailAddressData(
                    address: $targetMailbox->email_address,
                    name: $targetMailbox->display_name
                    ?? $targetMailbox->name,
                ),
            ],

            cc: [],
            bcc: [],
            replyTo: [],

            subject: $subject,

            textBody: "SimpleDesk IMAP failover verification.\n"
            ."Phase: {$phase}\n"
            ."Token: {$token}",

            htmlBody: null,

            headers: [
                'X-SimpleDesk-Integration-Test' => $token,

                'X-SimpleDesk-Failover-Phase' => $phase,
            ],

            attachments: [],

            internetMessageId: $messageIds->make(
                mailbox: $senderMailbox,
                idempotencyKey: $idempotencyKey,
            ),

            inReplyToMessageId: null,

            references: [],

            metadata: [
                'source' => 'incoming_failover_verification',

                'phase' => $phase,

                'verification_token' => $token,
            ],
        );

        $result = $drivers
            ->outgoing(
                $senderChannel->driver
            )
            ->send(
                channel: $senderChannel,
                message: $message,
            );

        if ($result->acceptedRecipients === []) {
            throw new RuntimeException(
                "SMTP did not accept the {$phase} verification message."
            );
        }

        return $subject;
    }

    private function waitForMessage(
        IncomingMailboxSyncService $synchronizer,
        Mailbox $mailbox,
        string $subject,
        int $timeout,
    ): array {
        $deadline = microtime(true) + $timeout;
        $lastResult = null;

        do {
            $lastResult = $synchronizer->synchronize(
                $mailbox->fresh()
            );

            $message = EmailMessage::query()
                ->where(
                    'mailbox_id',
                    $mailbox->id
                )
                ->where(
                    'direction',
                    EmailMessageDirection::Incoming->value
                )
                ->where(
                    'subject',
                    $subject
                )
                ->latest('id')
                ->first();

            if ($message !== null) {
                return [
                    $lastResult,
                    $message,
                ];
            }

            usleep(1_000_000);
        } while (
            microtime(true) < $deadline
        );

        throw new RuntimeException(
            'IMAP did not store verification message '
            ."[{$subject}] within {$timeout} seconds. "
            .'Last channel: '
            .(
                $lastResult?->mailboxChannelId
                ?? 'none'
            )
            .'.'
        );
    }

    private function mailbox(
        string $argument,
        string $role,
    ): Mailbox {
        $id = $this->positiveInt(
            $argument
        );

        $mailbox = Mailbox::query()->find(
            $id
        );

        if ($mailbox === null) {
            throw new RuntimeException(
                "{$role} mailbox [{$id}] was not found."
            );
        }

        if (! $mailbox->is_active) {
            throw new RuntimeException(
                "{$role} mailbox [{$id}] is disabled."
            );
        }

        if (
            filter_var(
                $mailbox->email_address,
                FILTER_VALIDATE_EMAIL
            ) === false
        ) {
            throw new RuntimeException(
                "{$role} mailbox [{$id}] has an invalid email address."
            );
        }

        return $mailbox;
    }

    private function incomingChannel(
        int $id,
        Mailbox $mailbox,
    ): MailboxChannel {
        $channel = MailboxChannel::query()
            ->with(
                'providerConnection'
            )
            ->find($id);

        $this->assertChannel(
            channel: $channel,
            mailbox: $mailbox,
            direction: MailboxChannelDirection::Incoming,
            driver: MailboxDriver::Imap,
            role: 'Primary',
        );

        return $channel;
    }

    private function outgoingChannel(
        int $id,
        Mailbox $mailbox,
    ): MailboxChannel {
        $channel = MailboxChannel::query()
            ->with(
                'providerConnection'
            )
            ->find($id);

        $this->assertChannel(
            channel: $channel,
            mailbox: $mailbox,
            direction: MailboxChannelDirection::Outgoing,
            driver: MailboxDriver::Smtp,
            role: 'Sender',
        );

        if (! $channel->is_enabled) {
            throw new RuntimeException(
                "Sender channel [{$channel->id}] is disabled."
            );
        }

        return $channel;
    }

    private function assertChannel(
        ?MailboxChannel $channel,
        Mailbox $mailbox,
        MailboxChannelDirection $direction,
        MailboxDriver $driver,
        string $role,
    ): void {
        if ($channel === null) {
            throw new RuntimeException(
                "{$role} channel was not found."
            );
        }

        if (
            $channel->mailbox_id
            !== $mailbox->id
        ) {
            throw new RuntimeException(
                "{$role} channel [{$channel->id}] "
                ."does not belong to mailbox [{$mailbox->id}]."
            );
        }

        if (
            $channel->direction !== $direction
            || $channel->driver !== $driver
        ) {
            throw new RuntimeException(
                "{$role} channel [{$channel->id}] must be "
                ."{$direction->value}/{$driver->value}."
            );
        }

        if (
            $channel->provider_connection_id !== null
            && (
                $channel->providerConnection === null
                || ! $channel
                    ->providerConnection
                    ->is_active
            )
        ) {
            throw new RuntimeException(
                "{$role} channel [{$channel->id}] "
                .'has an inactive provider connection.'
            );
        }
    }

    private function positiveInt(
        string $argument
    ): int {
        $value = filter_var(
            $this->argument($argument),
            FILTER_VALIDATE_INT
        );

        if (
            $value === false
            || (int) $value < 1
        ) {
            throw new RuntimeException(
                "Argument [{$argument}] must be a positive integer."
            );
        }

        return (int) $value;
    }

    private function snapshotTopology(
        Mailbox $mailbox
    ): Collection {
        return $mailbox
            ->channels()
            ->where(
                'direction',
                MailboxChannelDirection::Incoming->value
            )
            ->get()
            ->mapWithKeys(
                fn (
                    MailboxChannel $channel
                ): array => [
                    $channel->id => [
                        'is_enabled' => $channel->is_enabled,

                        'is_primary' => $channel->is_primary,

                        'failover_order' => $channel->failover_order,
                    ],
                ]
            );
    }

    private function setPrimaryOnly(
        Mailbox $mailbox,
        MailboxChannel $primary,
    ): void {
        $this->disableIncomingChannels(
            $mailbox
        );

        $this->activateChannel(
            channel: $primary,
            primary: true,
            order: 0,
        );
    }

    private function setFailoverTopology(
        Mailbox $mailbox,
        MailboxChannel $primary,
        MailboxChannel $fallback,
    ): void {
        $this->disableIncomingChannels(
            $mailbox
        );

        $this->activateChannel(
            channel: $primary,
            primary: true,
            order: 0,
        );

        $this->activateChannel(
            channel: $fallback,
            primary: false,
            order: 1,
        );
    }

    private function disableIncomingChannels(
        Mailbox $mailbox
    ): void {
        MailboxChannel::query()
            ->where(
                'mailbox_id',
                $mailbox->id
            )
            ->where(
                'direction',
                MailboxChannelDirection::Incoming->value
            )
            ->update([
                'is_enabled' => false,
                'is_primary' => false,
            ]);
    }

    private function activateChannel(
        MailboxChannel $channel,
        bool $primary,
        int $order,
    ): void {
        MailboxChannel::query()
            ->whereKey($channel->id)
            ->update([
                'is_enabled' => true,
                'is_primary' => $primary,
                'failover_order' => $order,
                'health_status' => MailboxHealthStatus::Healthy->value,
                'last_error_at' => null,
                'last_error_code' => null,
                'last_error_message' => null,
            ]);

        $channel->refresh();
    }

    private function createTemporaryFallback(
        MailboxChannel $primary
    ): MailboxChannel {
        $fallback = $primary->replicate();

        $fallback->forceFill([
            'name' => mb_substr(
                "[Integration test] {$primary->name} fallback",
                0,
                120
            ),

            'is_enabled' => false,
            'is_primary' => false,
            'failover_order' => 1,

            'health_status' => MailboxHealthStatus::Healthy,

            'last_checked_at' => null,
            'last_success_at' => null,
            'last_activity_at' => null,
            'last_error_at' => null,
            'last_error_code' => null,
            'last_error_message' => null,
        ])->save();

        return $fallback->fresh([
            'providerConnection',
        ]);
    }

    private function copyCursor(
        MailboxChannel $source,
        MailboxChannel $target,
    ): void {
        $state = $source
            ->syncState()
            ->first();

        $target
            ->syncState()
            ->updateOrCreate(
                [],
                [
                    'cursor' => $state?->cursor,

                    'cursor_metadata' => $state?->cursor_metadata,

                    'last_sync_started_at' => null,

                    'last_sync_completed_at' => $state?->last_sync_completed_at,

                    'last_sync_failed_at' => null,

                    'consecutive_failures' => 0,

                    'last_fetched_count' => 0,
                    'last_stored_count' => 0,
                    'last_duplicate_count' => 0,
                    'last_acknowledged_count' => 0,

                    'last_error_code' => null,
                    'last_error_message' => null,
                ]
            );
    }

    private function restoreTopology(
        Collection $snapshots
    ): void {
        $ids = $snapshots
            ->keys()
            ->map(
                fn (
                    mixed $id
                ): int => (int) $id
            )
            ->all();

        if ($ids === []) {
            return;
        }

        MailboxChannel::query()
            ->whereIn(
                'id',
                $ids
            )
            ->update([
                'is_primary' => false,
            ]);

        foreach (
            $snapshots as $id => $snapshot
        ) {
            MailboxChannel::query()
                ->whereKey(
                    (int) $id
                )
                ->update([
                    'is_enabled' => $snapshot['is_enabled'],

                    'is_primary' => false,

                    'failover_order' => $snapshot['failover_order'],
                ]);
        }

        foreach (
            $snapshots as $id => $snapshot
        ) {
            if (! $snapshot['is_primary']) {
                continue;
            }

            MailboxChannel::query()
                ->whereKey(
                    (int) $id
                )
                ->update([
                    'is_primary' => true,
                ]);
        }
    }

    private function assertSelectedChannel(
        IncomingMailboxSyncResultData $result,
        int $expectedChannelId,
        string $phase,
    ): void {
        if (
            $result->mailboxChannelId
            !== $expectedChannelId
        ) {
            throw new RuntimeException(
                "{$phase} synchronization used channel "
                ."[{$result->mailboxChannelId}] instead of "
                ."[{$expectedChannelId}]."
            );
        }
    }

    private function printPhase(
        string $title,
        IncomingMailboxSyncResultData $result,
        EmailMessage $message,
        MailboxChannel $primary,
        MailboxChannel $fallback,
    ): void {
        $this->newLine();

        $this->line(
            "{$title}:"
        );

        $this->table(
            [
                'Parameter',
                'Value',
            ],
            [
                [
                    'Selected channel',
                    (string) $result->mailboxChannelId,
                ],
                [
                    'Fetched',
                    (string) $result->fetched,
                ],
                [
                    'Stored',
                    (string) $result->stored,
                ],
                [
                    'Duplicates',
                    (string) $result->duplicates,
                ],
                [
                    'Acknowledged',
                    (string) $result->acknowledged,
                ],
                [
                    'Message ID',
                    (string) $message->id,
                ],
                [
                    'Message channel',
                    (string) $message->mailbox_channel_id,
                ],
                [
                    'Primary health',
                    $primary->health_status->value,
                ],
                [
                    'Fallback health',
                    $fallback->health_status->value,
                ],
            ]
        );
    }
}
