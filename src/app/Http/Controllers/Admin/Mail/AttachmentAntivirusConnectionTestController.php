<?php

namespace App\Http\Controllers\Admin\Mail;

use App\Http\Controllers\Controller;
use App\Http\Resources\Admin\Mail\MailConnectionTestResource;
use App\Services\Admin\Mail\Settings\AttachmentAntivirusConnectionTestService;

class AttachmentAntivirusConnectionTestController extends Controller
{
    public function __invoke(
        AttachmentAntivirusConnectionTestService $tester
    ): MailConnectionTestResource {
        return MailConnectionTestResource::make(
            $tester->test()
        );
    }
}
