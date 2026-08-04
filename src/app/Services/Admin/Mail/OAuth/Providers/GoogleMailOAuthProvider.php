<?php

namespace App\Services\Admin\Mail\OAuth\Providers;

use App\Contracts\Admin\Mail\OAuth\MailOAuthProvider;
use App\Data\Admin\Mail\OAuth\MailOAuthTokenData;
use App\Enums\Admin\Mail\MailProvider;
use App\Models\Admin\Mail\MailProviderConnection;

class GoogleMailOAuthProvider extends AbstractMailOAuthProvider implements MailOAuthProvider
{
    private const AUTHORIZATION_URL = 'https://accounts.google.com/o/oauth2/v2/auth';

    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';

    private const REVOKE_URL = 'https://oauth2.googleapis.com/revoke';

    public function provider(): MailProvider
    {
        return MailProvider::Google;
    }

    public function authorizationUrl(
        MailProviderConnection $connection,
        string $state,
        string $codeChallenge,
        string $nonce,
        string $redirectUri
    ): string {
        return self::AUTHORIZATION_URL.'?'.http_build_query([
            'client_id' => $this->clientId(
                $connection
            ),
            'redirect_uri' => $redirectUri,
            'response_type' => 'code',
            'scope' => implode(
                ' ',
                $this->requiredScopes()
            ),
            'access_type' => 'offline',
            'prompt' => 'consent',
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
            self::TOKEN_URL,
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
            self::TOKEN_URL,
            [
                'client_id' => $this->clientId(
                    $connection
                ),

                'client_secret' => $this->clientSecret(
                    $connection
                ),

                'refresh_token' => $refreshToken,

                'grant_type' => 'refresh_token',
            ]
        );

        return $this->token(
            $connection,
            $response,
            $refreshToken,
            true
        );
    }

    public function revoke(MailProviderConnection $connection, string $token): void
    {
        $this->http->asForm()->connectTimeout(5)->timeout(10)->post(self::REVOKE_URL, ['token' => $token]);
    }

    public function requiredScopes(): array
    {
        return ['openid', 'email', 'https://mail.google.com/'];
    }

    protected function resolveIdentity(
        MailProviderConnection $connection,
        string $accessToken,
        array $tokenPayload,
        ?string $expectedNonce,
        bool $refreshing
    ): array {
        $response = $this->http
            ->withToken(
                $accessToken
            )
            ->acceptJson()
            ->connectTimeout(5)
            ->timeout(10)
            ->get(
                'https://openidconnect.googleapis.com/v1/userinfo'
            );

        if (! $response->successful()) {
            return [
                'id' => null,
                'email' => null,
            ];
        }

        return [
            'id' => $this->nullable(
                $response->json('sub')
            ),

            'email' => $this->nullable(
                $response->json('email')
            ),
        ];
    }
}
