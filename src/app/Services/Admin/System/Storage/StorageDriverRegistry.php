<?php

namespace App\Services\Admin\System\Storage;

use App\Contracts\Admin\System\Storage\StorageDriverAdapter;
use App\Enums\Admin\System\StorageDriverType;
use App\Exceptions\Admin\System\Storage\InvalidStorageDriverAdapterException;
use App\Exceptions\Admin\System\Storage\StorageDriverAdapterNotRegisteredException;
use Illuminate\Contracts\Container\Container;

class StorageDriverRegistry
{
    private array $resolved = [];

    public function __construct(private readonly Container $container, private readonly array $adapters) {}

    public function adapter(StorageDriverType|string $type): StorageDriverAdapter
    {
        $type = is_string($type) ? StorageDriverType::tryFrom($type) : $type;
        if (! $type || ! isset($this->adapters[$type->value])) {
            throw new StorageDriverAdapterNotRegisteredException('Storage driver adapter is not registered.');
        }
        if (isset($this->resolved[$type->value])) {
            return $this->resolved[$type->value];
        }
        $class = $this->adapters[$type->value];
        $adapter = is_string($class) ? $this->container->make($class) : null;
        if (! $adapter instanceof StorageDriverAdapter) {
            throw new InvalidStorageDriverAdapterException("Class [{$class}] must implement ".StorageDriverAdapter::class.'.');
        }
        if ($adapter->type() !== $type) {
            throw new InvalidStorageDriverAdapterException("Class [{$class}] returned an inconsistent Storage driver type.");
        }

        return $this->resolved[$type->value] = $adapter;
    }

    public function definitions(): array
    {
        return array_map(fn (string $key) => $this->adapter($key)->definition(), array_keys($this->adapters));
    }
}
