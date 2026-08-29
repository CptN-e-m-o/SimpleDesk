<?php

use App\Services\Admin\System\Infrastructure\Connections\AlgoliaInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\AwsInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\MeilisearchInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\PusherInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\RedisInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\ReverbInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\S3CompatibleInfrastructureConnectionAdapter;
use App\Services\Admin\System\Infrastructure\Connections\TypesenseInfrastructureConnectionAdapter;

return ['adapters' => [
    'redis' => RedisInfrastructureConnectionAdapter::class,
    'reverb' => ReverbInfrastructureConnectionAdapter::class,
    'pusher' => PusherInfrastructureConnectionAdapter::class,
    'meilisearch' => MeilisearchInfrastructureConnectionAdapter::class,
    'typesense' => TypesenseInfrastructureConnectionAdapter::class,
    'algolia' => AlgoliaInfrastructureConnectionAdapter::class,
    'aws' => AwsInfrastructureConnectionAdapter::class,
    's3_compatible' => S3CompatibleInfrastructureConnectionAdapter::class,
]];
