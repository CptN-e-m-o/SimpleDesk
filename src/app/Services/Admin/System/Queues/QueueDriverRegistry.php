<?php

namespace App\Services\Admin\System\Queues;

use App\Contracts\Admin\System\Queues\QueueDriverAdapter;
use App\Data\Admin\System\Queues\QueueDriverDefinitionData;
use App\Enums\Admin\System\QueueDriverType;
use App\Exceptions\Admin\System\Queues\InvalidQueueDriverAdapterException;
use App\Exceptions\Admin\System\Queues\QueueDriverAdapterNotRegisteredException;
use Illuminate\Contracts\Container\Container;

class QueueDriverRegistry
{
    /**
     * @var array<string, QueueDriverAdapter>
     */
    private array $resolved = [];

    public function __construct(
        private readonly Container $container,
        private readonly array $adapters,
    ) {}

    public function adapter(
        QueueDriverType|string $type,
    ): QueueDriverAdapter {
        $type =
            is_string($type)
                ? QueueDriverType::tryFrom(
                $type,
            )
                : $type;

        if (! $type) {
            throw new QueueDriverAdapterNotRegisteredException(
                'Unknown queue driver type.',
            );
        }

        if (
            ! array_key_exists(
                $type->value,
                $this->adapters,
            )
        ) {
            throw new QueueDriverAdapterNotRegisteredException(
                "Queue driver adapter [{$type->value}] is not registered.",
            );
        }

        if (
            isset(
                $this->resolved[
                $type->value
                ],
            )
        ) {
            return $this->resolved[
            $type->value
            ];
        }

        $class =
            $this->adapters[
            $type->value
            ];

        if (
            ! is_string($class)
            || trim($class) === ''
        ) {
            throw new InvalidQueueDriverAdapterException(
                "Queue driver registry entry [{$type->value}] must contain an adapter class.",
            );
        }

        $adapter =
            $this
                ->container
                ->make(
                    $class,
                );

        if (
            ! $adapter
                instanceof QueueDriverAdapter
        ) {
            throw new InvalidQueueDriverAdapterException(
                "Class [{$class}] must implement "
                .QueueDriverAdapter::class
                .'.',
            );
        }

        if (
            $adapter->type()
            !== $type
        ) {
            throw new InvalidQueueDriverAdapterException(
                "Class [{$class}] returned type [{$adapter->type()->value}] instead of [{$type->value}].",
            );
        }

        return $this->resolved[
        $type->value
        ] = $adapter;
    }

    /**
     * @return array<int, QueueDriverDefinitionData>
     */
    public function definitions(): array
    {
        return array_map(
            fn (
                QueueDriverType $type,
            ): QueueDriverDefinitionData =>
            $this
                ->adapter(
                    $type,
                )
                ->definition(),
            $this->registeredTypes(),
        );
    }

    /**
     * @return array<int, QueueDriverType>
     */
    public function registeredTypes(): array
    {
        $types = [];

        foreach (
            array_keys(
                $this->adapters,
            ) as $key
        ) {
            if (! is_string($key)) {
                throw new InvalidQueueDriverAdapterException(
                    'Queue driver registry keys must be strings.',
                );
            }

            $type =
                QueueDriverType::tryFrom(
                    $key,
                );

            if (! $type) {
                throw new InvalidQueueDriverAdapterException(
                    "Unknown queue driver registry key [{$key}].",
                );
            }

            $types[] = $type;
        }

        return $types;
    }
}
