<?php

namespace App\Services\Admin\Mail\OAuth\Providers;

use App\Enums\Admin\Mail\MailOAuthTenantMode;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthAuthorizationException;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthConfigurationException;
use App\Models\Admin\Mail\MailProviderConnection;
use Firebase\JWT\JWK;
use Firebase\JWT\JWT;
use Illuminate\Http\Client\Factory;
use Illuminate\Support\Facades\Cache;
use stdClass;
use Throwable;

class MicrosoftMailOAuthIdTokenValidator
{
    private const INVALID_TOKEN_MESSAGE =
        'The Microsoft identity token could not be verified.';

    private const JWKS_CACHE_SECONDS = 3600;

    public function __construct(
        private readonly Factory $http
    ) {}

    public function validate(
        MailProviderConnection $connection,
        string $idToken,
        string $expectedNonce
    ): array {
        if (
            trim($idToken) === ''
            || trim($expectedNonce) === ''
        ) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        $jwksUrl = $this->jwksUrl(
            $connection
        );

        try {
            $claims = $this->decode(
                $idToken,
                $this->keys(
                    $jwksUrl,
                    false
                )
            );
        } catch (Throwable) {
            try {
                $claims = $this->decode(
                    $idToken,
                    $this->keys(
                        $jwksUrl,
                        true
                    )
                );
            } catch (Throwable) {
                throw new MailOAuthAuthorizationException(
                    self::INVALID_TOKEN_MESSAGE
                );
            }
        }

        return $this->validatedIdentity(
            $connection,
            $claims,
            $expectedNonce
        );
    }

    private function decode(
        string $idToken,
        array $keys
    ): stdClass {
        return JWT::decode(
            $idToken,
            $keys
        );
    }

    private function keys(
        string $jwksUrl,
        bool $forceRefresh
    ): array {
        $cacheKey =
            'mail-oauth:microsoft:jwks:'
            .hash(
                'sha256',
                $jwksUrl
            );

        if ($forceRefresh) {
            Cache::forget(
                $cacheKey
            );
        }

        $jwks = Cache::remember(
            $cacheKey,
            now()->addSeconds(
                self::JWKS_CACHE_SECONDS
            ),
            function () use (
                $jwksUrl
            ): array {
                $response = $this->http
                    ->acceptJson()
                    ->connectTimeout(5)
                    ->timeout(10)
                    ->get(
                        $jwksUrl
                    );

                if (! $response->successful()) {
                    throw new MailOAuthAuthorizationException(
                        self::INVALID_TOKEN_MESSAGE
                    );
                }

                $payload = $response->json();

                if (
                    ! is_array($payload)
                    || ! isset($payload['keys'])
                    || ! is_array($payload['keys'])
                ) {
                    throw new MailOAuthAuthorizationException(
                        self::INVALID_TOKEN_MESSAGE
                    );
                }

                return $payload;
            }
        );

        if (! is_array($jwks)) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        return JWK::parseKeySet(
            $jwks,
            'RS256'
        );
    }

    private function validatedIdentity(
        MailProviderConnection $connection,
        stdClass $claims,
        string $expectedNonce
    ): array {
        $clientId = $this->stringValue(
            $connection
                ->publicConfiguration()['client_id']
            ?? null
        );

        if (
            $clientId === null
            || ! $this->audienceMatches(
                $claims->aud ?? null,
                $claims->azp ?? null,
                $clientId
            )
        ) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        $nonce = $this->stringValue(
            $claims->nonce ?? null
        );

        if (
            $nonce === null
            || ! hash_equals(
                $expectedNonce,
                $nonce
            )
        ) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        $tenantId = $this->stringValue(
            $claims->tid ?? null
        );

        $issuer = $this->stringValue(
            $claims->iss ?? null
        );

        if (
            $tenantId === null
            || ! $this->isGuid(
                $tenantId
            )
            || $issuer === null
        ) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        $expectedIssuer =
            'https://login.microsoftonline.com/'
            .$tenantId
            .'/v2.0';

        if (
            ! hash_equals(
                $expectedIssuer,
                $issuer
            )
        ) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        $version = $this->stringValue(
            $claims->ver ?? null
        );

        if (
            $version !== null
            && $version !== '2.0'
        ) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        $this->assertSpecificTenant(
            $connection,
            $tenantId
        );

        $now = time();

        $expiresAt = $this->integerValue(
            $claims->exp ?? null
        );

        $issuedAt = $this->integerValue(
            $claims->iat ?? null
        );

        if (
            $expiresAt === null
            || $expiresAt <= $now
            || $issuedAt === null
            || $issuedAt > $now + 60
        ) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        $providerAccountId = $this->stringValue(
            $claims->sub ?? null
        );

        if ($providerAccountId === null) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }

        $email = $this->firstEmail([
            $claims->email ?? null,
            $claims->preferred_username ?? null,
            $claims->upn ?? null,
        ]);

        if ($email === null) {
            throw new MailOAuthAuthorizationException(
                'The Microsoft account did not provide a usable email address.'
            );
        }

        return [
            'id' => $providerAccountId,
            'email' => $email,
        ];
    }

    private function assertSpecificTenant(
        MailProviderConnection $connection,
        string $tokenTenantId
    ): void {
        $mode = MailOAuthTenantMode::tryFrom(
            (string) (
                $connection
                    ->publicConfiguration()['tenant_mode']
                ?? MailOAuthTenantMode::Common->value
            )
        );

        if (
            $mode
            !== MailOAuthTenantMode::Specific
        ) {
            return;
        }

        $configuredTenant = $this->stringValue(
            $connection->tenant_identifier
        );

        if (
            $configuredTenant === null
            || ! $this->isGuid(
                $configuredTenant
            )
        ) {
            return;
        }

        if (
            ! hash_equals(
                strtolower(
                    $configuredTenant
                ),
                strtolower(
                    $tokenTenantId
                )
            )
        ) {
            throw new MailOAuthAuthorizationException(
                self::INVALID_TOKEN_MESSAGE
            );
        }
    }

    private function jwksUrl(
        MailProviderConnection $connection
    ): string {
        $mode = MailOAuthTenantMode::tryFrom(
            (string) (
                $connection
                    ->publicConfiguration()['tenant_mode']
                ?? MailOAuthTenantMode::Common->value
            )
        );

        $authority = match ($mode) {
            MailOAuthTenantMode::Common => 'common',

            MailOAuthTenantMode::Organizations => 'organizations',

            MailOAuthTenantMode::Specific => $this->stringValue(
                $connection->tenant_identifier
            ),

            default => null,
        };

        if ($authority === null) {
            throw new MailOAuthConfigurationException(
                'A Microsoft tenant ID is required for specific tenant mode.'
            );
        }

        return
            'https://login.microsoftonline.com/'
            .rawurlencode(
                $authority
            )
            .'/discovery/v2.0/keys';
    }

    private function audienceMatches(
        mixed $audience,
        mixed $authorizedParty,
        string $clientId
    ): bool {
        if (is_string($audience)) {
            return hash_equals(
                $clientId,
                $audience
            );
        }

        if (! is_array($audience)) {
            return false;
        }

        $matchingAudiences = array_values(
            array_filter(
                $audience,
                static fn (mixed $value): bool => is_string($value)
                    && hash_equals(
                        $clientId,
                        $value
                    )
            )
        );

        if ($matchingAudiences === []) {
            return false;
        }

        if (count($audience) <= 1) {
            return true;
        }

        return
            is_string($authorizedParty)
            && hash_equals(
                $clientId,
                $authorizedParty
            );
    }

    private function firstEmail(
        array $values
    ): ?string {
        foreach ($values as $value) {
            $email = $this->stringValue(
                $value
            );

            if (
                $email !== null
                && filter_var(
                    $email,
                    FILTER_VALIDATE_EMAIL
                ) !== false
            ) {
                return $email;
            }
        }

        return null;
    }

    private function stringValue(
        mixed $value
    ): ?string {
        if (! is_string($value)) {
            return null;
        }

        $value = trim(
            $value
        );

        return $value === ''
            ? null
            : $value;
    }

    private function integerValue(
        mixed $value
    ): ?int {
        $validated = filter_var(
            $value,
            FILTER_VALIDATE_INT
        );

        return $validated === false
            ? null
            : $validated;
    }

    private function isGuid(
        string $value
    ): bool {
        return preg_match(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i',
            $value
        ) === 1;
    }
}
