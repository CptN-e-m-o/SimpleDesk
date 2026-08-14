<?php

namespace Tests\Unit\Admin\System\Queues;

use App\Services\Admin\System\Queues\QueueWorkloadRegistry;
use Tests\TestCase;

class QueueWorkloadRegistryTest extends TestCase
{
    public function test_registry_observes_actual_mail_dispatch_configuration_pairs(): void
    {
        config()->set(
            'simpledesk-mail.queues.incoming',
            'cli-incoming',
        );

        config()->set(
            'simpledesk-mail-automation.sync.queue',
            'scheduled-sync',
        );

        config()->set(
            'simpledesk-mail-automation.sync.queue_connection',
            'sync-connection',
        );

        config()->set(
            'simpledesk-mail-ticketing.queue',
            'ticket-processing',
        );

        config()->set(
            'simpledesk-mail-ticketing.queue_connection',
            'ticket-connection',
        );

        config()->set(
            'simpledesk-mail.queues.outgoing',
            'standard-outgoing',
        );

        config()->set(
            'simpledesk-mail-ticketing.outgoing_replies.queue',
            'ticket-outgoing',
        );

        config()->set(
            'simpledesk-mail-ticketing.outgoing_replies.queue_connection',
            'ticket-outgoing-connection',
        );

        config()->set(
            'simpledesk-mail-automation.recovery.incoming_queue',
            'recovery-in',
        );

        config()->set(
            'simpledesk-mail-automation.recovery.outgoing_queue',
            'recovery-out',
        );

        config()->set(
            'simpledesk-mail-automation.recovery.queue_connection',
            'recovery-connection',
        );

        config()->set(
            'simpledesk-mail-quarantine.queue',
            'quarantine-retry',
        );

        config()->set(
            'simpledesk-mail-quarantine.queue_connection',
            'quarantine-connection',
        );

        config()->set(
            'simpledesk-mail-antivirus.queue.name',
            'custom-antivirus',
        );

        config()->set(
            'simpledesk-mail-antivirus.queue.connection',
            'antivirus-connection',
        );

        $items =
            collect(
                app(
                    QueueWorkloadRegistry::class,
                )->definitions(),
            )
                ->keyBy('key');

        $this->assertSame(
            'cli-incoming',
            $items[
            'mail_incoming'
            ]->queueName,
        );

        $this->assertNull(
            $items[
            'mail_incoming'
            ]->connectionName,
        );

        $this->assertSame(
            'scheduled-sync',
            $items[
            'mail_sync'
            ]->queueName,
        );

        $this->assertSame(
            'sync-connection',
            $items[
            'mail_sync'
            ]->connectionName,
        );

        $this->assertSame(
            'ticket-processing',
            $items[
            'mail_ticketing_incoming'
            ]->queueName,
        );

        $this->assertSame(
            'ticket-connection',
            $items[
            'mail_ticketing_incoming'
            ]->connectionName,
        );

        $this->assertSame(
            'standard-outgoing',
            $items[
            'mail_outgoing'
            ]->queueName,
        );

        $this->assertNull(
            $items[
            'mail_outgoing'
            ]->connectionName,
        );

        $this->assertSame(
            'ticket-outgoing',
            $items[
            'mail_ticket_reply'
            ]->queueName,
        );

        $this->assertSame(
            'ticket-outgoing-connection',
            $items[
            'mail_ticket_reply'
            ]->connectionName,
        );

        $this->assertSame(
            'standard-outgoing',
            $items[
            'mail_outgoing_retry'
            ]->queueName,
        );

        $this->assertSame(
            'ticket-outgoing-connection',
            $items[
            'mail_outgoing_retry'
            ]->connectionName,
        );

        $this->assertSame(
            'recovery-in',
            $items[
            'mail_recovery_incoming'
            ]->queueName,
        );

        $this->assertSame(
            'recovery-connection',
            $items[
            'mail_recovery_incoming'
            ]->connectionName,
        );

        $this->assertSame(
            'recovery-out',
            $items[
            'mail_recovery_outgoing'
            ]->queueName,
        );

        $this->assertSame(
            'quarantine-retry',
            $items[
            'mail_quarantine_retry'
            ]->queueName,
        );

        $this->assertSame(
            'quarantine-connection',
            $items[
            'mail_quarantine_retry'
            ]->connectionName,
        );

        $this->assertSame(
            'custom-antivirus',
            $items[
            'mail_antivirus'
            ]->queueName,
        );

        $this->assertSame(
            'antivirus-connection',
            $items[
            'mail_antivirus'
            ]->connectionName,
        );
    }

    public function test_default_connection_flag_reflects_explicit_overrides(): void
    {
        config()->set(
            'simpledesk-mail-automation.sync.queue_connection',
            null,
        );

        config()->set(
            'simpledesk-mail-antivirus.queue.connection',
            'special',
        );

        $items =
            collect(
                app(
                    QueueWorkloadRegistry::class,
                )->definitions(),
            )
                ->keyBy('key');

        $this->assertTrue(
            $items[
            'mail_sync'
            ]->usesDefaultConnection,
        );

        $this->assertFalse(
            $items[
            'mail_antivirus'
            ]->usesDefaultConnection,
        );
    }
}
