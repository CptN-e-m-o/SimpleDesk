<?php

namespace App\Services\Admin\System\Search\Drivers;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\SearchDriverType;
use App\Models\Admin\System\InfrastructureConnection;

class TypesenseSearchDriverAdapter extends ExternalSearchDriverAdapter
{
    public function type(): SearchDriverType
    {
        return SearchDriverType::Typesense;
    }

    protected function infrastructureType(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Typesense;
    }

    protected function label(): string
    {
        return 'Typesense';
    }

    protected function connectivity(InfrastructureConnection $connection): array
    {
        $configuration = $connection->getAttribute('configuration');

        return ['api_key' => $connection->secrets()['api_key'], ...(is_array($configuration) ? $configuration : [])];
    }
}
