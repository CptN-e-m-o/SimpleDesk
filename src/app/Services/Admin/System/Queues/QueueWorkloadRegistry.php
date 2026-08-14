<?php

namespace App\Services\Admin\System\Queues;

use App\Data\Admin\System\Queues\QueueWorkloadDefinitionData;

class QueueWorkloadRegistry
{
    /**
     * Describe logical producers which currently dispatch queued
     * work in SimpleDesk.
     *
     * Different logical producers may resolve to the same physical
     * connection + queue pair. QueueBacklogService is responsible
     * for deduplicating those physical pairs.
     *
     * @return array<int, QueueWorkloadDefinitionData>
     */
    public function definitions(): array
    {
        return [
            $this->make(
                key: 'default',
                label: 'Default',
                description: 'General application jobs.',
                queue: 'default',
                connection: null,
            ),

            $this->make(
                key: 'mail_incoming',
                label: 'Incoming mail',
                description:
                'Incoming mailbox synchronization dispatched through the standard Mail queue.',
                queue: $this->queueName(
                    'simpledesk-mail.queues.incoming',
                    'mail-incoming',
                ),
                connection: null,
            ),

            $this->make(
                key: 'mail_sync',
                label: 'Mailbox synchronization',
                description:
                'Scheduled and manual mailbox synchronization.',
                queue: $this->queueName(
                    'simpledesk-mail-automation.sync.queue',
                    'mail-incoming',
                ),
                connection: $this->optional(
                    'simpledesk-mail-automation.sync.queue_connection',
                ),
                enabled:
                (bool) config(
                    'simpledesk-mail-automation.enabled',
                    true,
                )
                && (bool) config(
                    'simpledesk-mail-automation.sync.enabled',
                    true,
                ),
            ),

            $this->make(
                key: 'mail_ticketing_incoming',
                label: 'Incoming ticket processing',
                description:
                'Processes stored incoming email into tickets and replies.',
                queue: $this->queueName(
                    'simpledesk-mail-ticketing.queue',
                    'mail-incoming',
                ),
                connection: $this->optional(
                    'simpledesk-mail-ticketing.queue_connection',
                ),
                enabled: (bool) config(
                    'simpledesk-mail-ticketing.enabled',
                    true,
                ),
            ),

            $this->make(
                key: 'mail_outgoing',
                label: 'Outgoing mail',
                description:
                'Standard outbound email delivery.',
                queue: $this->queueName(
                    'simpledesk-mail.queues.outgoing',
                    'mail-outgoing',
                ),
                connection: null,
            ),

            $this->make(
                key: 'mail_ticket_reply',
                label: 'Ticket reply delivery',
                description:
                'Outbound email jobs dispatched directly by ticket reply handling.',
                queue: $this->queueName(
                    'simpledesk-mail-ticketing.outgoing_replies.queue',
                    'mail-outgoing',
                ),
                connection: $this->optional(
                    'simpledesk-mail-ticketing.outgoing_replies.queue_connection',
                ),
                enabled:
                (bool) config(
                    'simpledesk-mail-ticketing.enabled',
                    true,
                )
                && (bool) config(
                    'simpledesk-mail-ticketing.outgoing_replies.enabled',
                    true,
                ),
            ),

            $this->make(
                key: 'mail_outgoing_retry',
                label: 'Outgoing mail retry',
                description:
                'Retry delivery for previously failed outgoing email.',
                queue: $this->queueName(
                    'simpledesk-mail.queues.outgoing',
                    'mail-outgoing',
                ),
                connection: $this->optional(
                    'simpledesk-mail-ticketing.outgoing_replies.queue_connection',
                ),
            ),

            $this->make(
                key: 'mail_recovery_incoming',
                label: 'Incoming mail recovery',
                description:
                'Recovery processing for stuck or unprocessed incoming email.',
                queue: $this->queueName(
                    'simpledesk-mail-automation.recovery.incoming_queue',
                    'mail-incoming',
                ),
                connection: $this->optional(
                    'simpledesk-mail-automation.recovery.queue_connection',
                ),
                enabled: (bool) config(
                    'simpledesk-mail-automation.enabled',
                    true,
                ),
            ),

            $this->make(
                key: 'mail_recovery_outgoing',
                label: 'Outgoing mail recovery',
                description:
                'Recovery processing for stuck or queued outgoing email.',
                queue: $this->queueName(
                    'simpledesk-mail-automation.recovery.outgoing_queue',
                    'mail-outgoing',
                ),
                connection: $this->optional(
                    'simpledesk-mail-automation.recovery.queue_connection',
                ),
                enabled: (bool) config(
                    'simpledesk-mail-automation.enabled',
                    true,
                ),
            ),

            $this->make(
                key: 'mail_quarantine_retry',
                label: 'Mail quarantine retry',
                description:
                'Retries quarantined inbound ticket-processing jobs.',
                queue: $this->queueName(
                    'simpledesk-mail-quarantine.queue',
                    'mail-incoming',
                ),
                connection: $this->optional(
                    'simpledesk-mail-quarantine.queue_connection',
                ),
                enabled: (bool) config(
                    'simpledesk-mail-quarantine.enabled',
                    true,
                ),
            ),

            $this->make(
                key: 'mail_antivirus',
                label: 'Mail antivirus',
                description:
                'Attachment antivirus scanning.',
                queue: $this->queueName(
                    'simpledesk-mail-antivirus.queue.name',
                    'mail-antivirus',
                ),
                connection: $this->optional(
                    'simpledesk-mail-antivirus.queue.connection',
                ),
                enabled: (bool) config(
                    'simpledesk-mail-antivirus.enabled',
                    false,
                ),
            ),
        ];
    }

    private function make(
        string $key,
        string $label,
        string $description,
        string $queue,
        ?string $connection,
        bool $enabled = true,
    ): QueueWorkloadDefinitionData {
        return new QueueWorkloadDefinitionData(
            key: $key,
            label: $label,
            description: $description,
            queueName: $queue,
            connectionName: $connection,
            usesDefaultConnection:
            $connection === null,
            enabled: $enabled,
        );
    }

    private function queueName(
        string $key,
        string $fallback,
    ): string {
        $value = trim(
            (string) config(
                $key,
                $fallback,
            ),
        );

        return $value !== ''
            ? $value
            : $fallback;
    }

    private function optional(
        string $key,
    ): ?string {
        $value = trim(
            (string) config(
                $key,
                '',
            ),
        );

        return $value === ''
            ? null
            : $value;
    }
}
