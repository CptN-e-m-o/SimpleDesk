<?php

namespace Tests\Feature\Admin\Mail\OAuth;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\MailAdminAuditLog;
use App\Models\Admin\Mail\Mailbox;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Models\Permission;
use App\Models\PermissionGroup;
use App\Models\Role;
use App\Models\User\User;
use App\Services\Admin\Mail\OAuth\MailOAuthIntegrationService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MailOAuthIntegrationTest extends TestCase
{
    use DatabaseMigrations;

    public function test_index_and_mutations_require_their_permissions(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->get(
                route(
                    'admin.email.oauth-integrations.index'
                )
            )
            ->assertForbidden();

        $viewer = $this->user([
            'admin.mail.view_oauth_integrations',
        ]);

        $this
            ->actingAs($viewer)
            ->get(
                route(
                    'admin.email.oauth-integrations.index'
                )
            )
            ->assertOk();

        $this
            ->actingAs($viewer)
            ->post(
                route(
                    'admin.email.oauth-integrations.store'
                ),
                $this->payload()
            )
            ->assertForbidden();
    }

    public function test_client_secret_is_encrypted_and_absent_from_props(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.email.oauth-integrations.store'
                ),
                $this->payload()
            )
            ->assertRedirect();

        $connection = MailProviderConnection::query()
            ->sole();

        $this->assertSame(
            'client-secret-value',
            $connection
                ->secrets()['client_secret']
        );

        $this->assertStringNotContainsString(
            'client-secret-value',
            (string) $connection->getRawOriginal(
                'secret_configuration'
            )
        );

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.email.oauth-integrations.edit',
                    $connection
                )
            )
            ->assertOk()
            ->assertDontSee(
                'client-secret-value'
            );
    }

    public function test_blank_client_secret_preserves_existing_secret_when_provider_and_client_id_are_unchanged(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection([
            'secret_configuration' => [
                'client_secret' => 'existing-client-secret',
            ],
        ]);

        $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.email.oauth-integrations.update',
                    $connection
                ),
                $this->payload([
                    'client_secret' => '',
                ])
            )
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $connection->refresh();

        $this->assertSame(
            'google',
            $connection->getRawOriginal(
                'provider'
            )
        );

        $this->assertSame(
            'client-id',
            $connection
                ->publicConfiguration()['client_id']
        );

        $this->assertSame(
            'existing-client-secret',
            $connection
                ->secrets()['client_secret']
        );
    }

    public function test_provider_change_requires_new_client_secret(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection([
            'secret_configuration' => [
                'client_secret' => 'existing-client-secret',
            ],
        ]);

        $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.email.oauth-integrations.update',
                    $connection
                ),
                $this->payload([
                    'provider' => 'microsoft',
                    'client_secret' => '',
                    'tenant_mode' => 'common',
                    'tenant_id' => null,
                ])
            )
            ->assertSessionHasErrors(
                'client_secret'
            );

        $connection->refresh();

        $this->assertSame(
            'google',
            $connection->getRawOriginal(
                'provider'
            )
        );

        $this->assertSame(
            'existing-client-secret',
            $connection
                ->secrets()['client_secret']
        );
    }

    public function test_client_id_change_requires_new_client_secret(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection([
            'secret_configuration' => [
                'client_secret' => 'existing-client-secret',
            ],
        ]);

        $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.email.oauth-integrations.update',
                    $connection
                ),
                $this->payload([
                    'client_id' => 'different-client-id',
                    'client_secret' => '',
                ])
            )
            ->assertSessionHasErrors(
                'client_secret'
            );

        $connection->refresh();

        $this->assertSame(
            'client-id',
            $connection
                ->publicConfiguration()['client_id']
        );

        $this->assertSame(
            'existing-client-secret',
            $connection
                ->secrets()['client_secret']
        );
    }

    public function test_provider_and_client_id_change_with_new_secret_clear_tokens_and_disable_oauth_channels(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection([
            'account_identifier' => 'old-account@example.test',

            'secret_configuration' => [
                'client_secret' => 'old-client-secret',

                'access_token' => 'old-access-token',

                'refresh_token' => 'old-refresh-token',
            ],

            'token_expires_at' => now()->addHour(),

            'connected_at' => now()->subDay(),

            'last_refreshed_at' => now()->subMinutes(30),
        ]);

        $mailbox = $this->mailbox();

        $channel = $this->oauthChannel(
            $mailbox,
            $connection
        );

        $this
            ->actingAs($admin)
            ->put(
                route(
                    'admin.email.oauth-integrations.update',
                    $connection
                ),
                $this->payload([
                    'provider' => 'microsoft',

                    'client_id' => 'microsoft-client-id',

                    'client_secret' => 'new-client-secret',

                    'tenant_mode' => 'common',

                    'tenant_id' => null,
                ])
            )
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $connection->refresh();
        $channel->refresh();

        $secrets = $connection->secrets();

        $this->assertSame(
            'microsoft',
            $connection->getRawOriginal(
                'provider'
            )
        );

        $this->assertSame(
            'microsoft-client-id',
            $connection
                ->publicConfiguration()['client_id']
        );

        $this->assertSame(
            'new-client-secret',
            $secrets['client_secret']
        );

        $this->assertArrayNotHasKey(
            'access_token',
            $secrets
        );

        $this->assertArrayNotHasKey(
            'refresh_token',
            $secrets
        );

        $this->assertNull(
            $connection->account_identifier
        );

        $this->assertNull(
            $connection->token_expires_at
        );

        $this->assertNull(
            $connection->connected_at
        );

        $this->assertNull(
            $connection->last_refreshed_at
        );

        $this->assertFalse(
            $channel->is_enabled
        );
    }

    public function test_configuration_update_rolls_back_channel_changes_when_connection_save_fails(): void
    {
        $connection = $this->connection([
            'account_identifier' => 'mailbox@example.test',

            'secret_configuration' => [
                'client_secret' => 'old-client-secret',

                'access_token' => 'old-access-token',

                'refresh_token' => 'old-refresh-token',
            ],

            'token_expires_at' => now()->addHour(),

            'connected_at' => now()->subDay(),
        ]);

        $mailbox = $this->mailbox();

        $channel = $this->oauthChannel(
            $mailbox,
            $connection
        );

        $shouldThrow = true;

        MailProviderConnection::saving(
            function (
                MailProviderConnection $saving
            ) use (
                $connection,
                &$shouldThrow
            ): void {
                if (
                    $shouldThrow
                    && $saving->is($connection)
                ) {
                    $shouldThrow = false;

                    throw new RuntimeException(
                        'Forced OAuth integration save failure.'
                    );
                }
            }
        );

        try {
            app(
                MailOAuthIntegrationService::class
            )->update(
                $connection,
                $this->payload([
                    'provider' => 'microsoft',

                    'client_id' => 'microsoft-client-id',

                    'client_secret' => 'new-client-secret',

                    'tenant_mode' => 'common',

                    'tenant_id' => null,
                ])
            );

            $this->fail(
                'Expected OAuth integration save failure.'
            );
        } catch (RuntimeException $exception) {
            $this->assertSame(
                'Forced OAuth integration save failure.',
                $exception->getMessage()
            );
        }

        $channel->refresh();

        $persistedConnection =
            MailProviderConnection::query()
                ->findOrFail(
                    $connection->id
                );

        $this->assertTrue(
            $channel->is_enabled
        );

        $this->assertSame(
            'google',
            $persistedConnection->getRawOriginal(
                'provider'
            )
        );

        $this->assertSame(
            'client-id',
            $persistedConnection
                ->publicConfiguration()['client_id']
        );

        $this->assertSame(
            'old-access-token',
            $persistedConnection
                ->secrets()['access_token']
        );

        $this->assertSame(
            'old-refresh-token',
            $persistedConnection
                ->secrets()['refresh_token']
        );
    }

    public function test_specific_microsoft_tenant_is_required(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.email.oauth-integrations.store'
                ),
                $this->payload([
                    'provider' => 'microsoft',

                    'tenant_mode' => 'specific',

                    'tenant_id' => '',
                ])
            )
            ->assertSessionHasErrors(
                'tenant_id'
            );
    }

    public function test_authorization_state_is_bound_to_user_and_callback_stores_encrypted_tokens(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response([
                'access_token' => 'access-token-value',

                'refresh_token' => 'refresh-token-value',

                'expires_in' => 3600,

                'token_type' => 'Bearer',

                'scope' => 'openid email https://mail.google.com/',

                'id_token' => $this->idToken(
                    'mailbox@example.test'
                ),
            ]),

            'openidconnect.googleapis.com/*' => Http::response([
                'sub' => 'provider-id',

                'email' => 'mailbox@example.test',
            ]),
        ]);

        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection();

        $authorization = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.email.oauth-integrations.authorize',
                    $connection
                )
            )
            ->assertRedirect();

        parse_str(
            (string) parse_url(
                $authorization
                    ->headers
                    ->get('Location'),
                PHP_URL_QUERY
            ),
            $query
        );

        $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.email.oauth-integrations.callback'
                )
                .'?state='
                .$query['state']
                .'&code=authorization-code-value'
            )
            ->assertRedirect();

        $connection->refresh();

        $this->assertSame(
            'mailbox@example.test',
            $connection->account_identifier
        );

        $this->assertSame(
            'access-token-value',
            $connection
                ->secrets()['access_token']
        );

        $this->assertStringNotContainsString(
            'access-token-value',
            (string) $connection->getRawOriginal(
                'secret_configuration'
            )
        );

        $audit = (string) MailAdminAuditLog::query()
            ->latest('id')
            ->firstOrFail()
            ->getRawOriginal('context');

        $this->assertStringNotContainsString(
            'authorization-code-value',
            $audit
        );

        $this->assertStringNotContainsString(
            'access-token-value',
            $audit
        );

        $this->assertStringNotContainsString(
            'refresh-token-value',
            $audit
        );
    }

    public function test_state_mismatch_is_rejected_without_exposing_code(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection();

        $response = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.email.oauth-integrations.callback'
                )
                .'?state=wrong'
                .'&code=authorization-code-value'
            );

        $response->assertRedirect();
        $response->assertSessionHas('error');

        $this->assertArrayNotHasKey(
            'access_token',
            $connection
                ->fresh()
                ->secrets()
        );
    }

    public function test_delete_restore_disabled_disconnect_is_idempotent_and_force_delete_checks_channels(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection([
            'secret_configuration' => [
                'client_secret' => 'secret',

                'access_token' => 'access-token-value',

                'refresh_token' => 'refresh-token-value',
            ],
        ]);

        Http::fake();

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.email.oauth-integrations.disconnect',
                    $connection
                )
            )
            ->assertRedirect();

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.email.oauth-integrations.disconnect',
                    $connection
                )
            )
            ->assertRedirect();

        $this->assertArrayNotHasKey(
            'access_token',
            $connection
                ->fresh()
                ->secrets()
        );

        $this
            ->actingAs($admin)
            ->delete(
                route(
                    'admin.email.oauth-integrations.destroy',
                    $connection
                )
            )
            ->assertRedirect();

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.email.oauth-integrations.restore',
                    $connection->id
                )
            )
            ->assertRedirect();

        $this->assertFalse(
            $connection
                ->fresh()
                ->is_active
        );
    }

    public function test_connection_test_requires_existing_test_permission_and_returns_safe_json(): void
    {
        $connection = $this->connection([
            'secret_configuration' => [
                'client_secret' => 'client-secret-value',

                'access_token' => 'access-token-value',

                'refresh_token' => 'refresh-token-value',
            ],

            'token_expires_at' => now()->addHour(),
        ]);

        $manager = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $this
            ->actingAs($manager)
            ->postJson(
                route(
                    'admin.email.oauth-integrations.test',
                    $connection
                )
            )
            ->assertForbidden();

        $tester = $this->user([
            'admin.mail.test_connections',
        ]);

        $response = $this
            ->actingAs($tester)
            ->postJson(
                route(
                    'admin.email.oauth-integrations.test',
                    $connection
                )
            )
            ->assertOk();

        $response->assertJsonMissing([
            'access-token-value',
            'refresh-token-value',
            'client-secret-value',
        ]);
    }

    private function connection(
        array $overrides = []
    ): MailProviderConnection {
        return MailProviderConnection::query()
            ->create(
                array_merge(
                    [
                        'name' => 'Google',

                        'provider' => 'google',

                        'auth_type' => MailAuthenticationType::OAuth2,

                        'configuration' => [
                            'client_id' => 'client-id',

                            'tenant_mode' => null,
                        ],

                        'secret_configuration' => [
                            'client_secret' => 'secret',
                        ],

                        'scopes' => [],

                        'is_active' => true,

                        'health_status' => 'unknown',
                    ],
                    $overrides
                )
            );
    }

    private function mailbox(
        array $overrides = []
    ): Mailbox {
        return Mailbox::query()
            ->create(
                array_merge(
                    [
                        'name' => 'Support',

                        'email_address' => 'support-'
                            .uniqid()
                            .'@example.test',

                        'display_name' => 'Support',

                        'department_id' => null,

                        'is_active' => true,

                        'is_default_outgoing' => false,

                        'internal_notes' => null,
                    ],
                    $overrides
                )
            );
    }

    private function oauthChannel(
        Mailbox $mailbox,
        MailProviderConnection $connection,
        array $overrides = []
    ): MailboxChannel {
        return MailboxChannel::query()
            ->create(
                array_merge(
                    [
                        'mailbox_id' => $mailbox->id,

                        'provider_connection_id' => $connection->id,

                        'name' => 'OAuth IMAP',

                        'direction' => 'incoming',

                        'driver' => 'imap',

                        'auth_type' => MailAuthenticationType::OAuth2,

                        'is_enabled' => true,

                        'is_primary' => false,

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

    private function payload(
        array $overrides = []
    ): array {
        return array_merge(
            [
                'name' => 'Google',

                'provider' => 'google',

                'client_id' => 'client-id',

                'client_secret' => 'client-secret-value',

                'tenant_mode' => null,

                'tenant_id' => null,

                'is_active' => true,
            ],
            $overrides
        );
    }

    private function idToken(
        string $email
    ): string {
        $part = static fn (
            array $value
        ): string => rtrim(
            strtr(
                base64_encode(
                    (string) json_encode(
                        $value
                    )
                ),
                '+/',
                '-_'
            ),
            '='
        );

        return $part([
            'alg' => 'none',
        ])
            .'.'
            .$part([
                'sub' => 'provider-id',

                'email' => $email,
            ])
            .'.signature';
    }

    private function user(
        array $permissions
    ): User {
        $user = User::factory()
            ->create();

        $group = PermissionGroup::query()
            ->create([
                'key' => 'oauth-'.$user->id,

                'label' => 'OAuth',

                'panel' => 'admin',

                'type' => 'agent',

                'sort_order' => 1,
            ]);

        $role = Role::query()
            ->create([
                'name' => 'oauth-'.$user->id,

                'label' => 'OAuth',

                'type' => 'agent',

                'is_system' => false,

                'is_default' => false,
            ]);

        $ids = collect(
            $permissions
        )->map(
            fn (
                string $key
            ): int => Permission::query()
                ->create([
                    'permission_group_id' => $group->id,

                    'key' => $key,

                    'label' => $key,

                    'type' => 'agent',

                    'ui_type' => 'checkbox',

                    'sort_order' => 1,
                ])
                ->id
        );

        $role
            ->permissions()
            ->sync($ids);

        $user
            ->roles()
            ->attach($role);

        return $user;
    }

    public function test_disconnect_clears_connected_account_state_and_disables_oauth_channels(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection([
            'account_identifier' => 'mailbox@example.test',

            'secret_configuration' => [
                'client_secret' => 'client-secret-value',

                'access_token' => 'access-token-value',

                'refresh_token' => 'refresh-token-value',
            ],

            'scopes' => [
                'openid',
                'email',
                'https://mail.google.com/',
            ],

            'token_expires_at' => now()->addHour(),

            'connected_at' => now()->subDay(),

            'last_refreshed_at' => now()->subMinutes(30),

            'health_status' => 'failed',

            'last_checked_at' => now()->subMinutes(10),

            'last_success_at' => now()->subHour(),

            'last_error_at' => now()->subMinutes(10),

            'last_error_code' => 'oauth_refresh_failed',

            'last_error_message' => 'Previous safe OAuth error.',
        ]);

        $mailbox = $this->mailbox();

        $channel = $this->oauthChannel(
            $mailbox,
            $connection
        );

        Http::fake();

        $this
            ->actingAs($admin)
            ->post(
                route(
                    'admin.email.oauth-integrations.disconnect',
                    $connection
                )
            )
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $connection->refresh();
        $channel->refresh();

        $secrets = $connection->secrets();

        $this->assertSame(
            'client-secret-value',
            $secrets['client_secret']
        );

        $this->assertArrayNotHasKey(
            'access_token',
            $secrets
        );

        $this->assertArrayNotHasKey(
            'refresh_token',
            $secrets
        );

        $this->assertNull(
            $connection->account_identifier
        );

        $this->assertSame(
            [],
            $connection->scopes
        );

        $this->assertNull(
            $connection->token_expires_at
        );

        $this->assertNull(
            $connection->connected_at
        );

        $this->assertNull(
            $connection->last_refreshed_at
        );

        $this->assertSame(
            MailboxHealthStatus::Unknown,
            $connection->health_status
        );

        $this->assertNull(
            $connection->last_checked_at
        );

        $this->assertNull(
            $connection->last_success_at
        );

        $this->assertNull(
            $connection->last_error_at
        );

        $this->assertNull(
            $connection->last_error_code
        );

        $this->assertNull(
            $connection->last_error_message
        );

        $this->assertFalse(
            $channel->is_enabled
        );
    }

    public function test_callback_does_not_expose_internal_exception_message(): void
    {
        $admin = $this->user([
            'admin.mail.manage_oauth_integrations',
        ]);

        $connection = $this->connection();

        $authorization = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.email.oauth-integrations.authorize',
                    $connection
                )
            )
            ->assertRedirect();

        parse_str(
            (string) parse_url(
                $authorization
                    ->headers
                    ->get('Location'),
                PHP_URL_QUERY
            ),
            $query
        );

        Http::fake(function (): never {
            throw new RuntimeException(
                'Internal provider failure with access-token-value and database details.'
            );
        });

        $response = $this
            ->actingAs($admin)
            ->get(
                route(
                    'admin.email.oauth-integrations.callback'
                )
                .'?state='
                .$query['state']
                .'&code=authorization-code-value'
            );

        $response
            ->assertRedirect()
            ->assertSessionHas(
                'error',
                'OAuth authorization could not be completed. Please try again or review the integration configuration.'
            );

        $response->assertSessionMissing(
            'access-token-value'
        );

        $this->assertStringNotContainsString(
            'Internal provider failure',
            (string) session('error')
        );

        $this->assertStringNotContainsString(
            'access-token-value',
            (string) session('error')
        );
    }
}
