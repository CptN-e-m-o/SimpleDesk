<?php

namespace App\Services\Admin\System\Cache;

use App\Contracts\Admin\System\Cache\CacheDriverAdapter;
use App\Enums\Admin\System\CacheDriverType;
use App\Exceptions\Admin\System\Cache\CacheDriverAdapterNotRegisteredException;
use App\Exceptions\Admin\System\Cache\InvalidCacheDriverAdapterException;
use Illuminate\Contracts\Container\Container;

class CacheDriverRegistry
{
    private array $resolved = [];

    public function __construct(private readonly Container $container, private readonly array $adapters) {}

    public function adapter(CacheDriverType|string $type): CacheDriverAdapter
    {
        $type = is_string($type) ? CacheDriverType::tryFrom($type) : $type;
        if (! $type || ! array_key_exists($type->value, $this->adapters)) {
            throw new CacheDriverAdapterNotRegisteredException('Unknown or unavailable cache driver type.');
        }
        if (isset($this->resolved[$type->value])) {
            return $this->resolved[$type->value];
        }
        $class = $this->adapters[$type->value];
        if (! is_string($class) || trim($class) === '') {
            throw new InvalidCacheDriverAdapterException('Cache adapter registry entries must be adapter classes.');
        }
        $adapter = $this->container->make($class);
        if (! $adapter instanceof CacheDriverAdapter || $adapter->type() !== $type) {
            throw new InvalidCacheDriverAdapterException("Invalid cache adapter registered for [{$type->value}].");
        }

        return $this->resolved[$type->value] = $adapter;
    }

    public function registeredTypes(): array
    {
        return array_map(function ($key) {
            $type = is_string($key) ? CacheDriverType::tryFrom($key) : null;
            if (! $type) {
                throw new InvalidCacheDriverAdapterException('Unknown cache adapter registry key.');
            }

return $type;
        }, array_keys($this->adapters));
    }

    public function definitions(): array
    {
        return array_map(fn ($type) => $this->adapter($type)->definition(), $this->registeredTypes());
    }
}
