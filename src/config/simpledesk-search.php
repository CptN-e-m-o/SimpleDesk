<?php

use App\Services\Admin\System\Search\Drivers\AlgoliaSearchDriverAdapter;
use App\Services\Admin\System\Search\Drivers\DatabaseSearchDriverAdapter;
use App\Services\Admin\System\Search\Drivers\MeilisearchSearchDriverAdapter;
use App\Services\Admin\System\Search\Drivers\TypesenseSearchDriverAdapter;

return [
    'runtime' => ['driver_name' => 'simpledesk-managed'],
    'deployment' => ['driver' => env('SCOUT_DRIVER', 'database')],
    'adapters' => [
        'database' => DatabaseSearchDriverAdapter::class,
        'meilisearch' => MeilisearchSearchDriverAdapter::class,
        'typesense' => TypesenseSearchDriverAdapter::class,
        'algolia' => AlgoliaSearchDriverAdapter::class,
    ],
];
