<?php

namespace Tests\Unit\Admin\Mail\OAuth;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailProvider;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\MailOAuthProviderRegistry;
use App\Services\Admin\Mail\OAuth\Providers\MicrosoftMailOAuthIdTokenValidator;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class MailOAuthProviderTest extends TestCase
{
    use DatabaseMigrations;

    public function test_google_authorization_url_uses_offline_pkce_and_mail_scope(): void
    {
        $connection = $this->connection(MailProvider::Google);
        $url = app(MailOAuthProviderRegistry::class)->resolve(MailProvider::Google)
            ->authorizationUrl(
                $connection,
                'state-value',
                'challenge-value',
                'nonce-value',
                'https://desk.test/callback'
            );

        $this->assertStringContainsString(
            'nonce=nonce-value',
            $url
        );
        $this->assertStringContainsString('accounts.google.com', $url);
        $this->assertStringContainsString('access_type=offline', $url);
        $this->assertStringContainsString('code_challenge=challenge-value', $url);
        $this->assertStringContainsString(rawurlencode('https://mail.google.com/'), $url);
    }

    public function test_microsoft_authorization_urls_use_selected_tenant_and_exchange_scopes(): void
    {
        $provider = app(MailOAuthProviderRegistry::class)->resolve(MailProvider::Microsoft);

        foreach (['common' => 'common', 'organizations' => 'organizations', 'specific' => 'tenant-id'] as $mode => $tenant) {
            $connection = $this->connection(MailProvider::Microsoft, $mode, $mode === 'specific' ? 'tenant-id' : null);
            $url = $provider->authorizationUrl(
                $connection,
                'state',
                'challenge',
                'nonce-value',
                'https://desk.test/callback'
            );
            $this->assertStringContainsString(
                'nonce=nonce-value',
                $url
            );
            $this->assertStringContainsString("microsoftonline.com/{$tenant}/oauth2/v2.0/authorize", $url);
            $this->assertStringContainsString(rawurlencode('https://outlook.office.com/IMAP.AccessAsUser.All'), $url);
            $this->assertStringNotContainsString('graph.microsoft.com', $url);
        }
    }

    public function test_microsoft_authorization_code_exchange_uses_verified_id_token_identity(): void
    {
        Http::preventStrayRequests();

        Http::fake([
            'https://login.microsoftonline.com/common/oauth2/v2.0/token' => Http::response([
                'access_token' => 'microsoft-access-token',

                'refresh_token' => 'microsoft-refresh-token',

                'expires_in' => 3600,

                'token_type' => 'Bearer',

                'scope' => implode(' ', [
                    'openid',
                    'email',
                    'offline_access',
                    'https://outlook.office.com/IMAP.AccessAsUser.All',
                    'https://outlook.office.com/SMTP.Send',
                ]),

                'id_token' => 'signed-microsoft-id-token',
            ]),
        ]);

        $connection = $this->connection(
            MailProvider::Microsoft
        );

        $validator = Mockery::mock(
            MicrosoftMailOAuthIdTokenValidator::class
        );

        $validator
            ->shouldReceive('validate')
            ->once()
            ->withArgs(
                static function (
                    MailProviderConnection $receivedConnection,
                    string $idToken,
                    string $expectedNonce
                ) use (
                    $connection
                ): bool {
                    return
                        $receivedConnection->is(
                            $connection
                        )
                        && $idToken
                        === 'signed-microsoft-id-token'
                        && $expectedNonce
                        === 'expected-nonce';
                }
            )
            ->andReturn([
                'id' => 'microsoft-account-id',

                'email' => 'mailbox@example.test',
            ]);

        $this->app->instance(
            MicrosoftMailOAuthIdTokenValidator::class,
            $validator
        );

        $data = app(
            MailOAuthProviderRegistry::class
        )
            ->resolve(
                MailProvider::Microsoft
            )
            ->exchangeAuthorizationCode(
                $connection,
                'authorization-code-value',
                'pkce-verifier-value',
                'expected-nonce',
                'https://desk.test/callback'
            );

        $this->assertSame(
            'microsoft-access-token',
            $data->accessToken
        );

        $this->assertSame(
            'microsoft-refresh-token',
            $data->refreshToken
        );

        $this->assertSame(
            'microsoft-account-id',
            $data->providerAccountId
        );

        $this->assertSame(
            'mailbox@example.test',
            $data->email
        );

        $this->assertSame(
            'Bearer',
            $data->tokenType
        );

        Http::assertSent(
            static function (
                Request $request
            ): bool {
                return
                    $request->url()
                    === 'https://login.microsoftonline.com/common/oauth2/v2.0/token'
                    && $request['client_id']
                    === 'client-id'
                    && $request['client_secret']
                    === 'client-secret-value'
                    && $request['code']
                    === 'authorization-code-value'
                    && $request['code_verifier']
                    === 'pkce-verifier-value'
                    && $request['grant_type']
                    === 'authorization_code'
                    && $request['redirect_uri']
                    === 'https://desk.test/callback';
            }
        );

        Http::assertNotSent(
            static function (
                Request $request
            ): bool {
                return str_contains(
                    $request->url(),
                    'graph.microsoft.com/oidc/userinfo'
                );
            }
        );

        Http::assertSentCount(1);
    }

    public function test_refresh_preserves_existing_refresh_token_when_provider_omits_new_one(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response([
            'access_token' => 'new-access-token', 'expires_in' => 3600, 'token_type' => 'Bearer', 'scope' => 'https://mail.google.com/',
        ]), 'openidconnect.googleapis.com/*' => Http::response(['sub' => 'id', 'email' => 'mailbox@example.test'])]);
        $connection = $this->connection(MailProvider::Google);
        $data = app(MailOAuthProviderRegistry::class)->resolve(MailProvider::Google)->refreshAccessToken($connection, 'old-refresh-token');

        $this->assertSame('new-access-token', $data->accessToken);
        $this->assertSame('old-refresh-token', $data->refreshToken);
    }

    public function test_provider_failure_does_not_expose_response_secrets(): void
    {
        Http::fake(['oauth2.googleapis.com/token' => Http::response(['access_token' => 'access-token-value', 'refresh_token' => 'refresh-token-value'], 400)]);
        $connection = $this->connection(MailProvider::Google);

        try {
            app(MailOAuthProviderRegistry::class)->resolve(MailProvider::Google)->refreshAccessToken($connection, 'refresh-token-value');
            $this->fail('Expected provider failure.');
        } catch (\Throwable $exception) {
            $this->assertStringNotContainsString('access-token-value', $exception->getMessage());
            $this->assertStringNotContainsString('refresh-token-value', $exception->getMessage());
        }
    }

    private function connection(MailProvider $provider, string $tenantMode = 'common', ?string $tenant = null): MailProviderConnection
    {
        return MailProviderConnection::query()->create([
            'name' => 'OAuth test', 'provider' => $provider, 'auth_type' => MailAuthenticationType::OAuth2,
            'tenant_identifier' => $tenant, 'configuration' => ['client_id' => 'client-id', 'tenant_mode' => $tenantMode],
            'secret_configuration' => ['client_secret' => 'client-secret-value'], 'scopes' => [], 'is_active' => true, 'health_status' => 'unknown',
        ]);
    }
}
