<?php

use App\Services\Admin\System\Broadcasting\Drivers\PusherBroadcastDriverAdapter;
use App\Services\Admin\System\Broadcasting\Drivers\ReverbBroadcastDriverAdapter;

return [
    'runtime' => ['connection_name' => 'simpledesk-managed'],
    'deployment' => ['connection' => env('BROADCAST_CONNECTION', 'null')],
    'adapters' => ['reverb' => ReverbBroadcastDriverAdapter::class, 'pusher' => PusherBroadcastDriverAdapter::class],
];
