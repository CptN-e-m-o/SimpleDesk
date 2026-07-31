<?php

namespace Tests\Feature\Admin\Mail\Settings;

use App\Enums\Admin\Mail\EmailAttachmentScanStatus;
use App\Enums\Admin\Mail\EmailMessageStatus;
use App\Enums\Admin\Mail\EmailQuarantineResolution;
use App\Jobs\Admin\Mail\ProcessInboundEmailJob;
use App\Jobs\Admin\Mail\SendOutgoingEmailJob;
use App\Jobs\Admin\Mail\SyncIncomingMailboxJob;
use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Tests\Fakes\Admin\Mail\FakeScanEmailAttachmentJob;
use Tests\TestCase;

class AdminMailOperationsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Queue::fake();
        Storage::fake('local');

        config()->set(
            'simpledesk-mail-admin.actions.dispatch_lock_seconds',
            300
        );

        config()->set(
            'simpledesk-mail-admin.attachment_rescan.job',
            FakeScanEmailAttachmentJob::class
        );

        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );

        config()->set(
            'simpledesk-mail-antivirus.queue',
            'mail-antivirus'
        );

        config()->set(
            'simpledesk-mail-antivirus.queue_connection',
            null
        );

        config()->set(
            'simpledesk-mail.queues.outgoing',
            'mail-outgoing'
        );

        config()->set(
            'simpledesk-mail-automation.sync.queue',
            'mail-incoming'
        );

        config()->set(
            'simpledesk-mail-automation.sync.queue_connection',
            null
        );

        config()->set(
            'simpledesk-mail-quarantine.queue',
            'mail-incoming'
        );

        config()->set(
            'simpledesk-mail-quarantine.queue_connection',
            null
        );
    }

    public function test_operation_routes_require_permissions(): void
    {
        $user = User::factory()->create();

        $mailbox = $this->createMailbox();
        $message = $this->createOutgoingMessage($mailbox);
        $attachment = $this->createAttachment($message);
        $quarantine = $this->createQuarantine();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'admin.email.mailboxes.sync',
                    $mailbox
                )
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'admin.email.messages.retry',
                    $message
                )
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'admin.email.attachments.rescan',
                    $attachment
                )
            )
            ->assertForbidden();

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'admin.email.quarantines.retry',
                    $quarantine
                )
            )
            ->assertForbidden();
    }

    public function test_mailbox_sync_is_queued_only_once(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.sync_mailboxes',
        ]);

        $mailbox = $this->createMailbox();

        $this->createIncomingChannel(
            $mailbox
        );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.mailboxes.sync',
                    $mailbox
                )
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                true
            )
            ->assertJsonPath(
                'data.details.mailbox_id',
                $mailbox->id
            );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.mailboxes.sync',
                    $mailbox
                )
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                false
            );

        Queue::assertPushed(
            SyncIncomingMailboxJob::class,
            fn (
                SyncIncomingMailboxJob $job
            ): bool => $job->mailboxId === $mailbox->id
                && $job->queue === 'mail-incoming'
                && $job->afterCommit === true
        );

        Queue::assertPushedTimes(
            SyncIncomingMailboxJob::class,
            1
        );
    }

    public function test_failed_outgoing_message_can_be_retried_only_once(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.retry_messages',
        ]);

        $mailbox = $this->createMailbox();

        $message = $this->createOutgoingMessage(
            $mailbox
        );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.messages.retry',
                    $message
                )
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                true
            );

        $message->refresh();

        $this->assertSame(
            EmailMessageStatus::Queued,
            $message->status
        );

        $this->assertNull(
            $message->failed_at
        );

        $this->assertNull(
            $message->failure_code
        );

        $this->assertNull(
            $message->failure_message
        );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.messages.retry',
                    $message
                )
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                false
            );

        Queue::assertPushed(
            SendOutgoingEmailJob::class,
            fn (
                SendOutgoingEmailJob $job
            ): bool => $job->emailMessageId === $message->id
                && $job->queue === 'mail-outgoing'
                && $job->afterCommit === true
        );

        Queue::assertPushedTimes(
            SendOutgoingEmailJob::class,
            1
        );
    }

    public function test_sent_outgoing_message_cannot_be_retried(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.retry_messages',
        ]);

        $mailbox = $this->createMailbox();

        $message = $this->createOutgoingMessage(
            $mailbox,
            [
                'status' => 'sent',
                'sent_at' => now(),
                'failed_at' => null,
                'failure_code' => null,
                'failure_message' => null,
            ]
        );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.messages.retry',
                    $message
                )
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error_code',
                'email_message_status_not_retryable'
            )
            ->assertJsonValidationErrors(
                'message'
            );

        Queue::assertNotPushed(
            SendOutgoingEmailJob::class
        );
    }

    public function test_attachment_rescan_is_queued_only_once(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.rescan_attachments',
        ]);

        $mailbox = $this->createMailbox();

        $message = $this->createOutgoingMessage(
            $mailbox,
            [
                'status' => 'rejected',
                'failure_code' => 'attachment_infected',
                'failure_message' => 'Attachment was infected.',
            ]
        );

        $attachment = $this->createAttachment(
            $message,
            [
                'scan_status' => 'infected',
                'scanned_at' => now(),
                'quarantined_at' => now(),
                'scan_result' => [
                    'signature' => 'Test.Signature',
                ],
            ]
        );

        Storage::disk('local')->put(
            $attachment->path,
            'attachment-content'
        );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.attachments.rescan',
                    $attachment
                )
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                true
            )
            ->assertJsonPath(
                'data.details.scan_status',
                'pending'
            );

        $attachment->refresh();
        $message->refresh();

        $this->assertSame(
            EmailAttachmentScanStatus::Pending,
            $attachment->scan_status
        );

        $this->assertNull(
            $attachment->scanned_at
        );

        $this->assertNull(
            $attachment->scan_result
        );

        $this->assertNotNull(
            $attachment->quarantined_at
        );

        $this->assertSame(
            EmailMessageStatus::Preparing,
            $message->status
        );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.attachments.rescan',
                    $attachment
                )
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                false
            );

        Queue::assertPushed(
            FakeScanEmailAttachmentJob::class,
            fn (
                FakeScanEmailAttachmentJob $job
            ): bool => $job->emailAttachmentId === $attachment->id
                && $job->queue === 'mail-antivirus'
                && $job->afterCommit === true
        );

        Queue::assertPushedTimes(
            FakeScanEmailAttachmentJob::class,
            1
        );
    }

    public function test_attachment_with_missing_file_cannot_be_rescanned(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.rescan_attachments',
        ]);

        $mailbox = $this->createMailbox();

        $message = $this->createOutgoingMessage(
            $mailbox
        );

        $attachment = $this->createAttachment(
            $message
        );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.attachments.rescan',
                    $attachment
                )
            )
            ->assertUnprocessable()
            ->assertJsonPath(
                'error_code',
                'attachment_file_missing'
            )
            ->assertJsonValidationErrors(
                'attachment'
            );

        Queue::assertNotPushed(
            FakeScanEmailAttachmentJob::class
        );
    }

    public function test_quarantined_email_can_be_retried_only_once(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.manage_quarantine',
        ]);

        $quarantine = $this->createQuarantine();

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.quarantines.retry',
                    $quarantine
                )
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                true
            )
            ->assertJsonPath(
                'data.details.resolution',
                'retried'
            );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.quarantines.retry',
                    $quarantine
                )
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                false
            );

        $quarantine->refresh();
        $quarantine->emailMessage->refresh();

        $this->assertSame(
            EmailQuarantineResolution::Retried,
            $quarantine->resolution
        );

        $this->assertSame(
            $admin->id,
            $quarantine->released_by_id
        );

        $this->assertNull(
            $quarantine->resolved_at
        );

        $this->assertSame(
            EmailMessageStatus::Received,
            $quarantine->emailMessage->status
        );

        Queue::assertPushed(
            ProcessInboundEmailJob::class,
            fn (
                ProcessInboundEmailJob $job
            ): bool => $job->emailMessageId
                === $quarantine->email_message_id
                && $job->queue === 'mail-incoming'
                && $job->afterCommit === true
        );

        Queue::assertPushedTimes(
            ProcessInboundEmailJob::class,
            1
        );
    }

    public function test_quarantined_email_can_be_ignored(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.manage_quarantine',
        ]);

        $quarantine = $this->createQuarantine();

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.quarantines.ignore',
                    $quarantine
                ),
                [
                    'reason' => 'The message was reviewed manually.',
                ]
            )
            ->assertAccepted()
            ->assertJsonPath(
                'data.dispatched',
                false
            )
            ->assertJsonPath(
                'data.details.resolution',
                'ignored'
            );

        $quarantine->refresh();
        $quarantine->emailMessage->refresh();

        $this->assertSame(
            EmailQuarantineResolution::Ignored,
            $quarantine->resolution
        );

        $this->assertSame(
            $admin->id,
            $quarantine->released_by_id
        );

        $this->assertNotNull(
            $quarantine->resolved_at
        );

        $this->assertSame(
            EmailMessageStatus::Processed,
            $quarantine->emailMessage->status
        );

        Queue::assertNotPushed(
            ProcessInboundEmailJob::class
        );
    }

    private function createAgentWithPermissions(
        array $permissionKeys
    ): User {
        $user = User::factory()->create();

        $role = Role::query()->create([
            'name' => 'mail-operations-admin-'.$user->id,
            'label' => 'Mail operations administrator',
            'description' => null,
            'type' => 'agent',
            'is_system' => false,
            'is_default' => false,
        ]);

        $group = PermissionGroup::query()->create([
            'key' => 'mail-operations-test-'.$user->id,
            'label' => 'Mail operations test',
            'panel' => 'admin',
            'type' => 'agent',
            'sort_order' => 1,
        ]);

        $permissionIds = collect($permissionKeys)
            ->map(
                fn (string $key): int => Permission::query()->create([
                    'permission_group_id' => $group->id,
                    'parent_id' => null,
                    'key' => $key,
                    'label' => $key,
                    'type' => 'agent',
                    'ui_type' => 'checkbox',
                    'description' => null,
                    'sort_order' => 1,
                ])->id
            )
            ->all();

        $role->permissions()->sync(
            $permissionIds
        );

        $user->roles()->attach(
            $role
        );

        return $user;
    }

    private function createMailbox(
        array $overrides = []
    ): Mailbox {
        return Mailbox::query()->create(
            array_merge(
                [
                    'name' => 'Support',
                    'email_address' => 'support-'
                        .uniqid()
                        .'@example.test',
                    'display_name' => 'SimpleDesk Support',
                    'department_id' => null,
                    'is_active' => true,
                    'is_default_outgoing' => false,
                    'internal_notes' => null,
                ],
                $overrides
            )
        );
    }

    private function createIncomingChannel(
        Mailbox $mailbox
    ): MailboxChannel {
        return MailboxChannel::query()->create([
            'mailbox_id' => $mailbox->id,
            'provider_connection_id' => null,
            'name' => 'IMAP',
            'direction' => 'incoming',
            'driver' => 'imap',
            'auth_type' => 'none',
            'is_enabled' => true,
            'is_primary' => true,
            'failover_order' => 100,
            'configuration' => [],
            'secret_configuration' => [],
            'health_status' => 'unknown',
        ]);
    }

    private function createOutgoingMessage(
        Mailbox $mailbox,
        array $overrides = [],
    ): EmailMessage {
        return EmailMessage::query()->create(
            array_merge(
                [
                    'mailbox_id' => $mailbox->id,
                    'mailbox_channel_id' => null,
                    'ticket_id' => null,
                    'ticket_reply_id' => null,
                    'direction' => 'outgoing',
                    'driver' => null,
                    'status' => 'failed',
                    'idempotency_key' => 'admin-operation-outgoing-'
                        .uniqid(),
                    'sender_address' => $mailbox->email_address,
                    'to_recipients' => [
                        [
                            'address' => 'customer@example.test',
                            'name' => 'Customer',
                        ],
                    ],
                    'subject' => 'Test message',
                    'text_body' => 'Test body',
                    'metadata' => [],
                    'failed_at' => now(),
                    'failure_code' => 'all_channels_failed',
                    'failure_message' => 'All channels failed.',
                ],
                $overrides
            )
        );
    }

    private function createAttachment(
        EmailMessage $message,
        array $overrides = [],
    ): EmailAttachment {
        $content = 'attachment-content';

        return EmailAttachment::query()->create(
            array_merge(
                [
                    'email_message_id' => $message->id,
                    'position' => 0,
                    'external_id' => null,
                    'deduplication_key' => hash(
                        'sha256',
                        uniqid(
                            'attachment-',
                            true
                        )
                    ),
                    'file_name' => 'document.txt',
                    'mime_type' => 'text/plain',
                    'size' => strlen($content),
                    'disk' => 'local',
                    'path' => 'mail/attachments/'
                        .uniqid()
                        .'/document.txt',
                    'checksum_sha256' => hash(
                        'sha256',
                        $content
                    ),
                    'content_id' => null,
                    'is_inline' => false,
                    'scan_status' => 'failed',
                    'scanned_at' => now(),
                    'quarantined_at' => null,
                    'scan_result' => [
                        'error_code' => 'scan_failed',
                    ],
                    'metadata' => [],
                ],
                $overrides
            )
        );
    }

    private function createQuarantine(): EmailMessageQuarantine
    {
        $mailbox = $this->createMailbox();

        $channel = $this->createIncomingChannel(
            $mailbox
        );

        $message = EmailMessage::query()->create([
            'mailbox_id' => $mailbox->id,
            'mailbox_channel_id' => $channel->id,
            'ticket_id' => null,
            'ticket_reply_id' => null,
            'direction' => 'incoming',
            'driver' => 'imap',
            'status' => 'failed',
            'idempotency_key' => 'admin-operation-incoming-'
                .uniqid(),
            'sender_address' => 'customer@example.test',
            'to_recipients' => [
                [
                    'address' => $mailbox->email_address,
                    'name' => null,
                ],
            ],
            'subject' => 'Incoming message',
            'text_body' => 'Incoming body',
            'metadata' => [],
            'received_at' => now(),
            'failed_at' => now(),
            'failure_code' => 'inbound_ticket_processing_quarantined',
            'failure_message' => 'Ticketing failed.',
        ]);

        return EmailMessageQuarantine::query()->create([
            'email_message_id' => $message->id,
            'mailbox_id' => $mailbox->id,
            'mailbox_channel_id' => $channel->id,
            'stage' => 'inbound_ticketing',
            'reason_code' => 'ticketing_failed',
            'reason_message' => 'Ticketing failed.',
            'exception_class' => null,
            'attempts' => 1,
            'first_quarantined_at' => now(),
            'last_quarantined_at' => now(),
            'released_at' => null,
            'released_by_id' => null,
            'resolved_at' => null,
            'resolution' => null,
            'metadata' => [],
        ]);
    }
}
