<?php

namespace App\Services\Admin\System\Infrastructure;

use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Exceptions\Admin\System\Infrastructure\InfrastructureConnectionAdapterNotRegisteredException;
use App\Exceptions\Admin\System\Infrastructure\InvalidInfrastructureConnectionAdapterException;
use Illuminate\Contracts\Container\Container;

class InfrastructureConnectionRegistry
{
    private array $resolved = [];

    public function __construct(private readonly Container $container, private readonly array $adapters) {}

    public function adapter(InfrastructureConnectionType|string $type): InfrastructureConnectionAdapter
    {
        $type = is_string($type) ? InfrastructureConnectionType::tryFrom($type) : $type;
        if (! $type || ! isset($this->adapters[$type->value])) {
            throw new InfrastructureConnectionAdapterNotRegisteredException('Infrastructure connection adapter is not registered.');
        }
        if (isset($this->resolved[$type->value])) {
            return $this->resolved[$type->value];
        }
        $class = $this->adapters[$type->value];
        $adapter = is_string($class) ? $this->container->make($class) : null;
        if (! $adapter instanceof InfrastructureConnectionAdapter) {
            throw new InvalidInfrastructureConnectionAdapterException("Class [{$class}] must implement ".InfrastructureConnectionAdapter::class.'.');
        }
        if ($adapter->type() !== $type) {
            throw new InvalidInfrastructureConnectionAdapterException("Class [{$class}] returned type [{$adapter->type()->value}] instead of [{$type->value}].");
        }

        return $this->resolved[$type->value] = $adapter;
    }

    public function definitions(): array
    {
        return array_values(array_map(fn (string $key) => $this->adapter($key)->definition(), array_keys($this->adapters)));
    }
}
