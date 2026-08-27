<?php

namespace Tests\Fakes\Admin\System;

use App\Contracts\Admin\System\Search\SearchDriverAdapter;
use App\Data\Admin\System\Search\SearchDriverDefinitionData;
use App\Data\Admin\System\Search\SearchHealthResultData;
use App\Data\Admin\System\Search\SearchRuntimeConfigurationData;
use App\Enums\Admin\System\SearchDriverType;
use App\Enums\Admin\System\SearchHealthStatus;
use App\Models\Admin\System\SearchDriverConfiguration;

class FakeSearchDriverAdapter implements SearchDriverAdapter
{
    public SearchHealthResultData $result;

    public function __construct()
    {
        $this->result = new SearchHealthResultData(SearchHealthStatus::Healthy, 2, 'Search verified.');
    }

    public function type(): SearchDriverType
    {
        return SearchDriverType::Database;
    }

    public function definition(): SearchDriverDefinitionData
    {
        return new SearchDriverDefinitionData($this->type(), 'Fake Search', 'Test adapter.', true, false);
    }

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array
    {
        return ['configuration' => [], 'infrastructure_connection_id' => null];
    }

    public function runtimeConfiguration(SearchDriverConfiguration $configuration): SearchRuntimeConfigurationData
    {
        return new SearchRuntimeConfigurationData($this->type());
    }

    public function test(SearchDriverConfiguration $configuration): SearchHealthResultData
    {
        return $this->result;
    }
}
