<?php

namespace App\Contracts\Admin\System\Search;

use App\Data\Admin\System\Search\SearchDriverDefinitionData;
use App\Data\Admin\System\Search\SearchHealthResultData;
use App\Data\Admin\System\Search\SearchRuntimeConfigurationData;
use App\Enums\Admin\System\SearchDriverType;
use App\Models\Admin\System\SearchDriverConfiguration;

interface SearchDriverAdapter
{
    public function type(): SearchDriverType;

    public function definition(): SearchDriverDefinitionData;

    public function validateAndNormalize(array $configuration, mixed $infrastructureConnectionId): array;

    public function runtimeConfiguration(SearchDriverConfiguration $configuration): SearchRuntimeConfigurationData;

    public function test(SearchDriverConfiguration $configuration): SearchHealthResultData;
}
