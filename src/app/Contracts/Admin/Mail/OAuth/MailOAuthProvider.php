<?php

namespace App\Contracts\Admin\Mail\OAuth;

use App\Data\Admin\Mail\OAuth\MailOAuthTokenData;
use App\Enums\Admin\Mail\MailProvider;
use App\Models\Admin\Mail\MailProviderConnection;

interface MailOAuthProvider
{
    public function provider(): MailProvider;

    public function authorizationUrl(
        MailProviderConnection $connection,
        string $state,
        string $codeChallenge,
        string $nonce,
        string $redirectUri
    ): string;

    public function exchangeAuthorizationCode(
        MailProviderConnection $connection,
        string $code,
        string $codeVerifier,
        string $expectedNonce,
        string $redirectUri
    ): MailOAuthTokenData;

    public function refreshAccessToken(MailProviderConnection $connection, string $refreshToken): MailOAuthTokenData;

    public function revoke(MailProviderConnection $connection, string $token): void;

    public function requiredScopes(): array;
}
