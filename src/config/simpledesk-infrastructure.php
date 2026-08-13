<?php

use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureConnectionAdapter;

return ['adapters' => ['redis' => RedisInfrastructureConnectionAdapter::class]];
