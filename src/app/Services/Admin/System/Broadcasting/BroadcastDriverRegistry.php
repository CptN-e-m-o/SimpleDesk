<?php

namespace App\Services\Admin\System\Broadcasting;

use App\Contracts\Admin\System\Broadcasting\BroadcastDriverAdapter;
use App\Data\Admin\System\Broadcasting\BroadcastDriverDefinitionData;
use App\Enums\Admin\System\BroadcastDriverType;
use Illuminate\Contracts\Container\Container;
use RuntimeException;

class BroadcastDriverRegistry
{
    private array $resolved = [];

    public function __construct(private readonly Container $container, private readonly array $adapters) {}

    public function adapter(BroadcastDriverType|string $type): BroadcastDriverAdapter
    {
        $type = is_string($type) ? BroadcastDriverType::tryFrom($type) : $type;
        if (! $type || ! isset($this->adapters[$type->value])) {
            throw new RuntimeException('Broadcast driver adapter is not registered.');
        }
        if (isset($this->resolved[$type->value])) {
            return $this->resolved[$type->value];
        }
        $adapter = $this->container->make($this->adapters[$type->value]);
        if (! $adapter instanceof BroadcastDriverAdapter || $adapter->type() !== $type) {
            throw new RuntimeException('Invalid Broadcast driver adapter registration.');
        }

        return $this->resolved[$type->value] = $adapter;
    }

    public function definitions(): array
    {
        $definitions = array_map(fn (string $type) => $this->adapter($type)->definition(), array_keys($this->adapters));
        $definitions[] = new BroadcastDriverDefinitionData(BroadcastDriverType::Ably, 'Ably', 'Ably support requires its server SDK and is deferred.', false, 'The Ably PHP SDK is not installed.');

        return array_values($definitions);
    }
}
