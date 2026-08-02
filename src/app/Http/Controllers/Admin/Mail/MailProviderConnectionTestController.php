<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Mail\MailConnectionTestResource;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\Settings\MailProviderConnectionTester;

class MailProviderConnectionTestController extends Controller
{
    public function __invoke(
        MailProviderConnection $providerConnection,
        MailProviderConnectionTester $tester,
    ): MailConnectionTestResource {
        return MailConnectionTestResource::make(
            $tester->test($providerConnection)
        );
    }
}
