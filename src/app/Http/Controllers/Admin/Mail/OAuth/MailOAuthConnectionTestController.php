<?php

namespace App\Http\Controllers\Admin\Mail\OAuth;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Mail\MailConnectionTestResource;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\OAuth\MailOAuthTokenService;
use App\Services\Admin\Mail\Settings\MailProviderConnectionTester;

class MailOAuthConnectionTestController extends Controller
{
    public function __invoke(MailProviderConnection $connection, MailOAuthTokenService $tokens, MailProviderConnectionTester $tester): MailConnectionTestResource
    {
        $tokens->accessToken($connection);

        return MailConnectionTestResource::make($tester->test($connection));
    }
}
