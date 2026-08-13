<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use Illuminate\Foundation\Application;
use Illuminate\Support\Facades\Facade;
use InvalidArgumentException;

class RedisInfrastructureRuntimeConnectionRegistrar
{
    public function __construct(
        private readonly Application $app,
    ) {}

    /**
     * @param array<string, array<string, mixed>> $connections
     */
    public function registerMany(
        array $connections,
    ): void {
        if ($connections === []) {
            return;
        }

        foreach (
            $connections
            as $name => $configuration
        ) {
            $name =
                trim(
                    (string) $name,
                );

            if ($name === '') {
                throw new InvalidArgumentException(
                    'Runtime Redis connection name cannot be empty.',
                );
            }

            if (! is_array($configuration)) {
                throw new InvalidArgumentException(
                    "Runtime Redis connection [{$name}] configuration must be an array.",
                );
            }

            config()->set(
                "database.redis.{$name}",
                $configuration,
            );
        }

        if ($this->app->resolved('redis')) {
            $this->app->forgetInstance(
                'redis',
            );

            Facade::clearResolvedInstance(
                'redis',
            );
        }
    }
}
