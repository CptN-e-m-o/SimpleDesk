<?php

namespace App\Services\Admin\Mail\OAuth\Providers;

use App\Data\Admin\Mail\OAuth\MailOAuthTokenData;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthAuthorizationException;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthConfigurationException;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthTokenRefreshException;
use App\Models\Admin\Mail\MailProviderConnection;
use Carbon\CarbonImmutable;
use Illuminate\Http\Client\Factory;
use Illuminate\Http\Client\Response;

abstract class AbstractMailOAuthProvider
{
    public function __construct(protected readonly Factory $http) {}

    protected function clientId(MailProviderConnection $connection): string
    {
        return $this->required($connection->publicConfiguration()['client_id'] ?? null, 'OAuth client ID is not configured.');
    }

    protected function clientSecret(MailProviderConnection $connection): string
    {
        return $this->required($connection->secrets()['client_secret'] ?? null, 'OAuth client secret is not configured.');
    }

    protected function token(
        MailProviderConnection $connection,
        Response $response,
        ?string $existingRefreshToken,
        bool $refreshing = false,
        ?string $expectedNonce = null
    ): MailOAuthTokenData {
        if (! $response->successful()) {
            throw $refreshing
                ? new MailOAuthTokenRefreshException(
                    'The OAuth provider rejected the token refresh request.'
                )
                : new MailOAuthAuthorizationException(
                    'The OAuth provider rejected the authorization request.'
                );
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            throw $refreshing
                ? new MailOAuthTokenRefreshException(
                    'The OAuth provider returned an invalid token response.'
                )
                : new MailOAuthAuthorizationException(
                    'The OAuth provider returned an invalid token response.'
                );
        }

        $accessToken = $this->nullable(
            $payload['access_token'] ?? null
        );

        if ($accessToken === null) {
            throw $refreshing
                ? new MailOAuthTokenRefreshException(
                    'The OAuth provider returned an incomplete token response.'
                )
                : new MailOAuthAuthorizationException(
                    'The OAuth provider returned an incomplete token response.'
                );
        }

        $expiresIn = filter_var(
            $payload['expires_in'] ?? null,
            FILTER_VALIDATE_INT
        );

        if (
            $expiresIn === false
            || $expiresIn < 1
        ) {
            throw $refreshing
                ? new MailOAuthTokenRefreshException(
                    'The OAuth provider returned an invalid token expiry.'
                )
                : new MailOAuthAuthorizationException(
                    'The OAuth provider returned an invalid token expiry.'
                );
        }

        $scopes =
            isset($payload['scope'])
            && is_string($payload['scope'])
                ? preg_split(
                    '/\s+/',
                    trim($payload['scope'])
                ) ?: []
                : $this->requiredScopes();

        $identity = $this->resolveIdentity(
            $connection,
            $accessToken,
            $payload,
            $expectedNonce,
            $refreshing
        );

        return new MailOAuthTokenData(
            accessToken: $accessToken,

            refreshToken: $this->nullable(
                $payload['refresh_token'] ?? null
            ) ?? $existingRefreshToken,

            tokenType: $this->nullable(
                $payload['token_type'] ?? null
            ) ?? 'Bearer',

            expiresAt: CarbonImmutable::now()
                ->addSeconds(
                    $expiresIn
                ),

            scopes: array_values(
                array_filter(
                    $scopes,
                    'is_string'
                )
            ),

            providerAccountId: $identity['id'],

            email: $identity['email'],
        );
    }

    protected function formPost(string $url, array $form): Response
    {
        return $this->http->asForm()->acceptJson()->connectTimeout(5)->timeout(15)->post($url, $form);
    }

    protected function required(mixed $value, string $message): string
    {
        $value = $this->nullable($value);

        if ($value === null) {
            throw new MailOAuthConfigurationException($message);
        }

        return $value;
    }

    protected function nullable(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }

    abstract protected function requiredScopes(): array;

    abstract protected function resolveIdentity(
        MailProviderConnection $connection,
        string $accessToken,
        array $tokenPayload,
        ?string $expectedNonce,
        bool $refreshing
    ): array;
}
