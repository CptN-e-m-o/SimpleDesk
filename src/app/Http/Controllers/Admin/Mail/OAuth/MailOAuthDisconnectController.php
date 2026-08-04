<?php

namespace App\Http\Controllers\Admin\Mail\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\MailOAuthIntegrationService;
use App\Services\Admin\Mail\OAuth\MailOAuthProviderRegistry;
use Illuminate\Http\RedirectResponse;
use Throwable;

class MailOAuthDisconnectController extends Controller
{
    public function __invoke(MailProviderConnection $connection, MailOAuthProviderRegistry $providers, MailOAuthIntegrationService $integrations): RedirectResponse
    {
        $secrets = $connection->secrets();
        $token = $secrets['refresh_token'] ?? $secrets['access_token'] ?? null;

        if (is_string($token) && $token !== '') {
            try {
                $providers->resolve($connection->provider)->revoke($connection, $token);
            } catch (Throwable) {
                // Local disconnect must remain available when the provider is unavailable.
            }
        }

        $integrations->disconnect($connection);

        return back()->with('success', 'OAuth account disconnected and linked OAuth channels disabled.');
    }
}
