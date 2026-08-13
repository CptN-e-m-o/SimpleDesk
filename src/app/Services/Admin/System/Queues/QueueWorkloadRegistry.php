<?php

namespace App\Services\Admin\System\Queues;

use App\Data\Admin\System\Queues\QueueWorkloadDefinitionData;

class QueueWorkloadRegistry
{
    public function definitions(): array
    {
        return [$this->make('default', 'Default', 'General application jobs', 'default', null, true), $this->make('mail_incoming', 'Incoming mail', 'Mailbox synchronization', (string) config('simpledesk-mail.queues.incoming', 'mail-incoming'), $this->optional('simpledesk-mail-automation.sync.queue_connection')), $this->make('mail_outgoing', 'Outgoing mail', 'Outbound email delivery', (string) config('simpledesk-mail.queues.outgoing', 'mail-outgoing'), $this->optional('simpledesk-mail-ticketing.outgoing_replies.queue_connection')), $this->make('mail_antivirus', 'Mail antivirus', 'Attachment antivirus scans', (string) config('simpledesk-mail-antivirus.queue.name', 'mail-antivirus'), $this->optional('simpledesk-mail-antivirus.queue.connection'), (bool) config('simpledesk-mail-antivirus.enabled', false))];
    }

    private function make(string $key, string $label, string $description, string $queue, ?string $connection, bool $enabled = true): QueueWorkloadDefinitionData
    {
        return new QueueWorkloadDefinitionData($key, $label, $description, $queue, $connection, $connection === null, $enabled);
    }

    private function optional(string $key): ?string
    {
        $value = trim((string) config($key,''));

        return $value === '' ? null : $value;
    }
}
