<?php

namespace App\Console\Commands\Admin\Mail;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailProvider;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\MailOAuthTokenService;
use Illuminate\Console\Command;
use Throwable;

class RefreshMailOAuthTokensCommand extends Command
{
    protected $signature = 'simpledesk:mail:refresh-oauth-tokens';

    protected $description = 'Refresh OAuth mail access tokens approaching expiry';

    public function handle(MailOAuthTokenService $tokens): int
    {
        $failed = 0;

        MailProviderConnection::query()
            ->where('auth_type', MailAuthenticationType::OAuth2)
            ->whereIn('provider', [MailProvider::Google, MailProvider::Microsoft])
            ->where('is_active', true)
            ->whereNotNull('token_expires_at')
            ->where('token_expires_at', '<=', now()->addMinutes(10))
            ->orderBy('id')
            ->eachById(function (MailProviderConnection $connection) use ($tokens, &$failed): void {
                try {
                    $tokens->accessToken($connection, true);
                    $this->components->info("OAuth integration {$connection->id} refreshed.");
                } catch (Throwable) {
                    $failed++;
                    $this->components->error("OAuth integration {$connection->id} could not be refreshed.");
                }
            });

        return $failed === 0 ? self::SUCCESS : self::FAILURE;
    }
}
