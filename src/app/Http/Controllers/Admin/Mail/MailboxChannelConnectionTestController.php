<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Mail\MailConnectionTestResource;
use App\Models\Admin\Mail\MailboxChannel;
use App\Services\Admin\Mail\Settings\MailChannelConnectionTestService;

class MailboxChannelConnectionTestController extends Controller
{
    public function __invoke(
        MailboxChannel $channel,
        MailChannelConnectionTestService $tester,
    ): MailConnectionTestResource {
        return MailConnectionTestResource::make(
            $tester->test($channel)
        );
    }
}
