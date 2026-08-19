<?php

namespace App\Services\Admin\System\Cache\Drivers;

use App\Contracts\Admin\System\Cache\CacheDriverAdapter;
use App\Data\Admin\System\Cache\CacheDriverDefinitionData;
use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Data\Admin\System\Cache\CacheRuntimeConfigurationData;
use App\Enums\Admin\System\CacheDriverType;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Services\Admin\System\Cache\CacheStoreHealthProbe;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FileCacheDriverAdapter implements CacheDriverAdapter
{
    public function __construct(
        private readonly CacheStoreHealthProbe $probe,
    ) {}

    public function type(): CacheDriverType
    {
        return CacheDriverType::File;
    }

    public function definition(): CacheDriverDefinitionData
    {
        return new CacheDriverDefinitionData(
            type: $this->type(),
            label: 'File',
            description: 'Use an isolated SimpleDesk-owned directory below storage/framework/cache.',
            requiresInfrastructure: false,
            infrastructureType: null,
            recommendedForProduction: false,
        );
    }

    public function validateAndNormalize(array $configuration): array
    {
        Validator::make(
            $configuration,
            [
                'path' => ['prohibited'],
                'lock_path' => ['prohibited'],
            ],
        )->validate();

        return [];
    }

    public function runtimeConfiguration(
        CacheDriverConfiguration $configuration,
    ): CacheRuntimeConfigurationData {
        $this->validateAndNormalize(
            $configuration->configuration ?? [],
        );

        if (! $configuration->exists || ! $configuration->id) {
            throw ValidationException::withMessages([
                'configuration' => 'File cache configuration must be persisted before use.',
            ]);
        }

        $basePath = storage_path(
            'framework/cache/simpledesk/'.$configuration->id,
        );

        return new CacheRuntimeConfigurationData([
            'driver' => 'file',
            'path' => $basePath.'/data',
            'lock_path' => $basePath.'/locks',
        ]);
    }

    public function test(
        CacheDriverConfiguration $configuration,
    ): CacheHealthResultData {
        $runtime = $this->runtimeConfiguration(
            $configuration,
        );

        return $this->probe->test(
            store: $runtime->store,
            details: [
                'profile_isolated' => true,
                'separate_lock_store' => true,
            ],
        );
    }
}
