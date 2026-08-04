<?php

namespace App\Http\Controllers\Admin\Mail\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\MailOAuthProviderRegistry;
use App\Services\Admin\Mail\OAuth\MailOAuthStateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class MailOAuthAuthorizationController extends Controller
{
    public function __invoke(
        Request $request,
        MailProviderConnection $connection,
        MailOAuthStateService $states,
        MailOAuthProviderRegistry $providers
    ): RedirectResponse {
        abort_unless(
            $connection->is_active,
            422,
            'Enable the OAuth integration before connecting an account.'
        );

        $flow = $states->issue(
            $request,
            $connection
        );

        $redirectUri = route(
            'admin.email.oauth-integrations.callback'
        );

        return redirect()->away(
            $providers
                ->resolve(
                    $connection->provider
                )
                ->authorizationUrl(
                    $connection,
                    $flow['state'],
                    $flow['challenge'],
                    $flow['nonce'],
                    $redirectUri
                )
        );
    }
}
