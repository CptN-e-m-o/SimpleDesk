<?php

namespace Tests\Unit\Admin\Mail\OAuth;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthAuthorizationException;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\Providers\MicrosoftMailOAuthIdTokenValidator;
use Firebase\JWT\JWT;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class MicrosoftMailOAuthIdTokenValidatorTest extends TestCase
{
    use DatabaseMigrations;

    protected function setUp(): void
    {
        parent::setUp();

        Cache::flush();
        Http::preventStrayRequests();
    }

    public function test_valid_signed_id_token_returns_verified_identity(): void
    {
        $keys = $this->rsaKeys();

        Http::fake([
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' =>
                Http::response(
                    $keys['jwks']
                ),
        ]);

        $connection = $this->connection();

        $token = $this->idToken(
            $keys['private_key'],
            [
                'aud' => 'client-id',
                'nonce' => 'expected-nonce',
                'email' => 'mailbox@example.test',
            ]
        );

        $identity = app(
            MicrosoftMailOAuthIdTokenValidator::class
        )->validate(
            $connection,
            $token,
            'expected-nonce'
        );

        $this->assertSame(
            'provider-account-id',
            $identity['id']
        );

        $this->assertSame(
            'mailbox@example.test',
            $identity['email']
        );

        Http::assertSentCount(1);
    }

    public function test_nonce_mismatch_is_rejected(): void
    {
        $keys = $this->rsaKeys();

        Http::fake([
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' =>
                Http::response(
                    $keys['jwks']
                ),
        ]);

        $token = $this->idToken(
            $keys['private_key'],
            [
                'aud' => 'client-id',
                'nonce' => 'different-nonce',
            ]
        );

        $this->expectException(
            MailOAuthAuthorizationException::class
        );

        $this->expectExceptionMessage(
            'The Microsoft identity token could not be verified.'
        );

        app(
            MicrosoftMailOAuthIdTokenValidator::class
        )->validate(
            $this->connection(),
            $token,
            'expected-nonce'
        );
    }

    public function test_wrong_audience_is_rejected(): void
    {
        $keys = $this->rsaKeys();

        Http::fake([
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' =>
                Http::response(
                    $keys['jwks']
                ),
        ]);

        $token = $this->idToken(
            $keys['private_key'],
            [
                'aud' => 'another-client-id',
                'nonce' => 'expected-nonce',
            ]
        );

        $this->expectException(
            MailOAuthAuthorizationException::class
        );

        $this->expectExceptionMessage(
            'The Microsoft identity token could not be verified.'
        );

        app(
            MicrosoftMailOAuthIdTokenValidator::class
        )->validate(
            $this->connection(),
            $token,
            'expected-nonce'
        );
    }

    public function test_expired_id_token_is_rejected(): void
    {
        $keys = $this->rsaKeys();

        Http::fake([
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' =>
                Http::response(
                    $keys['jwks']
                ),
        ]);

        $token = $this->idToken(
            $keys['private_key'],
            [
                'aud' => 'client-id',
                'nonce' => 'expected-nonce',
                'iat' => now()
                    ->subHours(2)
                    ->getTimestamp(),
                'exp' => now()
                    ->subHour()
                    ->getTimestamp(),
            ]
        );

        $this->expectException(
            MailOAuthAuthorizationException::class
        );

        $this->expectExceptionMessage(
            'The Microsoft identity token could not be verified.'
        );

        app(
            MicrosoftMailOAuthIdTokenValidator::class
        )->validate(
            $this->connection(),
            $token,
            'expected-nonce'
        );
    }

    public function test_wrong_issuer_is_rejected(): void
    {
        $keys = $this->rsaKeys();

        Http::fake([
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' =>
                Http::response(
                    $keys['jwks']
                ),
        ]);

        $token = $this->idToken(
            $keys['private_key'],
            [
                'aud' => 'client-id',
                'nonce' => 'expected-nonce',
                'iss' =>
                    'https://login.microsoftonline.com/'
                    .'22222222-2222-4222-8222-222222222222'
                    .'/v2.0',
            ]
        );

        $this->expectException(
            MailOAuthAuthorizationException::class
        );

        $this->expectExceptionMessage(
            'The Microsoft identity token could not be verified.'
        );

        app(
            MicrosoftMailOAuthIdTokenValidator::class
        )->validate(
            $this->connection(),
            $token,
            'expected-nonce'
        );
    }

    public function test_specific_tenant_mismatch_is_rejected(): void
    {
        $keys = $this->rsaKeys();

        $configuredTenant =
            '11111111-1111-4111-8111-111111111111';

        $tokenTenant =
            '22222222-2222-4222-8222-222222222222';

        Http::fake([
            'https://login.microsoftonline.com/'
            .$configuredTenant
            .'/discovery/v2.0/keys' =>
                Http::response(
                    $keys['jwks']
                ),
        ]);

        $connection = $this->connection(
            tenantMode: 'specific',
            tenantIdentifier: $configuredTenant
        );

        $token = $this->idToken(
            $keys['private_key'],
            [
                'aud' => 'client-id',
                'nonce' => 'expected-nonce',
                'tid' => $tokenTenant,
                'iss' =>
                    'https://login.microsoftonline.com/'
                    .$tokenTenant
                    .'/v2.0',
            ]
        );

        $this->expectException(
            MailOAuthAuthorizationException::class
        );

        $this->expectExceptionMessage(
            'The Microsoft identity token could not be verified.'
        );

        app(
            MicrosoftMailOAuthIdTokenValidator::class
        )->validate(
            $connection,
            $token,
            'expected-nonce'
        );
    }

    public function test_missing_usable_email_is_rejected(): void
    {
        $keys = $this->rsaKeys();

        Http::fake([
            'https://login.microsoftonline.com/common/discovery/v2.0/keys' =>
                Http::response(
                    $keys['jwks']
                ),
        ]);

        $token = $this->idToken(
            $keys['private_key'],
            [
                'aud' => 'client-id',
                'nonce' => 'expected-nonce',
                'email' => null,
                'preferred_username' => null,
                'upn' => null,
            ]
        );

        $this->expectException(
            MailOAuthAuthorizationException::class
        );

        $this->expectExceptionMessage(
            'The Microsoft account did not provide a usable email address.'
        );

        app(
            MicrosoftMailOAuthIdTokenValidator::class
        )->validate(
            $this->connection(),
            $token,
            'expected-nonce'
        );
    }

    private function connection(
        string $tenantMode = 'common',
        ?string $tenantIdentifier = null
    ): MailProviderConnection {
        return MailProviderConnection::query()
            ->create([
                'name' =>
                    'Microsoft OAuth',

                'provider' =>
                    'microsoft',

                'auth_type' =>
                    MailAuthenticationType::OAuth2,

                'tenant_identifier' =>
                    $tenantIdentifier,

                'configuration' => [
                    'client_id' =>
                        'client-id',

                    'tenant_mode' =>
                        $tenantMode,
                ],

                'secret_configuration' => [
                    'client_secret' =>
                        'client-secret-value',
                ],

                'scopes' =>
                    [],

                'is_active' =>
                    true,

                'health_status' =>
                    'unknown',
            ]);
    }

    private function idToken(
        string $privateKey,
        array $overrides = []
    ): string {
        $tenantId =
            '11111111-1111-4111-8111-111111111111';

        $claims = array_merge(
            [
                'aud' =>
                    'client-id',

                'iss' =>
                    'https://login.microsoftonline.com/'
                    .$tenantId
                    .'/v2.0',

                'iat' =>
                    now()->getTimestamp(),

                'nbf' =>
                    now()
                        ->subSecond()
                        ->getTimestamp(),

                'exp' =>
                    now()
                        ->addHour()
                        ->getTimestamp(),

                'nonce' =>
                    'expected-nonce',

                'tid' =>
                    $tenantId,

                'ver' =>
                    '2.0',

                'sub' =>
                    'provider-account-id',

                'email' =>
                    'mailbox@example.test',

                'preferred_username' =>
                    'mailbox@example.test',
            ],
            $overrides
        );

        return JWT::encode(
            $claims,
            $privateKey,
            'RS256',
            'microsoft-test-key'
        );
    }

    private function rsaKeys(): array
    {
        $resource = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);

        if ($resource === false) {
            throw new RuntimeException(
                'Unable to generate RSA test key.'
            );
        }

        $exported = openssl_pkey_export(
            $resource,
            $privateKey
        );

        if (
            ! $exported
            || ! is_string($privateKey)
        ) {
            throw new RuntimeException(
                'Unable to export RSA test key.'
            );
        }

        $details = openssl_pkey_get_details(
            $resource
        );

        if (
            ! is_array($details)
            || ! isset(
                $details['rsa']['n'],
                $details['rsa']['e']
            )
        ) {
            throw new RuntimeException(
                'Unable to read RSA test key details.'
            );
        }

        return [
            'private_key' =>
                $privateKey,

            'jwks' => [
                'keys' => [
                    [
                        'kty' =>
                            'RSA',

                        'use' =>
                            'sig',

                        'kid' =>
                            'microsoft-test-key',

                        'alg' =>
                            'RS256',

                        'n' =>
                            $this->base64Url(
                                $details['rsa']['n']
                            ),

                        'e' =>
                            $this->base64Url(
                                $details['rsa']['e']
                            ),
                    ],
                ],
            ],
        ];
    }

    private function base64Url(
        string $value
    ): string {
        return rtrim(
            strtr(
                base64_encode(
                    $value
                ),
                '+/',
                '-_'
            ),
            '='
        );
    }
}
