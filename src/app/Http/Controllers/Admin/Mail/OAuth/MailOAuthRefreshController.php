<?php

namespace App\Http\Controllers\Admin\Mail\OAuth;

use App\Http\Controllers\Controller;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\MailOAuthTokenService;
use Illuminate\Http\RedirectResponse;

class MailOAuthRefreshController extends Controller
{
    public function __invoke(MailProviderConnection $connection, MailOAuthTokenService $tokens): RedirectResponse
    {
        $tokens->accessToken($connection, true);

        return back()->with('success', 'OAuth access token refreshed.');
    }
}
