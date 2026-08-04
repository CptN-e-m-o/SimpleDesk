<?php

namespace App\Services\Admin\Mail\Diagnostics;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageDirection;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\MailAdminAuditEvent;
use App\Enums\Admin\Mail\MailboxChannelDirection;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailAttachmentRejection;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Models\Admin\Mail\MailAdminAuditLog;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailboxChannelSyncState;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\MailSensitiveDataRedactor;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class MailDiagnosticsService
{
    public function __construct(
        private readonly MailDiagnosticsThresholds $thresholds,
        private readonly MailSensitiveDataRedactor $redactor,
    ) {}

    public function overview(): array
    {
        return [
            'generated_at' => now()->toIso8601String(),

            'mailboxes' => $this->mailboxSummary(),

            'channels' => $this->channelSummary(),

            'provider_connections' => $this->providerConnectionSummary(),

            'messages' => $this->messageSummary(),

            'attachments' => $this->attachmentSummary(),

            'synchronization' => $this->syncSummary(),

            'quarantine' => $this->quarantineSummary(),

            'recent_errors' => $this->recentErrors(),
        ];
    }

    public function dashboard(): array
    {
        $mailboxes = Mailbox::query();
        $channels = MailboxChannel::query()
            ->whereHas('mailbox')
            ->with([
                'mailbox:id,name,email_address,is_active',
                'providerConnection:id,is_active',
                'syncState:mailbox_channel_id,consecutive_failures',
            ])
            ->orderBy('mailbox_id')
            ->orderBy('direction')
            ->orderBy('failover_order')
            ->orderBy('id')
            ->get();

        $messageStatistics = $this->dashboardMessageStatistics();
        $channelItems = $channels
            ->map(fn (MailboxChannel $channel): array => $this->dashboardChannel($channel))
            ->values()
            ->all();
        $antivirus = $this->antivirusSnapshot();
        $system = $this->systemSnapshot();
        $overall = $this->overallStatus(
            channels: $channelItems,
            messages: $messageStatistics,
            antivirus: $antivirus,
            system: $system,
        );

        $failedChannels = collect($channelItems)
            ->where('health_status', MailboxHealthStatus::Failed->value)
            ->count();
        $warningChannels = collect($channelItems)
            ->where('health_status', MailboxHealthStatus::Warning->value)
            ->count();

        return [
            'generated_at' => now()->toIso8601String(),
            'summary' => [
                'overall_status' => $overall['status'],
                'overall_message' => $overall['message'],
                'mailboxes_total' => (clone $mailboxes)->count(),
                'mailboxes_active' => (clone $mailboxes)->where('is_active', true)->count(),
                'channels_total' => count($channelItems),
                'channels_healthy' => collect($channelItems)->where('health_status', MailboxHealthStatus::Healthy->value)->count(),
                'channels_degraded' => $warningChannels,
                'channels_failed' => $failedChannels,
                'messages_last_24_hours' => $messageStatistics['incoming_last_24_hours'] + $messageStatistics['outgoing_last_24_hours'],
                'failed_messages_last_24_hours' => $messageStatistics['failed_last_24_hours'],
                'stuck_messages' => $messageStatistics['stuck_preparing']
                    + $messageStatistics['stuck_queued']
                    + $messageStatistics['stuck_processing']
                    + $messageStatistics['stuck_sending'],
                'antivirus_status' => $antivirus['status'],
            ],
            'channels' => $channelItems,
            'message_statistics' => $messageStatistics,
            'recent_failures' => $this->dashboardRecentFailures(),
            'antivirus' => $antivirus,
            'system' => $system,
        ];
    }

    private function dashboardChannel(MailboxChannel $channel): array
    {
        $providerAvailable = $channel->provider_connection_id === null
            || ($channel->providerConnection?->is_active ?? false);
        $available = $channel->is_enabled
            && ($channel->mailbox?->is_active ?? false)
            && $providerAvailable;
        $cooldownUntil = null;

        if ($channel->health_status === MailboxHealthStatus::Failed
            && $channel->last_error_at !== null) {
            $cooldownUntil = $channel->last_error_at
                ->addSeconds((int) config('simpledesk-mail.failover.failed_channel_cooldown_seconds', 300))
                ->toIso8601String();
        }

        return [
            'id' => $channel->id,
            'mailbox_id' => $channel->mailbox_id,
            'mailbox_name' => $channel->mailbox?->name,
            'mailbox_email' => $channel->mailbox?->email_address,
            'name' => $channel->name,
            'direction' => $channel->direction->value,
            'driver' => $channel->driver->value,
            'is_enabled' => $channel->is_enabled,
            'is_available' => $available,
            'health_status' => $channel->health_status->value,
            'consecutive_failures' => $channel->syncState?->consecutive_failures,
            'last_success_at' => $channel->last_success_at?->toIso8601String(),
            'last_failure_at' => $channel->last_error_at?->toIso8601String(),
            'last_checked_at' => $channel->last_checked_at?->toIso8601String(),
            'last_error_message' => $channel->last_error_message === null
                ? null
                : $this->redactor->redactString($channel->last_error_message),
            'cooldown_until' => $cooldownUntil,
            'can_test' => $channel->is_enabled && ($channel->mailbox?->is_active ?? false),
        ];
    }

    private function dashboardMessageStatistics(): array
    {
        $dayAgo = now()->subDay();

        $recent = EmailMessage::query()
            ->where(
                'created_at',
                '>=',
                $dayAgo
            );

        return [
            'incoming_last_24_hours' => (clone $recent)
                ->where(
                    'direction',
                    EmailMessageDirection::Incoming->value
                )
                ->count(),

            'outgoing_last_24_hours' => (clone $recent)
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->count(),

            'processed_last_24_hours' => EmailMessage::query()
                ->where(
                    'processed_at',
                    '>=',
                    $dayAgo
                )
                ->count(),

            'sent_last_24_hours' => EmailMessage::query()
                ->where(
                    'sent_at',
                    '>=',
                    $dayAgo
                )
                ->count(),

            'failed_last_24_hours' => EmailMessage::query()
                ->where(
                    'failed_at',
                    '>=',
                    $dayAgo
                )
                ->count(),

            'currently_processing' => EmailMessage::query()
                ->where(
                    'direction',
                    EmailMessageDirection::Incoming->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Processing->value
                )
                ->count(),

            'currently_sending' => EmailMessage::query()
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Sending->value
                )
                ->count(),

            'stuck_preparing' => EmailMessage::query()
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Preparing->value
                )
                ->where(
                    'created_at',
                    '<=',
                    $this
                        ->thresholds
                        ->preparingCutoff()
                )
                ->count(),

            'stuck_queued' => EmailMessage::query()
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Queued->value
                )
                ->where(function (
                    Builder $query
                ): void {
                    $query
                        ->where(function (
                            Builder $query
                        ): void {
                            $query
                                ->whereNotNull(
                                    'queued_at'
                                )
                                ->where(
                                    'queued_at',
                                    '<=',
                                    $this
                                        ->thresholds
                                        ->queuedCutoff()
                                );
                        })
                        ->orWhere(function (
                            Builder $query
                        ): void {
                            $query
                                ->whereNull(
                                    'queued_at'
                                )
                                ->where(
                                    'created_at',
                                    '<=',
                                    $this
                                        ->thresholds
                                        ->queuedCutoff()
                                );
                        });
                })
                ->count(),

            'stuck_processing' => EmailMessage::query()
                ->where(
                    'direction',
                    EmailMessageDirection::Incoming->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Processing->value
                )
                ->whereNotNull(
                    'processing_started_at'
                )
                ->where(
                    'processing_started_at',
                    '<=',
                    $this
                        ->thresholds
                        ->processingCutoff()
                )
                ->count(),

            'stuck_sending' => EmailMessage::query()
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Sending->value
                )
                ->whereNotNull(
                    'processing_started_at'
                )
                ->where(
                    'processing_started_at',
                    '<=',
                    $this
                        ->thresholds
                        ->sendingCutoff()
                )
                ->count(),
        ];
    }

    private function dashboardRecentFailures(): array
    {
        return EmailMessage::query()
            ->where('status', EmailMessageStatus::Failed->value)
            ->with('mailbox:id,name')
            ->latest('failed_at')
            ->latest('id')
            ->limit(10)
            ->get()
            ->map(fn (EmailMessage $message): array => [
                'id' => $message->id,
                'direction' => $message->direction->value,
                'subject' => $message->subject,
                'mailbox_id' => $message->mailbox_id,
                'mailbox_name' => $message->mailbox?->name,
                'failure_code' => $message->failure_code,
                'failure_message' => $message->failure_message === null
                    ? null
                    : $this->redactor->redactString($message->failure_message),
                'failed_at' => $message->failed_at?->toIso8601String(),
                'ticket_id' => $message->ticket_id,
            ])
            ->values()
            ->all();
    }

    private function antivirusSnapshot(): array
    {
        $configured = (bool) config('simpledesk-mail-antivirus.enabled', false);
        $audit = MailAdminAuditLog::query()
            ->where('event', MailAdminAuditEvent::AntivirusConnectionTested->value)
            ->latest('created_at')
            ->first();
        $successful = data_get($audit?->context, 'result.successful');
        $status = ! $configured
            ? 'not_configured'
            : ($successful === true ? 'healthy' : ($successful === false ? 'failed' : 'unknown'));
        $message = data_get($audit?->context, 'result.message');

        return [
            'configured' => $configured,
            'status' => $status,
            'last_checked_at' => $audit?->created_at?->toIso8601String(),
            'message' => is_string($message)
                ? $this->redactor->redactString($message)
                : ($configured ? 'Antivirus has not been tested yet.' : 'Attachment antivirus scanning is optional and not configured.'),
        ];
    }

    private function systemSnapshot(): array
    {
        return [
            'mail_ticketing_enabled' => (bool) config(
                'simpledesk-mail-ticketing.enabled',
                true
            ),

            'outgoing_replies_enabled' => (bool) config(
                'simpledesk-mail-ticketing.outgoing_replies.enabled',
                true
            ),

            'incoming_queue' => (string) config(
                'simpledesk-mail-ticketing.queue',
                'mail-incoming'
            ),

            'incoming_queue_connection' => (string) (
                config(
                    'simpledesk-mail-ticketing.queue_connection'
                )
                    ?: config('queue.default')
            ),

            'outgoing_queue' => (string) config(
                'simpledesk-mail-ticketing.outgoing_replies.queue',
                'mail-outgoing'
            ),

            'outgoing_queue_connection' => (string) (
                config(
                    'simpledesk-mail-ticketing.outgoing_replies.queue_connection'
                )
                    ?: config('queue.default')
            ),

            'attachment_scanning_enabled' => (bool) config(
                'simpledesk-mail-antivirus.enabled',
                false
            ),

            'reply_parsing_enabled' => (bool) config(
                'simpledesk-mail-reply-parsing.enabled',
                true
            ),
        ];
    }

    private function overallStatus(
        array $channels,
        array $messages,
        array $antivirus,
        array $system,
    ): array {
        $availableChannels = collect(
            $channels
        )->where(
            'is_available',
            true
        );

        $operationalIncoming = $availableChannels
            ->where(
                'direction',
                MailboxChannelDirection::Incoming->value
            )
            ->reject(
                fn (array $channel): bool => $channel['health_status']
                    === MailboxHealthStatus::Failed->value
            )
            ->count();

        $operationalOutgoing = $availableChannels
            ->where(
                'direction',
                MailboxChannelDirection::Outgoing->value
            )
            ->reject(
                fn (array $channel): bool => $channel['health_status']
                    === MailboxHealthStatus::Failed->value
            )
            ->count();

        if (
            $messages['stuck_preparing']
            + $messages['stuck_queued']
            + $messages['stuck_processing']
            + $messages['stuck_sending']
            > 0
        ) {
            return [
                'status' => 'critical',

                'message' => 'Email processing contains stuck messages.',
            ];
        }

        if (
            $system['mail_ticketing_enabled']
            && $operationalIncoming === 0
        ) {
            return [
                'status' => 'critical',

                'message' => 'Mail ticketing is enabled, but no operational incoming channel is available.',
            ];
        }

        if (
            $system['outgoing_replies_enabled']
            && $operationalOutgoing === 0
        ) {
            return [
                'status' => 'critical',

                'message' => 'Outgoing replies are enabled, but no operational outgoing channel is available.',
            ];
        }

        if (
            $antivirus['configured']
            && $antivirus['status'] === 'failed'
        ) {
            return [
                'status' => 'critical',

                'message' => 'Required attachment antivirus is unavailable.',
            ];
        }

        $hasChannelWarning =
            $availableChannels->contains(
                fn (array $channel): bool => in_array(
                    $channel['health_status'],
                    [
                        MailboxHealthStatus::Warning->value,
                        MailboxHealthStatus::Failed->value,
                        'unknown',
                    ],
                    true
                )
            );

        $hasWarning =
            $hasChannelWarning
            || $messages['failed_last_24_hours'] > 0
            || ! $antivirus['configured'];

        if ($hasWarning) {
            return [
                'status' => 'warning',

                'message' => 'Email is operational, but one or more items need attention.',
            ];
        }

        return [
            'status' => 'healthy',

            'message' => 'Mail channels and processing are operating normally.',
        ];
    }

    public function mailbox(
        Mailbox $mailbox
    ): array {
        $mailbox->load([
            'department:id,name',

            'channels' => fn ($query) => $query
                ->with('syncState')
                ->orderBy('direction')
                ->orderByDesc('is_primary')
                ->orderBy('failover_order')
                ->orderBy('id'),
        ]);

        $messageBase = EmailMessage::query()
            ->where(
                'mailbox_id',
                $mailbox->id
            );

        $attachmentBase = EmailAttachment::query()
            ->whereHas(
                'emailMessage',
                fn (Builder $query) => $query->where(
                    'mailbox_id',
                    $mailbox->id
                )
            );

        $quarantineBase =
            EmailMessageQuarantine::query()
                ->where(
                    'mailbox_id',
                    $mailbox->id
                );

        return [
            'generated_at' => now()->toIso8601String(),

            'mailbox' => [
                'id' => $mailbox->id,
                'name' => $mailbox->name,
                'email_address' => $mailbox->email_address,
                'display_name' => $mailbox->display_name,

                'department' => $mailbox->department === null
                        ? null
                        : [
                            'id' => $mailbox->department->id,

                            'name' => $mailbox->department->name,
                        ],

                'is_active' => $mailbox->is_active,

                'is_default_outgoing' => $mailbox->is_default_outgoing,
            ],

            'channels' => $mailbox
                ->channels
                ->map(
                    fn (
                        MailboxChannel $channel
                    ): array => $this->channelDetails(
                        $channel
                    )
                )
                ->values()
                ->all(),

            'messages' => [
                'total' => (clone $messageBase)->count(),

                'by_direction' => $this->groupedCounts(
                    clone $messageBase,
                    'direction',
                    array_map(
                        static fn (
                            EmailMessageDirection $direction
                        ): string => $direction->value,
                        EmailMessageDirection::cases()
                    )
                ),

                'by_status' => $this->groupedCounts(
                    clone $messageBase,
                    'status',
                    array_map(
                        static fn (
                            EmailMessageStatus $status
                        ): string => $status->value,
                        EmailMessageStatus::cases()
                    )
                ),

                'stuck' => $this->stuckMessageCounts(
                    clone $messageBase
                ),

                'recent' => $this->recentMessages(
                    $mailbox->id
                ),
            ],

            'attachments' => [
                'total' => (clone $attachmentBase)->count(),

                'by_scan_status' => $this->groupedCounts(
                    clone $attachmentBase,
                    'scan_status',
                    array_map(
                        static fn (
                            EmailAttachmentScanStatus $status
                        ): string => $status->value,
                        EmailAttachmentScanStatus::cases()
                    )
                ),

                'stale_pending' => (clone $attachmentBase)
                    ->where(
                        'scan_status',
                        EmailAttachmentScanStatus::Pending
                            ->value
                    )
                    ->where(
                        'updated_at',
                        '<=',
                        $this
                            ->thresholds
                            ->attachmentPendingCutoff()
                    )
                    ->count(),

                'rejected' => EmailAttachmentRejection::query()
                    ->whereHas(
                        'emailMessage',
                        fn (
                            Builder $query
                        ) => $query->where(
                            'mailbox_id',
                            $mailbox->id
                        )
                    )
                    ->count(),
            ],

            'quarantine' => [
                'total' => (clone $quarantineBase)->count(),

                'open' => (clone $quarantineBase)
                    ->whereNull('resolved_at')
                    ->count(),

                'released_for_retry' => (clone $quarantineBase)
                    ->whereNotNull('released_at')
                    ->whereNull('resolved_at')
                    ->count(),

                'resolved' => (clone $quarantineBase)
                    ->whereNotNull('resolved_at')
                    ->count(),
            ],

            'recent_errors' => $this->recentErrors(
                $mailbox->id
            ),
        ];
    }

    private function mailboxSummary(): array
    {
        return [
            'total' => Mailbox::query()->count(),

            'active' => Mailbox::query()
                ->where('is_active', true)
                ->count(),

            'inactive' => Mailbox::query()
                ->where('is_active', false)
                ->count(),

            'default_outgoing' => Mailbox::query()
                ->where(
                    'is_default_outgoing',
                    true
                )
                ->count(),
        ];
    }

    private function channelSummary(): array
    {
        return [
            'total' => MailboxChannel::query()->count(),

            'enabled' => MailboxChannel::query()
                ->where('is_enabled', true)
                ->count(),

            'disabled' => MailboxChannel::query()
                ->where('is_enabled', false)
                ->count(),

            'by_direction' => $this->groupedCounts(
                MailboxChannel::query(),
                'direction',
                array_map(
                    static fn (
                        MailboxChannelDirection $direction
                    ): string => $direction->value,
                    MailboxChannelDirection::cases()
                )
            ),

            'by_health' => $this->groupedCounts(
                MailboxChannel::query(),
                'health_status',
                array_map(
                    static fn (
                        MailboxHealthStatus $status
                    ): string => $status->value,
                    MailboxHealthStatus::cases()
                )
            ),
        ];
    }

    private function providerConnectionSummary(): array
    {
        return [
            'total' => MailProviderConnection::query()->count(),

            'active' => MailProviderConnection::query()
                ->where('is_active', true)
                ->count(),

            'inactive' => MailProviderConnection::query()
                ->where('is_active', false)
                ->count(),

            'by_health' => $this->groupedCounts(
                MailProviderConnection::query(),
                'health_status',
                array_map(
                    static fn (
                        MailboxHealthStatus $status
                    ): string => $status->value,
                    MailboxHealthStatus::cases()
                )
            ),
        ];
    }

    private function messageSummary(): array
    {
        $query = EmailMessage::query();

        return [
            'total' => (clone $query)->count(),

            'by_direction' => $this->groupedCounts(
                clone $query,
                'direction',
                array_map(
                    static fn (
                        EmailMessageDirection $direction
                    ): string => $direction->value,
                    EmailMessageDirection::cases()
                )
            ),

            'by_status' => $this->groupedCounts(
                clone $query,
                'status',
                array_map(
                    static fn (
                        EmailMessageStatus $status
                    ): string => $status->value,
                    EmailMessageStatus::cases()
                )
            ),

            'failed_outgoing' => (clone $query)
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Failed->value
                )
                ->count(),

            'stuck' => $this->stuckMessageCounts(
                clone $query
            ),
        ];
    }

    private function attachmentSummary(): array
    {
        $query = EmailAttachment::query();

        return [
            'total' => (clone $query)->count(),

            'by_scan_status' => $this->groupedCounts(
                clone $query,
                'scan_status',
                array_map(
                    static fn (
                        EmailAttachmentScanStatus $status
                    ): string => $status->value,
                    EmailAttachmentScanStatus::cases()
                )
            ),

            'stale_pending' => (clone $query)
                ->where(
                    'scan_status',
                    EmailAttachmentScanStatus::Pending
                        ->value
                )
                ->where(
                    'updated_at',
                    '<=',
                    $this
                        ->thresholds
                        ->attachmentPendingCutoff()
                )
                ->count(),

            'rejected' => EmailAttachmentRejection::query()->count(),

            'antivirus_enabled' => (bool) config(
                'simpledesk-mail-antivirus.enabled',
                false
            ),
        ];
    }

    private function syncSummary(): array
    {
        $incomingChannels =
            MailboxChannel::query()
                ->where(
                    'direction',
                    MailboxChannelDirection::Incoming
                        ->value
                )
                ->where('is_enabled', true);

        $neverSynced =
            MailboxChannel::query()
                ->where(
                    'direction',
                    MailboxChannelDirection::Incoming
                        ->value
                )
                ->where('is_enabled', true)
                ->whereDoesntHave(
                    'syncState',
                    function (
                        Builder $query
                    ): void {
                        $query->whereNotNull(
                            'last_sync_completed_at'
                        );
                    }
                )
                ->count();

        $failed =
            MailboxChannelSyncState::query()
                ->whereHas(
                    'mailboxChannel',
                    function (
                        Builder $query
                    ): void {
                        $query
                            ->where(
                                'direction',
                                MailboxChannelDirection::Incoming
                                    ->value
                            )
                            ->where(
                                'is_enabled',
                                true
                            );
                    }
                )
                ->whereNotNull(
                    'last_sync_failed_at'
                )
                ->where(function (
                    Builder $query
                ): void {
                    $query
                        ->whereNull(
                            'last_sync_completed_at'
                        )
                        ->orWhereColumn(
                            'last_sync_failed_at',
                            '>',
                            'last_sync_completed_at'
                        );
                })
                ->count();

        $stale =
            MailboxChannel::query()
                ->where(
                    'direction',
                    MailboxChannelDirection::Incoming
                        ->value
                )
                ->where('is_enabled', true)
                ->where(function (
                    Builder $query
                ): void {
                    $query
                        ->whereDoesntHave('syncState')
                        ->orWhereHas(
                            'syncState',
                            function (
                                Builder $query
                            ): void {
                                $query
                                    ->whereNull(
                                        'last_sync_completed_at'
                                    )
                                    ->orWhere(
                                        'last_sync_completed_at',
                                        '<=',
                                        $this
                                            ->thresholds
                                            ->syncCutoff()
                                    );
                            }
                        );
                })
                ->count();

        $lastCompletedAt =
            MailboxChannelSyncState::query()
                ->whereHas(
                    'mailboxChannel',
                    function (
                        Builder $query
                    ): void {
                        $query
                            ->where(
                                'direction',
                                MailboxChannelDirection::Incoming
                                    ->value
                            )
                            ->where(
                                'is_enabled',
                                true
                            );
                    }
                )
                ->max('last_sync_completed_at');

        return [
            'enabled_incoming_channels' => (clone $incomingChannels)->count(),

            'never_synced' => $neverSynced,

            'failed' => $failed,

            'stale' => $stale,

            'last_completed_at' => $lastCompletedAt === null
                    ? null
                    : Carbon::parse(
                        $lastCompletedAt
                    )->toIso8601String(),
        ];
    }

    private function quarantineSummary(): array
    {
        return [
            'total' => EmailMessageQuarantine::query()
                ->count(),

            'open' => EmailMessageQuarantine::query()
                ->whereNull('resolved_at')
                ->count(),

            'released_for_retry' => EmailMessageQuarantine::query()
                ->whereNotNull('released_at')
                ->whereNull('resolved_at')
                ->count(),

            'resolved' => EmailMessageQuarantine::query()
                ->whereNotNull('resolved_at')
                ->count(),
        ];
    }

    private function stuckMessageCounts(
        Builder $baseQuery
    ): array {
        return [
            'total' => $this
                ->thresholds
                ->applyStuckMessageConstraint(
                    clone $baseQuery
                )
                ->count(),

            'preparing' => (clone $baseQuery)
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Preparing->value
                )
                ->where(
                    'created_at',
                    '<=',
                    $this
                        ->thresholds
                        ->preparingCutoff()
                )
                ->count(),

            'queued' => (clone $baseQuery)
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Queued->value
                )
                ->where(function (
                    Builder $query
                ): void {
                    $query
                        ->where(function (
                            Builder $query
                        ): void {
                            $query
                                ->whereNotNull(
                                    'queued_at'
                                )
                                ->where(
                                    'queued_at',
                                    '<=',
                                    $this
                                        ->thresholds
                                        ->queuedCutoff()
                                );
                        })
                        ->orWhere(function (
                            Builder $query
                        ): void {
                            $query
                                ->whereNull(
                                    'queued_at'
                                )
                                ->where(
                                    'created_at',
                                    '<=',
                                    $this
                                        ->thresholds
                                        ->queuedCutoff()
                                );
                        });
                })
                ->count(),

            'processing' => (clone $baseQuery)
                ->where(
                    'direction',
                    EmailMessageDirection::Incoming->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Processing->value
                )
                ->whereNotNull(
                    'processing_started_at'
                )
                ->where(
                    'processing_started_at',
                    '<=',
                    $this
                        ->thresholds
                        ->processingCutoff()
                )
                ->count(),

            'sending' => (clone $baseQuery)
                ->where(
                    'direction',
                    EmailMessageDirection::Outgoing->value
                )
                ->where(
                    'status',
                    EmailMessageStatus::Sending->value
                )
                ->whereNotNull(
                    'processing_started_at'
                )
                ->where(
                    'processing_started_at',
                    '<=',
                    $this
                        ->thresholds
                        ->sendingCutoff()
                )
                ->count(),
        ];
    }

    private function recentMessages(
        int $mailboxId
    ): array {
        $limit = max(
            1,
            min(
                50,
                (int) config(
                    'simpledesk-mail-diagnostics.recent_messages_limit',
                    10
                )
            )
        );

        return EmailMessage::query()
            ->where(
                'mailbox_id',
                $mailboxId
            )
            ->with(
                'mailboxChannel:id,name'
            )
            ->withCount([
                'attachments',
                'attachmentRejections',
            ])
            ->latest('id')
            ->limit($limit)
            ->get()
            ->map(
                fn (
                    EmailMessage $message
                ): array => [
                    'id' => $message->id,

                    'channel' => $message->mailboxChannel === null
                            ? null
                            : [
                                'id' => $message
                                    ->mailboxChannel
                                    ->id,

                                'name' => $message
                                    ->mailboxChannel
                                    ->name,
                            ],

                    'direction' => $message->direction->value,

                    'status' => $message->status->value,

                    'subject' => $message->subject,

                    'sender_address' => $message->sender_address,

                    'failure_code' => $message->failure_code,

                    'failure_message' => $message->failure_message === null
                            ? null
                            : $this
                                ->redactor
                                ->redactString(
                                    $message
                                        ->failure_message
                                ),

                    'attachments_count' => $message->attachments_count,

                    'attachment_rejections_count' => $message
                        ->attachment_rejections_count,

                    'created_at' => $message
                        ->created_at
                        ?->toIso8601String(),
                ]
            )
            ->all();
    }

    private function channelDetails(
        MailboxChannel $channel
    ): array {
        $syncState = $channel->syncState;

        return [
            'id' => $channel->id,

            'name' => $channel->name,

            'direction' => $channel->direction->value,

            'driver' => $channel->driver->value,

            'is_enabled' => $channel->is_enabled,

            'is_primary' => $channel->is_primary,

            'failover_order' => $channel->failover_order,

            'health_status' => $channel->health_status->value,

            'last_checked_at' => $channel
                ->last_checked_at
                ?->toIso8601String(),

            'last_success_at' => $channel
                ->last_success_at
                ?->toIso8601String(),

            'last_activity_at' => $channel
                ->last_activity_at
                ?->toIso8601String(),

            'last_error_at' => $channel
                ->last_error_at
                ?->toIso8601String(),

            'last_error_code' => $channel->last_error_code,

            'last_error_message' => $channel->last_error_message === null
                    ? null
                    : $this
                        ->redactor
                        ->redactString(
                            $channel
                                ->last_error_message
                        ),

            'sync_state' => $syncState === null
                    ? null
                    : [
                        'last_sync_started_at' => $syncState
                            ->last_sync_started_at
                            ?->toIso8601String(),

                        'last_sync_completed_at' => $syncState
                            ->last_sync_completed_at
                            ?->toIso8601String(),

                        'last_sync_failed_at' => $syncState
                            ->last_sync_failed_at
                            ?->toIso8601String(),

                        'consecutive_failures' => $syncState
                            ->consecutive_failures,

                        'last_fetched_count' => $syncState
                            ->last_fetched_count,

                        'last_stored_count' => $syncState
                            ->last_stored_count,

                        'last_duplicate_count' => $syncState
                            ->last_duplicate_count,

                        'last_acknowledged_count' => $syncState
                            ->last_acknowledged_count,

                        'last_error_code' => $syncState
                            ->last_error_code,

                        'last_error_message' => $syncState
                            ->last_error_message
                            === null
                            ? null
                            : $this
                                ->redactor
                                ->redactString(
                                    $syncState
                                        ->last_error_message
                                ),
                    ],
        ];
    }

    private function recentErrors(
        ?int $mailboxId = null
    ): array {
        $limit = max(
            1,
            min(
                50,
                (int) config(
                    'simpledesk-mail-diagnostics.recent_errors_limit',
                    10
                )
            )
        );

        $channels = MailboxChannel::query()
            ->when(
                $mailboxId !== null,
                fn (
                    Builder $query
                ) => $query->where(
                    'mailbox_id',
                    $mailboxId
                )
            )
            ->whereNotNull('last_error_at')
            ->latest('last_error_at')
            ->limit($limit)
            ->get()
            ->map(
                fn (
                    MailboxChannel $channel
                ): array => [
                    'source' => 'channel',

                    'source_id' => $channel->id,

                    'mailbox_id' => $channel->mailbox_id,

                    'occurred_at' => $channel->last_error_at,

                    'code' => $channel->last_error_code,

                    'message' => $this
                        ->redactor
                        ->redactString(
                            (string) $channel
                                ->last_error_message
                        ),
                ]
            );

        $providerConnections =
            MailProviderConnection::query()
                ->when(
                    $mailboxId !== null,
                    fn (
                        Builder $query
                    ) => $query->whereHas(
                        'channels',
                        fn (
                            Builder $query
                        ) => $query->where(
                            'mailbox_id',
                            $mailboxId
                        )
                    )
                )
                ->whereNotNull('last_error_at')
                ->latest('last_error_at')
                ->limit($limit)
                ->get()
                ->map(
                    fn (
                        MailProviderConnection $connection
                    ): array => [
                        'source' => 'provider_connection',

                        'source_id' => $connection->id,

                        'mailbox_id' => null,

                        'occurred_at' => $connection
                            ->last_error_at,

                        'code' => $connection
                            ->last_error_code,

                        'message' => $this
                            ->redactor
                            ->redactString(
                                (string) $connection
                                    ->last_error_message
                            ),
                    ]
                );

        $messages = EmailMessage::query()
            ->when(
                $mailboxId !== null,
                fn (
                    Builder $query
                ) => $query->where(
                    'mailbox_id',
                    $mailboxId
                )
            )
            ->whereNotNull('failed_at')
            ->latest('failed_at')
            ->limit($limit)
            ->get()
            ->map(
                fn (
                    EmailMessage $message
                ): array => [
                    'source' => 'message',

                    'source_id' => $message->id,

                    'mailbox_id' => $message->mailbox_id,

                    'occurred_at' => $message->failed_at,

                    'code' => $message->failure_code,

                    'message' => $this
                        ->redactor
                        ->redactString(
                            (string) $message
                                ->failure_message
                        ),
                ]
            );

        $syncStates =
            MailboxChannelSyncState::query()
                ->with(
                    'mailboxChannel:id,mailbox_id'
                )
                ->when(
                    $mailboxId !== null,
                    fn (
                        Builder $query
                    ) => $query->whereHas(
                        'mailboxChannel',
                        fn (
                            Builder $query
                        ) => $query->where(
                            'mailbox_id',
                            $mailboxId
                        )
                    )
                )
                ->whereNotNull(
                    'last_sync_failed_at'
                )
                ->latest(
                    'last_sync_failed_at'
                )
                ->limit($limit)
                ->get()
                ->map(
                    fn (
                        MailboxChannelSyncState $state
                    ): array => [
                        'source' => 'sync',

                        'source_id' => $state
                            ->mailbox_channel_id,

                        'mailbox_id' => $state
                            ->mailboxChannel
                            ?->mailbox_id,

                        'occurred_at' => $state
                            ->last_sync_failed_at,

                        'code' => $state
                            ->last_error_code,

                        'message' => $this
                            ->redactor
                            ->redactString(
                                (string) $state
                                    ->last_error_message
                            ),
                    ]
                );

        $quarantines =
            EmailMessageQuarantine::query()
                ->when(
                    $mailboxId !== null,
                    fn (
                        Builder $query
                    ) => $query->where(
                        'mailbox_id',
                        $mailboxId
                    )
                )
                ->latest(
                    'last_quarantined_at'
                )
                ->limit($limit)
                ->get()
                ->map(
                    fn (
                        EmailMessageQuarantine $quarantine
                    ): array => [
                        'source' => 'quarantine',

                        'source_id' => $quarantine->id,

                        'mailbox_id' => $quarantine
                            ->mailbox_id,

                        'occurred_at' => $quarantine
                            ->last_quarantined_at,

                        'code' => $quarantine
                            ->reason_code,

                        'message' => $this
                            ->redactor
                            ->redactString(
                                (string) $quarantine
                                    ->reason_message
                            ),
                    ]
                );

        return Collection::make()
            ->concat($channels)
            ->concat($providerConnections)
            ->concat($messages)
            ->concat($syncStates)
            ->concat($quarantines)
            ->sortByDesc(
                fn (
                    array $item
                ) => $item['occurred_at']
                    ?->getTimestamp() ?? 0
            )
            ->take($limit)
            ->map(function (
                array $item
            ): array {
                $item['occurred_at'] =
                    $item['occurred_at']
                        ?->toIso8601String();

                return $item;
            })
            ->values()
            ->all();
    }

    private function groupedCounts(
        Builder $query,
        string $column,
        array $knownValues = [],
    ): array {
        $counts = $query
            ->selectRaw(
                "{$column}, COUNT(*) as aggregate"
            )
            ->groupBy($column)
            ->pluck(
                'aggregate',
                $column
            )
            ->map(
                static fn (
                    mixed $count
                ): int => (int) $count
            )
            ->all();

        foreach (
            $knownValues as $value
        ) {
            $counts[$value] ??= 0;
        }

        ksort($counts);

        return $counts;
    }
}
