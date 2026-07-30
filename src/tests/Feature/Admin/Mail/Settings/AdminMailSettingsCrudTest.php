<?php

namespace Tests\Feature\Admin\Mail\Settings;

use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class AdminMailSettingsCrudTest extends TestCase
{
    use DatabaseMigrations;

    public function test_mail_settings_routes_require_permissions(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->getJson(
                route('admin.email.mailboxes.index')
            )
            ->assertForbidden();

        $viewer = $this->createAgentWithPermissions([
            'admin.mail.view',
        ]);

        $this
            ->actingAs($viewer)
            ->getJson(
                route('admin.email.mailboxes.index')
            )
            ->assertOk();

        $this
            ->actingAs($viewer)
            ->postJson(
                route('admin.email.mailboxes.store'),
                $this->mailboxPayload()
            )
            ->assertForbidden();
    }

    public function test_mailboxes_can_be_created_updated_and_soft_deleted(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.manage_mailboxes',
        ]);

        $firstResponse = $this
            ->actingAs($admin)
            ->postJson(
                route('admin.email.mailboxes.store'),
                $this->mailboxPayload([
                    'name' => 'First support',
                    'email_address' => 'FIRST@EXAMPLE.TEST',
                    'is_default_outgoing' => true,
                ])
            )
            ->assertCreated()
            ->assertJsonPath('data.email_address', 'first@example.test')
            ->assertJsonPath('data.is_default_outgoing', true);

        $firstId = $firstResponse->json('data.id');

        $secondResponse = $this
            ->actingAs($admin)
            ->postJson(
                route('admin.email.mailboxes.store'),
                $this->mailboxPayload([
                    'name' => 'Second support',
                    'email_address' => 'second@example.test',
                    'is_default_outgoing' => true,
                ])
            )
            ->assertCreated();

        $secondId = $secondResponse->json('data.id');

        $this->assertDatabaseHas('mailboxes', [
            'id' => $firstId,
            'is_default_outgoing' => false,
        ]);

        $this->assertDatabaseHas('mailboxes', [
            'id' => $secondId,
            'is_default_outgoing' => true,
        ]);

        $channel = MailboxChannel::query()->create([
            'mailbox_id' => $secondId,
            'provider_connection_id' => null,
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
        ]);

        $this
            ->actingAs($admin)
            ->putJson(
                route(
                    'admin.email.mailboxes.update',
                    $secondId
                ),
                $this->mailboxPayload([
                    'name' => 'Second support disabled',
                    'email_address' => 'second@example.test',
                    'is_active' => false,
                    'is_default_outgoing' => false,
                ])
            )
            ->assertOk()
            ->assertJsonPath('data.is_active', false);

        $channel->refresh();

        $this->assertFalse($channel->is_enabled);
        $this->assertFalse($channel->is_primary);

        $this
            ->actingAs($admin)
            ->deleteJson(
                route(
                    'admin.email.mailboxes.destroy',
                    $secondId
                )
            )
            ->assertNoContent();

        $this->assertSoftDeleted('mailboxes', [
            'id' => $secondId,
        ]);
    }

    public function test_provider_connection_secrets_are_encrypted_hidden_and_preserved(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.manage_provider_connections',
        ]);

        $response = $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.provider-connections.store'
                ),
                $this->providerConnectionPayload([
                    'secret_configuration' => [
                        'password' => 'initial-secret',
                        'access_token' => 'initial-token',
                    ],
                ])
            )
            ->assertCreated()
            ->assertJsonMissing([
                'password' => 'initial-secret',
            ])
            ->assertJsonPath(
                'data.has_secret_configuration',
                true
            )
            ->assertJsonPath(
                'data.configured_secret_keys.0',
                'access_token'
            )
            ->assertJsonPath(
                'data.configured_secret_keys.1',
                'password'
            );

        $connectionId = $response->json('data.id');

        $connection = MailProviderConnection::query()->findOrFail(
            $connectionId
        );

        $this->assertSame(
            'initial-secret',
            $connection->secret_configuration['password']
        );

        $rawSecret = $connection
            ->getRawOriginal('secret_configuration');

        $this->assertIsString($rawSecret);
        $this->assertStringNotContainsString(
            'initial-secret',
            $rawSecret
        );

        $this
            ->actingAs($admin)
            ->putJson(
                route(
                    'admin.email.provider-connections.update',
                    $connectionId
                ),
                $this->providerConnectionPayload([
                    'secret_configuration' => [
                        'password' => '',
                        'client_secret' => 'new-client-secret',
                    ],
                    'clear_secret_keys' => [
                        'access_token',
                    ],
                ])
            )
            ->assertOk()
            ->assertJsonMissing([
                'client_secret' => 'new-client-secret',
            ]);

        $connection->refresh();

        $this->assertSame(
            'initial-secret',
            $connection->secret_configuration['password']
        );

        $this->assertSame(
            'new-client-secret',
            $connection->secret_configuration['client_secret']
        );

        $this->assertArrayNotHasKey(
            'access_token',
            $connection->secret_configuration
        );
    }

    public function test_channel_creation_enforces_primary_channel_and_hides_secrets(): void
    {
        $admin = $this->createAgentWithPermissions([
            'admin.mail.manage_mailboxes',
            'admin.mail.manage_channels',
        ]);

        $mailbox = Mailbox::query()->create([
            'name' => 'Support',
            'email_address' => 'support@example.test',
            'display_name' => 'Support',
            'department_id' => null,
            'is_active' => true,
            'is_default_outgoing' => true,
            'internal_notes' => null,
        ]);

        $firstResponse = $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.mailboxes.channels.store',
                    $mailbox
                ),
                $this->channelPayload([
                    'name' => 'Primary SMTP',
                    'is_primary' => false,
                    'secret_configuration' => [
                        'password' => 'smtp-secret',
                    ],
                ])
            )
            ->assertCreated()
            ->assertJsonPath('data.is_primary', true)
            ->assertJsonPath(
                'data.has_secret_configuration',
                true
            )
            ->assertJsonMissing([
                'password' => 'smtp-secret',
            ]);

        $firstId = $firstResponse->json('data.id');

        $secondResponse = $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.mailboxes.channels.store',
                    $mailbox
                ),
                $this->channelPayload([
                    'name' => 'Backup SMTP',
                    'is_primary' => true,
                    'failover_order' => 200,
                ])
            )
            ->assertCreated()
            ->assertJsonPath('data.is_primary', true);

        $secondId = $secondResponse->json('data.id');

        $this->assertDatabaseHas('mailbox_channels', [
            'id' => $firstId,
            'is_primary' => false,
        ]);

        $this->assertDatabaseHas('mailbox_channels', [
            'id' => $secondId,
            'is_primary' => true,
        ]);
    }

    public function test_channel_rejects_driver_that_does_not_support_direction(): void
    {
        /*
         * В Laravel 13 этот метод явно направляет
         * ValidationException в стандартный exception handler.
         *
         * Остальные неожиданные исключения продолжат падать,
         * поэтому реальные ошибки тест не скроет.
         */
        $this->handleValidationExceptions();

        $admin = $this->createAgentWithPermissions([
            'admin.mail.manage_channels',
        ]);

        $mailbox = Mailbox::query()->create([
            'name' => 'Support',
            'email_address' => 'support@example.test',
            'display_name' => null,
            'department_id' => null,
            'is_active' => true,
            'is_default_outgoing' => false,
            'internal_notes' => null,
        ]);

        $this
            ->actingAs($admin)
            ->postJson(
                route(
                    'admin.email.mailboxes.channels.store',
                    $mailbox
                ),
                $this->channelPayload([
                    'direction' => 'incoming',
                    'driver' => 'smtp',
                ])
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'driver',
            ]);
    }

    public function test_provider_connection_cannot_be_deleted_while_referenced(): void
    {
        $this->handleValidationExceptions();

        $admin = $this->createAgentWithPermissions([
            'admin.mail.manage_provider_connections',
        ]);

        $connection = MailProviderConnection::query()->create([
            'name' => 'Shared credentials',
            'provider' => 'generic',
            'auth_type' => 'password',
            'account_identifier' => 'support@example.test',
            'tenant_identifier' => null,
            'configuration' => [],
            'secret_configuration' => [
                'password' => 'secret',
            ],
            'scopes' => [],
            'token_expires_at' => null,
            'is_active' => true,
            'health_status' => 'unknown',
        ]);

        $mailbox = Mailbox::query()->create([
            'name' => 'Support',
            'email_address' => 'support@example.test',
            'display_name' => null,
            'department_id' => null,
            'is_active' => true,
            'is_default_outgoing' => false,
            'internal_notes' => null,
        ]);

        MailboxChannel::query()->create([
            'mailbox_id' => $mailbox->id,
            'provider_connection_id' => $connection->id,
            'name' => 'SMTP',
            'direction' => 'outgoing',
            'driver' => 'smtp',
            'auth_type' => 'password',
            'is_enabled' => true,
            'is_primary' => true,
            'failover_order' => 100,
            'configuration' => [],
            'secret_configuration' => [],
            'health_status' => 'unknown',
        ]);

        $this
            ->actingAs($admin)
            ->deleteJson(
                route(
                    'admin.email.provider-connections.destroy',
                    $connection
                )
            )
            ->assertUnprocessable()
            ->assertJsonValidationErrors([
                'provider_connection',
            ]);

        $this->assertDatabaseHas(
            'mail_provider_connections',
            [
                'id' => $connection->id,
                'deleted_at' => null,
            ]
        );
    }

    private function createAgentWithPermissions(
        array $permissionKeys
    ): User {
        $user = User::factory()->create();

        $role = Role::query()->create([
            'name' => 'mail-admin-' . $user->id,
            'label' => 'Mail administrator',
            'description' => null,
            'type' => 'agent',
            'is_system' => false,
            'is_default' => false,
        ]);

        $group = PermissionGroup::query()->create([
            'key' => 'mail-test-' . $user->id,
            'label' => 'Mail test',
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

    private function mailboxPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'name' => 'Support',
                'email_address' => 'support@example.test',
                'display_name' => 'SimpleDesk Support',
                'department_id' => null,
                'is_active' => true,
                'is_default_outgoing' => false,
                'internal_notes' => null,
            ],
            $overrides
        );
    }

    private function providerConnectionPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'name' => 'Generic mail credentials',
                'provider' => 'generic',
                'auth_type' => 'password',
                'account_identifier' => 'support@example.test',
                'tenant_identifier' => null,
                'configuration' => [
                    'region' => 'local',
                ],
                'secret_configuration' => [],
                'clear_secret_keys' => [],
                'scopes' => [],
                'token_expires_at' => null,
                'is_active' => true,
            ],
            $overrides
        );
    }

    private function channelPayload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'provider_connection_id' => null,
                'name' => 'SMTP',
                'direction' => 'outgoing',
                'driver' => 'smtp',
                'auth_type' => 'none',
                'is_enabled' => true,
                'is_primary' => false,
                'failover_order' => 100,
                'configuration' => [
                    'host' => 'mailpit',
                    'port' => 1025,
                    'encryption' => 'none',
                ],
                'secret_configuration' => [],
                'clear_secret_keys' => [],
            ],
            $overrides
        );
    }
}
