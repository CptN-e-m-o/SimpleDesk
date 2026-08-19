<?php

namespace App\Contracts\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheDriverDefinitionData;
use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Data\Admin\System\Cache\CacheRuntimeConfigurationData;
use App\Enums\Admin\System\CacheDriverType;
use App\Models\Admin\System\CacheDriverConfiguration;

interface CacheDriverAdapter
{
    public function type(): CacheDriverType;
    public function definition(): CacheDriverDefinitionData;
    public function validateAndNormalize(array $configuration): array;
    public function runtimeConfiguration(CacheDriverConfiguration $configuration): CacheRuntimeConfigurationData;
    public function test(CacheDriverConfiguration $configuration): CacheHealthResultData;
}
