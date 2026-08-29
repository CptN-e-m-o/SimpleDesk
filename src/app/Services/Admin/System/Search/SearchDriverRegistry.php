<?php

namespace App\Services\Admin\System\Search;

use App\Contracts\Admin\System\Search\SearchDriverAdapter;
use App\Enums\Admin\System\SearchDriverType;
use App\Exceptions\Admin\System\Search\InvalidSearchDriverAdapterException;
use App\Exceptions\Admin\System\Search\SearchDriverAdapterNotRegisteredException;
use Illuminate\Contracts\Container\Container;

class SearchDriverRegistry
{
    private array $resolved = [];

    public function __construct(private readonly Container $container, private readonly array $adapters) {}

    public function adapter(SearchDriverType|string $type): SearchDriverAdapter
    {
        $type = is_string($type) ? SearchDriverType::tryFrom($type) : $type;
        if (! $type || ! isset($this->adapters[$type->value])) {
            throw new SearchDriverAdapterNotRegisteredException('Search driver adapter is not registered.');
        }
        if (isset($this->resolved[$type->value])) {
            return $this->resolved[$type->value];
        }
        $class = $this->adapters[$type->value];
        $adapter = is_string($class) ? $this->container->make($class) : null;
        if (! $adapter instanceof SearchDriverAdapter) {
            throw new InvalidSearchDriverAdapterException("Class [{$class}] must implement ".SearchDriverAdapter::class.'.');
        }
        if ($adapter->type() !== $type) {
            throw new InvalidSearchDriverAdapterException("Class [{$class}] returned type [{$adapter->type()->value}] instead of [{$type->value}].");
        }

        return $this->resolved[$type->value] = $adapter;
    }

    public function definitions(): array
    {
        return array_map(fn (string $key) => $this->adapter($key)->definition(), array_keys($this->adapters));
    }
}
