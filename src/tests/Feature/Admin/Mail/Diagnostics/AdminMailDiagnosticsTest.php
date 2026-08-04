<?php

namespace Tests\Feature\Admin\Mail\Diagnostics;

use App\Models\Admin\Mail\EmailAttachment;
use App\Models\Admin\Mail\EmailAttachmentRejection;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\EmailMessageQuarantine;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailboxChannelSyncState;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AdminMailDiagnosticsTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set(
            'simpledesk-mail-diagnostics.stale.preparing_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-diagnostics.stale.queued_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-diagnostics.stale.processing_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-ticketing.processing_lock_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-diagnostics.stale.sending_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-ticketing.outgoing_replies.job.lock_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-diagnostics.stale.attachment_pending_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-diagnostics.stale.sync_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-antivirus.enabled',
            true
        );
    }

    public function test_mailbox_diagnostics_returns_channels_sync_state_and_safe_recent_messages(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.view_diagnostics',
        ]);

        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            $mailbox
        );

        MailboxChannelSyncState::query()->create([
            'mailbox_channel_id' => $channel->id,
            'cursor' => 'private-cursor',
            'cursor_metadata' => [
                'internal' => 'not-for-api',
            ],
            'last_sync_started_at' => now()->subMinute(),
            'last_sync_completed_at' => now(),
            'last_sync_failed_at' => null,
            'consecutive_failures' => 0,
            'last_fetched_count' => 4,
            'last_stored_count' => 3,
            'last_duplicate_count' => 1,
            'last_acknowledged_count' => 4,
            'last_error_code' => null,
            'last_error_message' => null,
        ]);

        $message = $this->createMessage(
            $mailbox,
            [
                'mailbox_channel_id' => $channel->id,

                'subject' => 'Customer question',

                'text_body' => 'Private message body',

                'html_body' => '<p>Private HTML body</p>',

                'headers' => [
                    'authorization' => 'Bearer private-token',
                ],

                'metadata' => [
                    'password' => 'private-password',
                ],

                'raw_message_path' => 'mail/raw/private.eml',
            ]
        );

        $this->createAttachment(
            $message
        );

        $response = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.email.mailboxes.diagnostics',
                    $mailbox
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'data.mailbox.id',
                $mailbox->id
            )
            ->assertJsonPath(
                'data.channels.0.sync_state.last_fetched_count',
                4
            )
            ->assertJsonPath(
                'data.messages.recent.0.subject',
                'Customer question'
            )
            ->assertJsonPath(
                'data.messages.recent.0.attachments_count',
                1
            );

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            'private-cursor',
            $content
        );

        $this->assertStringNotContainsString(
            'not-for-api',
            $content
        );

        $this->assertStringNotContainsString(
            'Private message body',
            $content
        );

        $this->assertStringNotContainsString(
            'Private HTML body',
            $content
        );

        $this->assertStringNotContainsString(
            'private-token',
            $content
        );

        $this->assertStringNotContainsString(
            'private-password',
            $content
        );

        $this->assertStringNotContainsString(
            'mail/raw/private.eml',
            $content
        );
    }

    public function test_message_list_can_filter_stuck_messages_without_exposing_content(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.view_diagnostics',
        ]);

        $mailbox = $this->createMailbox();

        $stuck = $this->createMessage(
            $mailbox,
            [
                'direction' => 'outgoing',
                'status' => 'queued',
                'queued_at' => now()->subMinutes(2),
                'subject' => 'Stuck delivery',
                'text_body' => 'Secret body',
                'metadata' => [
                    'token' => 'secret-token',
                ],
            ]
        );

        $this->createMessage(
            $mailbox,
            [
                'direction' => 'outgoing',
                'status' => 'sent',
                'sent_at' => now(),
                'subject' => 'Completed delivery',
            ]
        );

        $response = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.email.diagnostics.messages',
                    [
                        'mailbox_id' => $mailbox->id,

                        'stuck' => 1,
                    ]
                )
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $stuck->id
            )
            ->assertJsonPath(
                'data.0.is_stuck',
                true
            )
            ->assertJsonPath(
                'data.0.subject',
                'Stuck delivery'
            );

        $this->assertStringNotContainsString(
            'Secret body',
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            'secret-token',
            $response->getContent()
        );
    }

    public function test_attachment_list_filters_antivirus_problems_without_exposing_storage_data(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.view_diagnostics',
        ]);

        $mailbox = $this->createMailbox();

        $message = $this->createMessage(
            $mailbox
        );

        $attachment = $this->createAttachment(
            $message,
            [
                'file_name' => 'infected.txt',

                'scan_status' => 'infected',

                'quarantined_at' => now(),

                'scan_result' => [
                    'driver' => 'clamav',

                    'signature' => 'Test.Signature',

                    'message' => 'Detected token=private-token',

                    'raw_response' => '/private/storage/path: FOUND',
                ],

                'metadata' => [
                    'storage_path' => '/private/storage/path',
                ],
            ]
        );

        $response = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.email.diagnostics.attachments',
                    [
                        'mailbox_id' => $mailbox->id,

                        'scan_status' => 'infected',

                        'quarantined' => 1,
                    ]
                )
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $attachment->id
            )
            ->assertJsonPath(
                'data.0.scan_status',
                'infected'
            )
            ->assertJsonPath(
                'data.0.scan_result.signature',
                'Test.Signature'
            );

        $content = $response->getContent();

        $this->assertStringNotContainsString(
            'private-token',
            $content
        );

        $this->assertStringNotContainsString(
            '/private/storage/path',
            $content
        );

        $this->assertStringNotContainsString(
            'raw_response',
            $content
        );

        $this->assertStringNotContainsString(
            'storage_path',
            $content
        );
    }

    public function test_quarantine_and_rejection_lists_are_filterable_and_safe(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.view_diagnostics',
        ]);

        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            $mailbox
        );

        $message = $this->createMessage(
            $mailbox,
            [
                'mailbox_channel_id' => $channel->id,

                'direction' => 'incoming',

                'driver' => 'imap',

                'status' => 'failed',

                'sender_address' => 'customer@example.test',

                'subject' => 'Rejected attachment',
            ]
        );

        $quarantine =
            EmailMessageQuarantine::query()
                ->create([
                    'email_message_id' => $message->id,

                    'mailbox_id' => $mailbox->id,

                    'mailbox_channel_id' => $channel->id,

                    'stage' => 'attachment_processing',

                    'reason_code' => 'attachment_failed',

                    'reason_message' => 'Scan failed password=private-value',

                    'exception_class' => 'Private\\Internal\\Exception',

                    'attempts' => 1,

                    'first_quarantined_at' => now(),

                    'last_quarantined_at' => now(),

                    'released_at' => null,

                    'released_by_id' => null,

                    'resolved_at' => null,

                    'resolution' => null,

                    'metadata' => [
                        'raw' => 'private-metadata',
                    ],
                ]);

        $rejection =
            EmailAttachmentRejection::query()
                ->create([
                    'email_message_id' => $message->id,

                    'position' => 0,

                    'external_id' => null,

                    'deduplication_key' => hash(
                        'sha256',
                        uniqid(
                            'rejection-',
                            true
                        )
                    ),

                    'file_name' => 'payload.exe',

                    'mime_type' => 'application/octet-stream',

                    'reported_size' => 2048,

                    'content_id' => null,

                    'is_inline' => false,

                    'reason_code' => 'mime_type_not_allowed',

                    'reason_message' => 'Attachment rejected token=private-token',

                    'metadata' => [
                        'raw_path' => '/private/path',
                    ],
                ]);

        $quarantineResponse = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.email.diagnostics.quarantines',
                    [
                        'mailbox_id' => $mailbox->id,

                        'resolution' => 'open',
                    ]
                )
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $quarantine->id
            )
            ->assertJsonPath(
                'data.0.state',
                'open'
            );

        $rejectionResponse = $this
            ->actingAs($admin)
            ->getJson(
                route(
                    'admin.email.diagnostics.rejected-attachments',
                    [
                        'mailbox_id' => $mailbox->id,

                        'reason_code' => 'mime_type_not_allowed',
                    ]
                )
            )
            ->assertOk()
            ->assertJsonCount(
                1,
                'data'
            )
            ->assertJsonPath(
                'data.0.id',
                $rejection->id
            )
            ->assertJsonPath(
                'data.0.file_name',
                'payload.exe'
            );

        $this->assertStringNotContainsString(
            'private-value',
            $quarantineResponse->getContent()
        );

        $this->assertStringNotContainsString(
            'Private\\Internal\\Exception',
            $quarantineResponse->getContent()
        );

        $this->assertStringNotContainsString(
            'private-metadata',
            $quarantineResponse->getContent()
        );

        $this->assertStringNotContainsString(
            'private-token',
            $rejectionResponse->getContent()
        );

        $this->assertStringNotContainsString(
            '/private/path',
            $rejectionResponse->getContent()
        );
    }

    private function createAgentWithPermissions(
        array $permissionKeys
    ): User {
        $user = User::factory()->create();

        $role = Role::query()->create([
            'name' => 'mail-diagnostics-admin-'
                .$user->id,

            'label' => 'Mail diagnostics administrator',

            'description' => null,

            'type' => 'agent',

            'is_system' => false,

            'is_default' => false,
        ]);

        $group =
            PermissionGroup::query()
                ->create([
                    'key' => 'mail-diagnostics-test-'
                        .$user->id,

                    'label' => 'Mail diagnostics test',

                    'panel' => 'admin',

                    'type' => 'agent',

                    'sort_order' => 1,
                ]);

        $permissionIds =
            collect($permissionKeys)
                ->map(
                    fn (
                        string $key
                    ): int => Permission::query()
                        ->create([
                            'permission_group_id' => $group->id,

                            'parent_id' => null,

                            'key' => $key,

                            'label' => $key,

                            'type' => 'agent',

                            'ui_type' => 'checkbox',

                            'description' => null,

                            'sort_order' => 1,
                        ])
                        ->id
                )
                ->all();

        $role
            ->permissions()
            ->sync(
                $permissionIds
            );

        $user
            ->roles()
            ->attach(
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

    private function createChannel(
        Mailbox $mailbox,
        array $overrides = [],
    ): MailboxChannel {
        return MailboxChannel::query()->create(
            array_merge(
                [
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

                    'last_checked_at' => null,

                    'last_success_at' => null,

                    'last_activity_at' => null,

                    'last_error_at' => null,

                    'last_error_code' => null,

                    'last_error_message' => null,
                ],
                $overrides
            )
        );
    }

    private function createMessage(
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

                    'direction' => 'incoming',

                    'driver' => 'imap',

                    'status' => 'received',

                    'idempotency_key' => 'diagnostics-message-'
                        .uniqid(),

                    'external_message_id' => null,

                    'internet_message_id' => null,

                    'in_reply_to_message_id' => null,

                    'reference_message_ids' => [],

                    'sender_address' => 'customer@example.test',

                    'sender_name' => 'Customer',

                    'to_recipients' => [
                        [
                            'address' => $mailbox
                                ->email_address,

                            'name' => null,
                        ],
                    ],

                    'cc_recipients' => [],

                    'bcc_recipients' => [],

                    'reply_to_recipients' => [],

                    'subject' => 'Diagnostic message',

                    'text_body' => null,

                    'html_body' => null,

                    'headers' => [],

                    'metadata' => [],

                    'raw_message_disk' => null,

                    'raw_message_path' => null,

                    'raw_message_size' => null,

                    'raw_message_checksum' => null,

                    'received_at' => now(),

                    'queued_at' => null,

                    'processing_started_at' => null,

                    'processed_at' => null,

                    'sent_at' => null,

                    'delivered_at' => null,

                    'failed_at' => null,

                    'failure_code' => null,

                    'failure_message' => null,
                ],
                $overrides
            )
        );
    }

    private function createAttachment(
        EmailMessage $message,
        array $overrides = [],
    ): EmailAttachment {
        $content = 'attachment';

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

                    'scan_status' => 'clean',

                    'scanned_at' => now(),

                    'quarantined_at' => null,

                    'scan_result' => [],

                    'metadata' => [],
                ],
                $overrides
            )
        );
    }
}
