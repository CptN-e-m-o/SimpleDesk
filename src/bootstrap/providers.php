<?php

use App\Providers\Admin\Mail\InboundEmailTicketingServiceProvider;
use App\Providers\Admin\Mail\MailAntivirusServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MailServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    MailServiceProvider::class,
    MailAntivirusServiceProvider::class,
    InboundEmailTicketingServiceProvider::class,
    App\Providers\Admin\Mail\MailAutomationServiceProvider::class,
];
