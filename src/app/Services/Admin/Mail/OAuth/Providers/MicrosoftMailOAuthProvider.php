<?php

namespace App\Services\Admin\Mail\OAuth\Providers;

use App\Contracts\Admin\Mail\OAuth\MailOAuthProvider;
use App\Data\Admin\Mail\OAuth\MailOAuthTokenData;
use App\Enums\Admin\Mail\MailOAuthTenantMode;
use App\Enums\Admin\Mail\MailProvider;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthAuthorizationException;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthConfigurationException;
use App\Models\Admin\Mail\MailProviderConnection;
use Illuminate\Http\Client\Factory;

class MicrosoftMailOAuthProvider extends AbstractMailOAuthProvider implements MailOAuthProvider
{
    private readonly MicrosoftMailOAuthIdTokenValidator $idTokens;

    public function __construct(
        Factory $http,
        MicrosoftMailOAuthIdTokenValidator $idTokens
    ) {
        parent::__construct(
            $http
        );

        $this->idTokens = $idTokens;
    }

    public function provider(): MailProvider
    {
        return MailProvider::Microsoft;
    }

    public function authorizationUrl(
        MailProviderConnection $connection,
        string $state,
        string $codeChallenge,
        string $nonce,
        string $redirectUri
    ): string {
        return $this->endpoint($connection, 'authorize').'?'.http_build_query([
            'client_id' => $this->clientId(
                $connection
            ),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'response_mode' => 'query',
            'scope' => implode(
                ' ',
                $this->requiredScopes()
            ),
            'state' => $state,
            'nonce' => $nonce,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
        ], '', '&', PHP_QUERY_RFC3986);
    }

    public function exchangeAuthorizationCode(
        MailProviderConnection $connection,
        string $code,
        string $codeVerifier,
        string $expectedNonce,
        string $redirectUri
    ): MailOAuthTokenData {
        $response = $this->formPost(
            $this->endpoint(
                $connection,
                'token'
            ),
            [
                'client_id' => $this->clientId(
                    $connection
                ),

                'client_secret' => $this->clientSecret(
                    $connection
                ),

                'code' => $code,

                'code_verifier' => $codeVerifier,

                'grant_type' => 'authorization_code',

                'redirect_uri' => $redirectUri,

                'scope' => implode(
                    ' ',
                    $this->requiredScopes()
                ),
            ]
        );

        return $this->token(
            $connection,
            $response,
            $connection
                ->secrets()['refresh_token']
            ?? null,
            false,
            $expectedNonce
        );
    }

    public function refreshAccessToken(
        MailProviderConnection $connection,
        string $refreshToken
    ): MailOAuthTokenData {
        $response = $this->formPost(
            $this->endpoint(
                $connection,
                'token'
            ),
            [
                'client_id' => $this->clientId(
                    $connection
                ),

                'client_secret' => $this->clientSecret(
                    $connection
                ),

                'refresh_token' => $refreshToken,

                'grant_type' => 'refresh_token',

                'scope' => implode(
                    ' ',
                    $this->requiredScopes()
                ),
            ]
        );

        return $this->token(
            $connection,
            $response,
            $refreshToken,
            true
        );
    }

    public function revoke(MailProviderConnection $connection, string $token): void {}

    public function requiredScopes(): array
    {
        return [
            'openid',
            'email',
            'offline_access',
            'https://outlook.office.com/IMAP.AccessAsUser.All',
            'https://outlook.office.com/SMTP.Send',
        ];
    }

    private function endpoint(MailProviderConnection $connection, string $operation): string
    {
        $mode = MailOAuthTenantMode::tryFrom((string) ($connection->publicConfiguration()['tenant_mode'] ?? 'common'));
        $tenant = match ($mode) {
            MailOAuthTenantMode::Common => 'common',
            MailOAuthTenantMode::Organizations => 'organizations',
            MailOAuthTenantMode::Specific => $connection->tenant_identifier,
            default => null,
        };

        if (! is_string($tenant) || trim($tenant) === '') {
            throw new MailOAuthConfigurationException('A Microsoft tenant ID is required for specific tenant mode.');
        }

        return 'https://login.microsoftonline.com/'.rawurlencode($tenant).'/oauth2/v2.0/'.$operation;
    }

    protected function resolveIdentity(
        MailProviderConnection $connection,
        string $accessToken,
        array $tokenPayload,
        ?string $expectedNonce,
        bool $refreshing
    ): array {
        if ($refreshing) {
            return [
                'id' => null,
                'email' => null,
            ];
        }

        $idToken = $this->nullable(
            $tokenPayload['id_token'] ?? null
        );

        if (
            $idToken === null
            || $expectedNonce === null
            || trim($expectedNonce) === ''
        ) {
            throw new MailOAuthAuthorizationException(
                'The Microsoft OAuth provider returned an incomplete identity response.'
            );
        }

        return $this->idTokens->validate(
            $connection,
            $idToken,
            $expectedNonce
        );
    }
}
