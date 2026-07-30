<?php

namespace App\Services\Admin\Mail\Settings;

use App\Data\Admin\Mail\MailAdminActionResultData;
use App\Exceptions\Admin\Mail\MailAdminActionException;
use App\Jobs\Admin\Mail\SyncIncomingMailboxJob;
use App\Models\Admin\Mail\Mailbox;
use Throwable;

class ManualMailboxSyncService
{
    public function __construct(
        private readonly MailAdminActionLock $locks,
    ) {
    }

    public function dispatch(
        Mailbox $mailbox
    ): MailAdminActionResultData {
        if (!$mailbox->is_active) {
            throw new MailAdminActionException(
                message: "Mailbox [{$mailbox->id}] is disabled.",
                errorCode: 'mailbox_disabled',
                field: 'mailbox',
            );
        }

        $incomingChannelCount = $mailbox
            ->incomingChannels()
            ->where('is_enabled', true)
            ->count();

        if ($incomingChannelCount === 0) {
            throw new MailAdminActionException(
                message: "Mailbox [{$mailbox->id}] has no enabled incoming channels.",
                errorCode: 'mailbox_has_no_incoming_channels',
                field: 'mailbox',
            );
        }

        if (!$this->locks->acquire('sync-mailbox', $mailbox->id)) {
            return new MailAdminActionResultData(
                action: 'mailbox_sync',
                dispatched: false,
                message: 'Mailbox synchronization is already queued.',
                details: [
                    'mailbox_id' => $mailbox->id,
                    'incoming_channels' => $incomingChannelCount,
                ],
            );
        }

        try {
            $pendingDispatch = SyncIncomingMailboxJob::dispatch(
                $mailbox->id
            );

            $connection = config(
                'simpledesk-mail-automation.sync.queue_connection'
            );

            if (
                is_string($connection)
                && trim($connection) !== ''
            ) {
                $pendingDispatch->onConnection(
                    trim($connection)
                );
            }

            $queue = trim((string) config(
                'simpledesk-mail-automation.sync.queue',
                'mail-incoming',
            ));

            if ($queue !== '') {
                $pendingDispatch->onQueue($queue);
            }

            $pendingDispatch->afterCommit();
        } catch (Throwable $exception) {
            $this->locks->release(
                'sync-mailbox',
                $mailbox->id
            );

            throw $exception;
        }

        return new MailAdminActionResultData(
            action: 'mailbox_sync',
            dispatched: true,
            message: 'Mailbox synchronization was queued.',
            details: [
                'mailbox_id' => $mailbox->id,
                'incoming_channels' => $incomingChannelCount,
            ],
        );
    }
}
