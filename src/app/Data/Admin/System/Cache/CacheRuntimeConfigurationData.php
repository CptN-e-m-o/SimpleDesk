<?php

namespace App\Data\Admin\System\Cache;

final readonly class CacheRuntimeConfigurationData
{
    public function __construct(public array $store, public array $redisConnections = []) {}
}
