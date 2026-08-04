<?php

namespace Tests\Feature\Admin\Mail\Diagnostics;

use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Models\Admin\Mail\EmailMessage;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use App\Services\Admin\Mail\Diagnostics\MailDiagnosticsService;
use App\Services\Admin\Mail\Settings\AttachmentAntivirusConnectionTestService;
use App\Services\Admin\Mail\Settings\MailChannelConnectionTestService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Mockery;
use Tests\TestCase;

class MailDiagnosticsPageTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('simpledesk-mail-ticketing.processing_lock_seconds', 60);
        config()->set('simpledesk-mail-ticketing.outgoing_replies.job.lock_seconds', 60);
        config()->set('simpledesk-mail-antivirus.enabled', true);
    }

    public function test_diagnostics_page_requires_permission_and_authorized_admin_can_open_it(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get(route('admin.email.diagnostics.index'))->assertForbidden();

        $admin = $this->createAgentWithPermissions(['admin.mail.view_diagnostics']);
        $this->actingAs($admin)
            ->get(route('admin.email.diagnostics.index'))
            ->assertOk()
            ->assertSee('Admin\\/Email\\/Diagnostics\\/Index', false);
    }

    public function test_reply_parsing_status_uses_configuration(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.view_diagnostics',
        ]);

        config()->set(
            'simpledesk-mail-reply-parsing.enabled',
            true
        );

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.email.diagnostics.index'
                )
            )
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component(
                        'Admin/Email/Diagnostics/Index'
                    )
                    ->where(
                        'system.reply_parsing_enabled',
                        true
                    )
            );

        config()->set(
            'simpledesk-mail-reply-parsing.enabled',
            false
        );

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.email.diagnostics.index'
                )
            )
            ->assertOk()
            ->assertInertia(
                fn ($page) => $page
                    ->component(
                        'Admin/Email/Diagnostics/Index'
                    )
                    ->where(
                        'system.reply_parsing_enabled',
                        false
                    )
            );
    }

    public function test_snapshot_counts_mailboxes_channels_messages_and_excludes_deleted_mailbox_channels(): void
    {
        $mailbox = $this->createMailbox();
        $this->createChannel($mailbox, ['direction' => 'incoming', 'driver' => 'imap', 'health_status' => 'healthy']);
        $this->createChannel($mailbox, ['direction' => 'outgoing', 'driver' => 'smtp', 'health_status' => 'healthy']);
        $deleted = $this->createMailbox(['email_address' => 'deleted@example.test']);
        $this->createChannel($deleted, ['health_status' => 'failed']);
        $deleted->delete();

        $this->createMessage($mailbox, ['direction' => 'incoming', 'status' => 'processed', 'processed_at' => now()]);
        $this->createMessage($mailbox, ['direction' => 'outgoing', 'status' => 'sent', 'sent_at' => now()]);

        $snapshot = app(MailDiagnosticsService::class)->dashboard();

        $this->assertSame(1, $snapshot['summary']['mailboxes_total']);
        $this->assertSame(2, $snapshot['summary']['channels_total']);
        $this->assertSame(2, $snapshot['summary']['channels_healthy']);
        $this->assertSame(1, $snapshot['message_statistics']['incoming_last_24_hours']);
        $this->assertSame(1, $snapshot['message_statistics']['outgoing_last_24_hours']);
    }

    public function test_failed_messages_recent_failures_and_secrets_are_safe(): void
    {
        $admin = $this->createAgentWithPermissions(['admin.mail.view_diagnostics']);
        $mailbox = $this->createMailbox();
        $channel = $this->createChannel($mailbox, [
            'last_error_message' => 'password=channel-secret',
            'health_status' => 'warning',
        ]);
        $this->createMessage($mailbox, [
            'mailbox_channel_id' => $channel->id,
            'status' => 'failed',
            'failed_at' => now(),
            'failure_code' => 'smtp_failed',
            'failure_message' => 'Bearer message-secret',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.email.diagnostics.index'))->assertOk();

        $this->assertStringNotContainsString('channel-secret', $response->getContent());
        $this->assertStringNotContainsString('message-secret', $response->getContent());
        $this->assertStringContainsString('smtp_failed', $response->getContent());
    }

    public function test_stuck_processing_and_sending_use_configured_lock_thresholds(): void
    {
        $mailbox = $this->createMailbox();
        $this->createMessage($mailbox, [
            'direction' => 'incoming',
            'status' => 'processing',
            'processing_started_at' => now()->subSeconds(61),
        ]);
        $this->createMessage($mailbox, [
            'direction' => 'outgoing',
            'driver' => 'smtp',
            'status' => 'sending',
            'processing_started_at' => now()->subSeconds(61),
        ]);

        $snapshot = app(MailDiagnosticsService::class)->dashboard();

        $this->assertSame(1, $snapshot['message_statistics']['stuck_processing']);
        $this->assertSame(1, $snapshot['message_statistics']['stuck_sending']);
        $this->assertSame('critical', $snapshot['summary']['overall_status']);
    }

    public function test_stuck_preparing_and_queued_messages_are_included_in_diagnostics(): void
    {
        config()->set(
            'simpledesk-mail-diagnostics.stale.preparing_seconds',
            60
        );

        config()->set(
            'simpledesk-mail-diagnostics.stale.queued_seconds',
            60
        );

        $mailbox = $this->createMailbox();

        $this->createChannel(
            $mailbox,
            [
                'direction' => 'incoming',
                'driver' => 'imap',
                'health_status' => 'healthy',
            ]
        );

        $this->createChannel(
            $mailbox,
            [
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'health_status' => 'healthy',
            ]
        );

        $this->createMessage(
            $mailbox,
            [
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'status' => 'preparing',
            ]
        );

        $this->createMessage(
            $mailbox,
            [
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'status' => 'queued',
                'queued_at' => now(),
            ]
        );

        $this->travel(61)->seconds();

        $snapshot = app(
            MailDiagnosticsService::class
        )->dashboard();

        $this->assertSame(
            1,
            $snapshot['message_statistics']['stuck_preparing']
        );

        $this->assertSame(
            1,
            $snapshot['message_statistics']['stuck_queued']
        );

        $this->assertSame(
            2,
            $snapshot['summary']['stuck_messages']
        );

        $this->assertSame(
            'critical',
            $snapshot['summary']['overall_status']
        );
    }

    public function test_overall_status_supports_healthy_warning_and_critical(): void
    {
        $mailbox = $this->createMailbox();
        $incoming = $this->createChannel($mailbox, ['direction' => 'incoming', 'driver' => 'imap', 'health_status' => 'healthy']);
        $outgoing = $this->createChannel($mailbox, ['direction' => 'outgoing', 'driver' => 'smtp', 'health_status' => 'healthy']);

        $this->assertSame('healthy', app(MailDiagnosticsService::class)->dashboard()['summary']['overall_status']);

        $incoming->update(['health_status' => 'warning']);
        $this->assertSame('warning', app(MailDiagnosticsService::class)->dashboard()['summary']['overall_status']);

        $outgoing->update(['health_status' => 'failed', 'last_error_at' => now()]);
        $this->assertSame('critical', app(MailDiagnosticsService::class)->dashboard()['summary']['overall_status']);
    }

    public function test_failed_channel_with_healthy_failover_produces_warning_instead_of_critical(): void
    {
        $mailbox = $this->createMailbox();

        $this->createChannel(
            $mailbox,
            [
                'name' => 'Primary IMAP',
                'direction' => 'incoming',
                'driver' => 'imap',
                'health_status' => 'healthy',
                'is_primary' => true,
                'failover_order' => 10,
            ]
        );

        $this->createChannel(
            $mailbox,
            [
                'name' => 'Primary SMTP',
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'health_status' => 'failed',
                'is_primary' => true,
                'failover_order' => 10,
                'last_error_at' => now(),
                'last_error_code' => 'connection_failed',
                'last_error_message' => 'Primary SMTP connection failed.',
            ]
        );

        $this->createChannel(
            $mailbox,
            [
                'name' => 'Backup SMTP',
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'health_status' => 'healthy',
                'is_primary' => false,
                'failover_order' => 20,
            ]
        );

        $snapshot = app(
            MailDiagnosticsService::class
        )->dashboard();

        $this->assertSame(
            'warning',
            $snapshot['summary']['overall_status']
        );

        $this->assertSame(
            1,
            $snapshot['summary']['channels_failed']
        );

        $this->assertSame(
            2,
            $snapshot['summary']['channels_healthy']
        );
    }

    public function test_disabled_failed_channel_does_not_affect_overall_status(): void
    {
        $mailbox = $this->createMailbox();

        $this->createChannel(
            $mailbox,
            [
                'name' => 'Healthy IMAP',
                'direction' => 'incoming',
                'driver' => 'imap',
                'health_status' => 'healthy',
            ]
        );

        $this->createChannel(
            $mailbox,
            [
                'name' => 'Healthy SMTP',
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'health_status' => 'healthy',
            ]
        );

        $this->createChannel(
            $mailbox,
            [
                'name' => 'Disabled Failed SMTP',
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'is_enabled' => false,
                'health_status' => 'failed',
                'last_error_at' => now(),
                'last_error_code' => 'old_connection_failure',
                'last_error_message' => 'Previously failed channel.',
            ]
        );

        $snapshot = app(
            MailDiagnosticsService::class
        )->dashboard();

        $this->assertSame(
            'healthy',
            $snapshot['summary']['overall_status']
        );
    }

    public function test_channel_test_requires_permission_and_uses_existing_safe_service(): void
    {
        $mailbox = $this->createMailbox();
        $channel = $this->createChannel($mailbox);
        $viewer = $this->createAgentWithPermissions(['admin.mail.view_diagnostics']);

        $this->actingAs($viewer)
            ->postJson(route('admin.email.diagnostics.channels.test', $channel))
            ->assertForbidden();

        $runner = $this->createAgentWithPermissions(['admin.mail.test_connections']);
        $tester = Mockery::mock(MailChannelConnectionTestService::class);
        $tester->shouldReceive('test')->once()->withArgs(
            fn (MailboxChannel $tested): bool => $tested->is($channel)
        )->andReturn(MailConnectionTestResultData::failure(
            message: 'The connection test failed safely.',
            details: ['error_code' => 'safe_failure'],
        ));
        $this->app->instance(MailChannelConnectionTestService::class, $tester);

        $this->actingAs($runner)
            ->postJson(route('admin.email.diagnostics.channels.test', $channel))
            ->assertOk()
            ->assertJsonPath('data.successful', false)
            ->assertJsonPath('data.message', 'The connection test failed safely.')
            ->assertJsonMissing(['exception' => true]);
    }

    public function test_antivirus_test_requires_permission_and_uses_existing_service(): void
    {
        $viewer = $this->createAgentWithPermissions(['admin.mail.view_diagnostics']);
        $this->actingAs($viewer)
            ->postJson(route('admin.email.diagnostics.antivirus.test'))
            ->assertForbidden();

        $runner = $this->createAgentWithPermissions(['admin.mail.test_connections']);
        $tester = Mockery::mock(AttachmentAntivirusConnectionTestService::class);
        $tester->shouldReceive('test')->once()->andReturn(
            MailConnectionTestResultData::success('Antivirus connection is healthy.')
        );
        $this->app->instance(AttachmentAntivirusConnectionTestService::class, $tester);

        $this->actingAs($runner)
            ->postJson(route('admin.email.diagnostics.antivirus.test'))
            ->assertOk()
            ->assertJsonPath('data.successful', true);
    }

    private function createAgentWithPermissions(array $keys): User
    {
        $user = User::factory()->create();
        $role = Role::query()->create([
            'name' => 'diagnostics-'.$user->id,
            'label' => 'Diagnostics',
            'description' => null,
            'type' => 'agent',
            'is_system' => false,
            'is_default' => false,
        ]);
        $group = PermissionGroup::query()->create([
            'key' => 'diagnostics-'.$user->id,
            'label' => 'Diagnostics',
            'panel' => 'admin',
            'type' => 'agent',
            'sort_order' => 1,
        ]);
        $ids = collect($keys)->map(fn (string $key): int => Permission::query()->create([
            'permission_group_id' => $group->id,
            'parent_id' => null,
            'key' => $key,
            'label' => $key,
            'type' => 'agent',
            'ui_type' => 'checkbox',
            'description' => null,
            'sort_order' => 1,
        ])->id)->all();
        $role->permissions()->sync($ids);
        $user->roles()->attach($role);

        return $user;
    }

    private function createMailbox(array $overrides = []): Mailbox
    {
        return Mailbox::query()->create(array_merge([
            'name' => 'Support',
            'email_address' => 'support-'.uniqid().'@example.test',
            'display_name' => 'Support',
            'department_id' => null,
            'is_active' => true,
            'is_default_outgoing' => false,
            'internal_notes' => null,
        ], $overrides));
    }

    private function createChannel(Mailbox $mailbox, array $overrides = []): MailboxChannel
    {
        return MailboxChannel::query()->create(array_merge([
            'mailbox_id' => $mailbox->id,
            'provider_connection_id' => null,
            'name' => 'Channel',
            'direction' => 'incoming',
            'driver' => 'imap',
            'auth_type' => 'none',
            'is_enabled' => true,
            'is_primary' => false,
            'failover_order' => 100,
            'configuration' => [],
            'secret_configuration' => ['password' => 'never-exposed'],
            'health_status' => 'unknown',
            'last_checked_at' => null,
            'last_success_at' => null,
            'last_activity_at' => null,
            'last_error_at' => null,
            'last_error_code' => null,
            'last_error_message' => null,
        ], $overrides));
    }

    private function createMessage(Mailbox $mailbox, array $overrides = []): EmailMessage
    {
        return EmailMessage::query()->create(array_merge([
            'mailbox_id' => $mailbox->id,
            'mailbox_channel_id' => null,
            'ticket_id' => null,
            'ticket_reply_id' => null,
            'direction' => 'incoming',
            'driver' => 'imap',
            'status' => 'received',
            'idempotency_key' => 'diagnostics-'.uniqid(),
            'external_message_id' => null,
            'internet_message_id' => null,
            'in_reply_to_message_id' => null,
            'reference_message_ids' => [],
            'sender_address' => 'customer@example.test',
            'sender_name' => null,
            'to_recipients' => [],
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
        ], $overrides));
    }
}
