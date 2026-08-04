<?php

namespace App\Data\Admin\Mail\OAuth;

use Carbon\CarbonImmutable;

final readonly class MailOAuthTokenData
{
    public function __construct(
        public string $accessToken,
        public ?string $refreshToken,
        public string $tokenType,
        public CarbonImmutable $expiresAt,
        public array $scopes,
        public ?string $providerAccountId = null,
        public ?string $email = null,
    ) {}
}
