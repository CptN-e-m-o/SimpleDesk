<?php

namespace App\Services\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use Illuminate\Validation\ValidationException;

class CacheDeploymentTargetService
{
    public function __construct(private readonly CacheStoreHealthProbe $probe) {}

    public function resolve(): array
    {
        $name = trim((string) config('simpledesk-cache.deployment.store', ''));
        $managed = trim((string) config('simpledesk-cache.runtime.store_name', 'simpledesk-managed'));
        if ($name === '') {
            throw ValidationException::withMessages(['activation' => 'The deployment Cache store is not configured.']);
        }
        if ($name === $managed) {
            throw ValidationException::withMessages(['activation' => 'The deployment Cache store cannot use the managed runtime store name.']);
        }
        $store = config("cache.stores.{$name}");
        if (! is_array($store)) {
            throw ValidationException::withMessages(['activation' => "The deployment Cache store [{$name}] no longer exists."]);
        }
        $driver = trim((string) ($store['driver'] ?? ''));
        if ($driver === '') {
            throw ValidationException::withMessages(['activation' => "The deployment Cache store [{$name}] does not define a driver."]);
        }

        return ['store' => $name, 'driver' => $driver, 'configuration' => $store];
    }

    public function test(?array $target = null): CacheHealthResultData
    {
        $target ??= $this->resolve();

        return $this->probe->test($target['configuration'], details: ['store' => $target['store'], 'driver' => $target['driver']]);
    }

    public function safeTarget(): array
    {
        try {
            $target = $this->resolve();

            return ['store' => $target['store'], 'driver' => $target['driver'], 'available' => true];
        } catch (ValidationException $e) {
            return ['store' => config('simpledesk-cache.deployment.store'), 'driver' => null, 'available' => false, 'message' => collect($e->errors())->flatten()->first()];
        }
    }
}
