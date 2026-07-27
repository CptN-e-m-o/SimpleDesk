<?php

use App\Providers\Admin\Mail\InboundEmailTicketingServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MailServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    MailServiceProvider::class,
    InboundEmailTicketingServiceProvider::class
];
