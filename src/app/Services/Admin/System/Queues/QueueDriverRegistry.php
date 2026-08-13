<?php

namespace App\Services\Admin\System\Queues;

use App\Contracts\Admin\System\Queues\QueueDriverAdapter;
use App\Enums\Admin\System\QueueDriverType;
use App\Exceptions\Admin\System\Queues\InvalidQueueDriverAdapterException;
use App\Exceptions\Admin\System\Queues\QueueDriverAdapterNotRegisteredException;
use Illuminate\Contracts\Container\Container;

class QueueDriverRegistry
{
    private array $resolved = [];

    public function __construct(
        private readonly Container $container,
        private readonly array $adapters,
    ) {}

    public function adapter(QueueDriverType|string $type): QueueDriverAdapter
    {
        $type = is_string($type) ? QueueDriverType::tryFrom($type) : $type;

        if (! $type || ! isset($this->adapters[$type->value])) {
            throw new QueueDriverAdapterNotRegisteredException('Queue driver adapter is not registered.');
        }

        if (isset($this->resolved[$type->value])) {
            return $this->resolved[$type->value];
        }

        $class = $this->adapters[$type->value];
        $adapter = is_string($class) ? $this->container->make($class) : null;

        if (! $adapter instanceof QueueDriverAdapter) {
            throw new InvalidQueueDriverAdapterException("Class [{$class}] must implement ".QueueDriverAdapter::class.'.');
        }

        if ($adapter->type() !== $type) {
            throw new InvalidQueueDriverAdapterException("Class [{$class}] returned type [{$adapter->type()->value}] instead of [{$type->value}].");
        }

        return $this->resolved[$type->value] = $adapter;
    }

    public function definitions(): array
    {
        return array_map(fn (QueueDriverType $type) => $this->adapter($type)->definition(), $this->registeredTypes());
    }

    public function registeredTypes(): array
    {
        return array_values(array_filter(array_map(
            static fn (string $type): ?QueueDriverType => QueueDriverType::tryFrom($type),
            array_keys($this->adapters),
        )));
    }
}
