<?php

use App\Services\Admin\System\Infrastructure\Connections\PusherInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\ReverbInfrastructureConnectionAdapter;

return ['adapters' => [
    'redis' => RedisInfrastructureConnectionAdapter::class,
    'reverb' => ReverbInfrastructureConnectionAdapter::class,
    'pusher' => PusherInfrastructureConnectionAdapter::class,
]];
