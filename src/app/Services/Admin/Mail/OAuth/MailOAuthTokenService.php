<?php

namespace App\Services\Admin\Mail\OAuth;

use App\Data\Admin\Mail\OAuth\MailOAuthTokenData;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Exceptions\Admin\Mail\OAuth\MailOAuthTokenRefreshException;
use App\Models\Admin\Mail\MailProviderConnection;
use Illuminate\Support\Facades\Cache;
use Throwable;

class MailOAuthTokenService
{
    private const REFRESH_FAILED_MESSAGE =
        'OAuth access token could not be refreshed. Reauthorize the account or try again later.';

    public function __construct(
        private readonly MailOAuthProviderRegistry $providers,
    ) {}

    public function accessToken(
        MailProviderConnection $connection,
        bool $forceRefresh = false
    ): string {
        $connection->refresh();

        $tokenBeforeLock = $this->secret(
            $connection,
            'access_token'
        );

        $expiresAtBeforeLock = $connection
            ->dateAttribute(
                'token_expires_at'
            )
            ?->getTimestamp();

        $refreshedAtBeforeLock = $connection
            ->dateAttribute(
                'last_refreshed_at'
            )
            ?->getTimestamp();

        if (
            ! $forceRefresh
            && $this->isUsable(
                $connection,
                $tokenBeforeLock
            )
        ) {
            return $tokenBeforeLock;
        }

        return Cache::lock(
            "mail-oauth-refresh:{$connection->id}",
            30
        )->block(
            10,
            function () use (
                $connection,
                $forceRefresh,
                $tokenBeforeLock,
                $expiresAtBeforeLock,
                $refreshedAtBeforeLock
            ): string {
                $connection->refresh();

                $currentToken = $this->secret(
                    $connection,
                    'access_token'
                );

                if (
                    ! $forceRefresh
                    && $this->isUsable(
                        $connection,
                        $currentToken
                    )
                ) {
                    return $currentToken;
                }

                if (
                    $forceRefresh
                    && $this->isUsable(
                        $connection,
                        $currentToken
                    )
                    && $this->tokensChanged(
                        connection: $connection,
                        currentToken: $currentToken,
                        previousToken: $tokenBeforeLock,
                        previousExpiresAt: $expiresAtBeforeLock,
                        previousRefreshedAt: $refreshedAtBeforeLock,
                    )
                ) {
                    return $currentToken;
                }

                $refreshToken = $this->secret(
                    $connection,
                    'refresh_token'
                );

                if ($refreshToken === null) {
                    throw new MailOAuthTokenRefreshException(
                        'The OAuth account must be authorized again.'
                    );
                }

                try {
                    $tokens = $this
                        ->providers
                        ->resolve(
                            $connection->provider
                        )
                        ->refreshAccessToken(
                            $connection,
                            $refreshToken
                        );

                    $this->storeTokens(
                        $connection,
                        $tokens,
                        true
                    );

                    return $tokens->accessToken;
                } catch (
                    MailOAuthTokenRefreshException $exception
                ) {
                    throw $exception;
                } catch (Throwable) {
                    /*
                     * Do not attach the original provider exception.
                     * Its message, response or previous exception chain
                     * may contain OAuth credentials.
                     */
                    try {
                        $connection
                            ->forceFill([
                                'health_status' => MailboxHealthStatus::Failed,

                                'last_error_at' => now(),

                                'last_error_code' => 'oauth_token_refresh_failed',

                                'last_error_message' => self::REFRESH_FAILED_MESSAGE,
                            ])
                            ->save();
                    } catch (Throwable) {
                        /*
                         * A failure to record health state must not expose
                         * either the provider exception or a database error
                         * through the OAuth refresh response.
                         */
                    }

                    throw new MailOAuthTokenRefreshException(
                        self::REFRESH_FAILED_MESSAGE
                    );
                }
            }
        );
    }

    public function storeTokens(
        MailProviderConnection $connection,
        MailOAuthTokenData $tokens,
        bool $refreshed = false
    ): void {
        $secrets = $connection->secrets();

        $secrets['access_token'] =
            $tokens->accessToken;

        if ($tokens->refreshToken !== null) {
            $secrets['refresh_token'] =
                $tokens->refreshToken;
        }

        $connection
            ->forceFill([
                'secret_configuration' => $secrets,

                'account_identifier' => $tokens->email
                    ?? $connection->account_identifier,

                'scopes' => $tokens->scopes,

                'token_expires_at' => $tokens->expiresAt,

                'connected_at' => $connection
                    ->dateAttribute(
                        'connected_at'
                    )
                    ?? now(),

                'last_refreshed_at' => $refreshed
                        ? now()
                        : $connection
                            ->dateAttribute(
                                'last_refreshed_at'
                            ),

                'health_status' => MailboxHealthStatus::Healthy,

                'last_success_at' => now(),

                'last_error_at' => null,

                'last_error_code' => null,

                'last_error_message' => null,
            ])
            ->save();
    }

    private function isUsable(
        MailProviderConnection $connection,
        ?string $token
    ): bool {
        return
            $token !== null
            && $connection
                ->dateAttribute(
                    'token_expires_at'
                )
                ?->isAfter(
                    now()->addMinutes(5)
                ) === true;
    }

    private function tokensChanged(
        MailProviderConnection $connection,
        string $currentToken,
        ?string $previousToken,
        ?int $previousExpiresAt,
        ?int $previousRefreshedAt
    ): bool {
        if (
            $previousToken === null
            || ! hash_equals(
                $previousToken,
                $currentToken
            )
        ) {
            return true;
        }

        $currentExpiresAt = $connection
            ->dateAttribute(
                'token_expires_at'
            )
            ?->getTimestamp();

        if (
            $currentExpiresAt
            !== $previousExpiresAt
        ) {
            return true;
        }

        $currentRefreshedAt = $connection
            ->dateAttribute(
                'last_refreshed_at'
            )
            ?->getTimestamp();

        return
            $currentRefreshedAt
            !== $previousRefreshedAt;
    }

    private function secret(
        MailProviderConnection $connection,
        string $key
    ): ?string {
        $value = $connection
            ->secrets()[$key]
            ?? null;

        return
            is_string($value)
            && trim($value) !== ''
                ? $value
                : null;
    }
}
