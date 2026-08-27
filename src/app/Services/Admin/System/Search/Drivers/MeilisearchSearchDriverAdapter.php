<?php

namespace App\Services\Admin\System\Search\Drivers;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\SearchDriverType;
use App\Models\Admin\System\InfrastructureConnection;

class MeilisearchSearchDriverAdapter extends ExternalSearchDriverAdapter
{
    public function type(): SearchDriverType
    {
        return SearchDriverType::Meilisearch;
    }

    protected function infrastructureType(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Meilisearch;
    }

    protected function label(): string
    {
        return 'Meilisearch';
    }

    protected function connectivity(InfrastructureConnection $connection): array
    {
        $configuration = $connection->getAttribute('configuration');

        return ['host' => is_array($configuration) ? $configuration['host'] : '', 'key' => $connection->secrets()['api_key']];
    }
}
