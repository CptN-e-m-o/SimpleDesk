<?php

namespace Tests\Feature\Admin\Mail\Settings;

use App\Contracts\Admin\Mail\Antivirus\AttachmentScanDriver;
use App\Data\Admin\Mail\AttachmentScanResultData;
use App\Data\Admin\Mail\MailConnectionTestResultData;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Exceptions\Admin\Mail\MailDriverException;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use App\Services\Admin\Mail\MailDriverRegistry;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\Fakes\Admin\Mail\FakeIncomingMailDriver;
use Tests\Fakes\Admin\Mail\FakeOutgoingMailDriver;
use Tests\TestCase;

class AdminMailConnectionTestingTest extends TestCase
{
    use DatabaseMigrations;

    private FakeIncomingMailDriver $incomingDriver;

    private FakeOutgoingMailDriver $outgoingDriver;

    protected function setUp(): void
    {
        parent::setUp();

        $this->incomingDriver = new FakeIncomingMailDriver(
            MailConnectionTestResultData::success(
                message: 'IMAP connection succeeded.',
                latencyMilliseconds: 12,
                details: [
                    'host' => 'imap.example.test',
                ],
            )
        );

        $this->outgoingDriver = new FakeOutgoingMailDriver(
            MailConnectionTestResultData::success(
                message: 'SMTP connection succeeded.',
                latencyMilliseconds: 8,
                details: [
                    'host' => 'smtp.example.test',
                ],
            )
        );

        config()->set(
            'simpledesk-mail.drivers.incoming',
            [
                'imap' => FakeIncomingMailDriver::class,
            ]
        );

        config()->set(
            'simpledesk-mail.drivers.outgoing',
            [
                'smtp' => FakeOutgoingMailDriver::class,
            ]
        );

        $this->app->instance(
            FakeIncomingMailDriver::class,
            $this->incomingDriver
        );

        $this->app->instance(
            FakeOutgoingMailDriver::class,
            $this->outgoingDriver
        );

        $this->app->forgetInstance(
            MailDriverRegistry::class
        );
    }

    public function test_connection_test_routes_require_permission(): void
    {
        $user = User::factory()->create();

        $mailbox = $this->createMailbox();
        $channel = $this->createChannel($mailbox);

        $this
            ->actingAs($user)
            ->postJson(
                route(
                    'admin.email.channels.test',
                    $channel
                )
            )
            ->assertForbidden();
    }

    public function test_outgoing_channel_connection_can_be_tested_and_health_is_updated(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.test_connections',
        ]);

        $connection = $this->createProviderConnection();
        $mailbox = $this->createMailbox();

        $channel = $this->createChannel(
            mailbox: $mailbox,
            connection: $connection,
        );

        $this->outgoingDriver->testResult =
            MailConnectionTestResultData::success(
                message: 'SMTP connection succeeded.',
                latencyMilliseconds: 24,
                details: [
                    'host' => 'smtp.example.test',
                    'port' => 587,
                    'password' => 'super-secret',
                    'nested' => [
                        'access_token' => 'secret-token',
                    ],
                ],
            );

        $response = $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.channels.test',
                    $channel
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'data.successful',
                true
            )
            ->assertJsonPath(
                'data.latency_ms',
                24
            )
            ->assertJsonPath(
                'data.details.host',
                'smtp.example.test'
            );

        $this->assertNull(
            $response->json('data.details.password')
        );

        $this->assertNull(
            $response->json(
                'data.details.nested.access_token'
            )
        );

        $this->assertStringNotContainsString(
            'super-secret',
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            'secret-token',
            $response->getContent()
        );

        $channel->refresh();
        $connection->refresh();

        $this->assertSame(
            MailboxHealthStatus::Healthy,
            $channel->health_status
        );

        $this->assertNotNull(
            $channel->last_checked_at
        );

        $this->assertNotNull(
            $channel->last_success_at
        );

        $this->assertSame(
            MailboxHealthStatus::Healthy,
            $connection->health_status
        );
    }

    public function test_driver_exception_returns_safe_failure_result_and_marks_channel_failed(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.test_connections',
        ]);

        $mailbox = $this->createMailbox();
        $channel = $this->createChannel($mailbox);

        $this->outgoingDriver->testException =
            new MailDriverException(
                message: 'SMTP authentication failed: password=visible-secret',
                driverErrorCode: 'smtp_authentication_failed',
                retryable: false,
                failoverAllowed: true,
                affectsChannelHealth: true,
                context: [
                    'authorization' => 'Bearer secret-token',
                    'smtp_response_code' => 535,
                ],
            );

        $response = $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.channels.test',
                    $channel
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'data.successful',
                false
            )
            ->assertJsonPath(
                'data.details.error_code',
                'smtp_authentication_failed'
            )
            ->assertJsonPath(
                'data.details.retryable',
                false
            );

        $this->assertStringNotContainsString(
            'visible-secret',
            $response->getContent()
        );

        $this->assertStringNotContainsString(
            'secret-token',
            $response->getContent()
        );

        $channel->refresh();

        $this->assertSame(
            MailboxHealthStatus::Failed,
            $channel->health_status
        );

        $this->assertSame(
            'smtp_authentication_failed',
            $channel->last_error_code
        );

        $this->assertStringNotContainsString(
            'visible-secret',
            (string) $channel->last_error_message
        );
    }

    public function test_provider_connection_test_aggregates_linked_channels(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.test_connections',
        ]);

        $connection = $this->createProviderConnection();
        $mailbox = $this->createMailbox();

        $incoming = $this->createChannel(
            mailbox: $mailbox,
            connection: $connection,
            overrides: [
                'name' => 'IMAP',
                'direction' => 'incoming',
                'driver' => 'imap',
                'is_primary' => true,
                'failover_order' => 100,
            ],
        );

        $outgoing = $this->createChannel(
            mailbox: $mailbox,
            connection: $connection,
            overrides: [
                'name' => 'SMTP',
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'is_primary' => true,
                'failover_order' => 100,
            ],
        );

        $this->incomingDriver->testResult =
            MailConnectionTestResultData::success(
                message: 'IMAP connection succeeded.',
                latencyMilliseconds: 10,
            );

        $this->outgoingDriver->testResult =
            MailConnectionTestResultData::failure(
                message: 'SMTP server is unavailable.',
                latencyMilliseconds: 15,
                details: [
                    'error_code' => 'smtp_connection_failed',
                ],
            );

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.provider-connections.test',
                    $connection
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'data.successful',
                false
            )
            ->assertJsonPath(
                'data.details.total_channels',
                2
            )
            ->assertJsonPath(
                'data.details.successful_channels',
                1
            )
            ->assertJsonPath(
                'data.details.failed_channels',
                1
            )
            ->assertJsonPath(
                'data.details.health_status',
                'warning'
            );

        $connection->refresh();
        $incoming->refresh();
        $outgoing->refresh();

        $this->assertSame(
            MailboxHealthStatus::Warning,
            $connection->health_status
        );

        $this->assertSame(
            'provider_connection_test_partial_failure',
            $connection->last_error_code
        );

        $this->assertSame(
            MailboxHealthStatus::Healthy,
            $incoming->health_status
        );

        $this->assertSame(
            MailboxHealthStatus::Failed,
            $outgoing->health_status
        );
    }

    public function test_provider_connection_without_channels_returns_failure_result(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.test_connections',
        ]);

        $connection = $this->createProviderConnection();

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.provider-connections.test',
                    $connection
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'data.successful',
                false
            )
            ->assertJsonPath(
                'data.details.total_channels',
                0
            );

        $connection->refresh();

        $this->assertSame(
            MailboxHealthStatus::Warning,
            $connection->health_status
        );

        $this->assertSame(
            'provider_connection_has_no_channels',
            $connection->last_error_code
        );
    }

    public function test_antivirus_connection_can_be_tested_without_exposing_secrets(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.test_connections',
        ]);

        $driver = new class implements AttachmentScanDriver
        {
            public function name(): string
            {
                return 'fake-antivirus';
            }

            public function testConnection(): MailConnectionTestResultData
            {
                return MailConnectionTestResultData::success(
                    message: 'Antivirus connection succeeded.',
                    latencyMilliseconds: 4,
                    details: [
                        'host' => 'clamav',
                        'port' => 3310,
                        'api_key' => 'secret-api-key',
                    ],
                );
            }

            public function scanStream(
                $stream,
                string $fileName,
                int $expectedSize,
            ): AttachmentScanResultData {
                return AttachmentScanResultData::clean(
                    driver: $this->name(),
                    rawResponse: 'stream: OK',
                    scannedBytes: $expectedSize,
                );
            }
        };

        $this->app->instance(
            AttachmentScanDriver::class,
            $driver
        );

        $response = $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.antivirus.test'
                )
            )
            ->assertOk()
            ->assertJsonPath(
                'data.successful',
                true
            )
            ->assertJsonPath(
                'data.details.host',
                'clamav'
            );

        $this->assertNull(
            $response->json('data.details.api_key')
        );

        $this->assertStringNotContainsString(
            'secret-api-key',
            $response->getContent()
        );
    }

    private function createAgentWithPermissions(
        array $permissionKeys
    ): User {
        $user = User::factory()->create();

        $role = Role::query()->create([
            'name' => 'mail-connection-admin-'.$user->id,
            'label' => 'Mail connection administrator',
            'description' => null,
            'type' => 'agent',
            'is_system' => false,
            'is_default' => false,
        ]);

        $group = PermissionGroup::query()->create([
            'key' => 'mail-connection-test-'.$user->id,
            'label' => 'Mail connection test',
            'panel' => 'admin',
            'type' => 'agent',
            'sort_order' => 1,
        ]);

        $permissionIds = collect($permissionKeys)
            ->map(
                fn (string $key): int => Permission::query()
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

        $role->permissions()->sync(
            $permissionIds
        );

        $user->roles()->attach(
            $role
        );

        return $user;
    }

    private function createMailbox(): Mailbox
    {
        return Mailbox::query()->create([
            'name' => 'Support',
            'email_address' => 'support-'.uniqid().'@example.test',
            'display_name' => 'SimpleDesk Support',
            'department_id' => null,
            'is_active' => true,
            'is_default_outgoing' => false,
            'internal_notes' => null,
        ]);
    }

    private function createProviderConnection(): MailProviderConnection
    {
        return MailProviderConnection::query()->create([
            'name' => 'Shared connection '.uniqid(),
            'provider' => 'generic',
            'auth_type' => 'none',
            'account_identifier' => null,
            'tenant_identifier' => null,
            'configuration' => [],
            'secret_configuration' => [],
            'scopes' => [],
            'token_expires_at' => null,
            'is_active' => true,
            'health_status' => 'unknown',
        ]);
    }

    private function createChannel(
        Mailbox $mailbox,
        ?MailProviderConnection $connection = null,
        array $overrides = [],
    ): MailboxChannel {
        return MailboxChannel::query()->create(
            array_merge(
                [
                    'mailbox_id' => $mailbox->id,
                    'provider_connection_id' => $connection?->id,
                    'name' => 'SMTP',
                    'direction' => 'outgoing',
                    'driver' => 'smtp',
                    'auth_type' => 'none',
                    'is_enabled' => true,
                    'is_primary' => true,
                    'failover_order' => 100,
                    'configuration' => [],
                    'secret_configuration' => [],
                    'health_status' => 'unknown',
                ],
                $overrides
            )
        );
    }
}
