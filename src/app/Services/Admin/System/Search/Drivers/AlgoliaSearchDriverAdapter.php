<?php

namespace App\Services\Admin\System\Search\Drivers;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\SearchDriverType;
use App\Models\Admin\System\InfrastructureConnection;

class AlgoliaSearchDriverAdapter extends ExternalSearchDriverAdapter
{
    public function type(): SearchDriverType
    {
        return SearchDriverType::Algolia;
    }

    protected function infrastructureType(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Algolia;
    }

    protected function label(): string
    {
        return 'Algolia';
    }

    protected function connectivity(InfrastructureConnection $connection): array
    {
        $configuration = $connection->getAttribute('configuration');

        return ['id' => is_array($configuration) ? $configuration['application_id'] : '', 'secret' => $connection->secrets()['api_key']];
    }
}
