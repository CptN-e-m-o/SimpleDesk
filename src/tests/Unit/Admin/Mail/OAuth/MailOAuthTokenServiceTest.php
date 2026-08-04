<?php

namespace Tests\Unit\Admin\Mail\OAuth;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthTokenRefreshException;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\MailOAuthTokenService;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MailOAuthTokenServiceTest extends TestCase
{
    use DatabaseMigrations;

    public function test_unexpired_token_is_returned_without_http_request(): void
    {
        Http::fake();
        $connection = $this->connection(now()->addHour());

        $this->assertSame('access-token-value', app(MailOAuthTokenService::class)->accessToken($connection));
        Http::assertNothingSent();
    }

    public function test_expiring_token_is_refreshed_and_old_refresh_token_is_preserved(): void
    {
        Http::fake([
            'oauth2.googleapis.com/token' => Http::response(['access_token' => 'new-access-token', 'expires_in' => 3600, 'token_type' => 'Bearer', 'scope' => 'https://mail.google.com/']),
            'openidconnect.googleapis.com/*' => Http::response(['sub' => 'account-id', 'email' => 'verified@example.test']),
        ]);
        $connection = $this->connection(now()->addMinute());
        $token = app(MailOAuthTokenService::class)->accessToken($connection);

        $this->assertSame('new-access-token', $token);
        $connection->refresh();
        $this->assertSame('refresh-token-value', $connection->secrets()['refresh_token']);
        $this->assertSame('verified@example.test', $connection->account_identifier);
        $this->assertNotNull($connection->last_refreshed_at);
    }

    public function test_provider_exception_is_not_exposed_or_attached_to_refresh_exception(): void
    {
        Http::fake(
            static function (): never {
                throw new RuntimeException(
                    'Provider failure containing access-token-value, refresh-token-value and client-secret-value.'
                );
            }
        );

        $connection = $this->connection(
            now()->addMinute()
        );

        try {
            app(
                MailOAuthTokenService::class
            )->accessToken(
                $connection
            );

            $this->fail(
                'Expected OAuth token refresh exception.'
            );
        } catch (
            MailOAuthTokenRefreshException $exception
        ) {
            $this->assertSame(
                'OAuth access token could not be refreshed. Reauthorize the account or try again later.',
                $exception->getMessage()
            );

            $this->assertNull(
                $exception->getPrevious()
            );

            $this->assertStringNotContainsString(
                'access-token-value',
                $exception->getMessage()
            );

            $this->assertStringNotContainsString(
                'refresh-token-value',
                $exception->getMessage()
            );

            $this->assertStringNotContainsString(
                'client-secret-value',
                $exception->getMessage()
            );

            $this->assertStringNotContainsString(
                'Provider failure',
                $exception->getMessage()
            );
        }

        $connection->refresh();

        $this->assertSame(
            MailboxHealthStatus::Failed,
            $connection->health_status
        );

        $this->assertSame(
            'oauth_token_refresh_failed',
            $connection->last_error_code
        );

        $this->assertSame(
            'OAuth access token could not be refreshed. Reauthorize the account or try again later.',
            $connection->last_error_message
        );

        $this->assertStringNotContainsString(
            'access-token-value',
            (string) $connection->last_error_message
        );

        $this->assertStringNotContainsString(
            'refresh-token-value',
            (string) $connection->last_error_message
        );

        $this->assertStringNotContainsString(
            'client-secret-value',
            (string) $connection->last_error_message
        );

        $this->assertSame(
            'access-token-value',
            $connection->secrets()['access_token']
        );

        $this->assertSame(
            'refresh-token-value',
            $connection->secrets()['refresh_token']
        );
    }

    private function connection(mixed $expiry): MailProviderConnection
    {
        return MailProviderConnection::query()->create([
            'name' => 'Google', 'provider' => 'google', 'auth_type' => MailAuthenticationType::OAuth2,
            'configuration' => ['client_id' => 'client-id'],
            'secret_configuration' => ['client_secret' => 'client-secret-value', 'access_token' => 'access-token-value', 'refresh_token' => 'refresh-token-value'],
            'scopes' => ['https://mail.google.com/'], 'token_expires_at' => $expiry, 'is_active' => true, 'health_status' => 'healthy',
        ]);
    }
}
