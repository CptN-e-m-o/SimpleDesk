<?php

namespace Tests\Unit\Admin\System\Queues;

use App\Services\Admin\System\Queues\QueueWorkloadRegistry;
use Tests\TestCase;

class QueueWorkloadRegistryTest extends TestCase
{
    public function test_registry_observes_mail_queue_names_and_overrides(): void
    {
        config(['simpledesk-mail.queues.incoming' => 'custom-in', 'simpledesk-mail-antivirus.queue.name' => 'custom-av', 'simpledesk-mail-antivirus.queue.connection' => 'special']);
        $items = collect(app(QueueWorkloadRegistry::class)->definitions())->keyBy('key');
        $this->assertSame('custom-in', $items['mail_incoming']->queueName);
        $this->assertSame('custom-av', $items['mail_antivirus']->queueName);
        $this->assertSame('special', $items['mail_antivirus']->connectionName);
        $this->assertFalse($items['mail_antivirus']->usesDefaultConnection);
    }
}
