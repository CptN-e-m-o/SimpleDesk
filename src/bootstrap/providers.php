<?php

use App\Providers\Admin\Mail\InboundEmailTicketingServiceProvider;
use App\Providers\Admin\Mail\MailAntivirusServiceProvider;
use App\Providers\Admin\Mail\MailAutomationServiceProvider;
use App\Providers\Admin\System\CacheServiceProvider;
use App\Providers\Admin\System\InfrastructureServiceProvider;
use App\Providers\Admin\System\QueueServiceProvider;
use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use App\Providers\MailServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    MailServiceProvider::class,
    MailAntivirusServiceProvider::class,
    InboundEmailTicketingServiceProvider::class,
    MailAutomationServiceProvider::class,
    InfrastructureServiceProvider::class,
    CacheServiceProvider::class,
    QueueServiceProvider::class,
];
