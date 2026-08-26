<?php

namespace App\Services\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use Illuminate\Validation\ValidationException;

class CacheDeploymentTargetService
{
    public function __construct(
        private readonly CacheStoreHealthProbe $probe,
    ) {}

    public function resolve(): array
    {
        $name = trim(
            (string) config(
                'simpledesk-cache.deployment.store',
                '',
            ),
        );

        $managed = trim(
            (string) config(
                'simpledesk-cache.runtime.store_name',
                'simpledesk-managed',
            ),
        );

        if ($name === '') {
            $this->reject(
                'The deployment Cache store is not configured.',
            );
        }

        if ($name === $managed) {
            $this->reject(
                'The deployment Cache store cannot use the managed runtime store name.',
            );
        }

        $store = config(
            "cache.stores.{$name}",
        );

        if (! is_array($store)) {
            $this->reject(
                "The deployment Cache store [{$name}] no longer exists.",
            );
        }

        $driver = trim(
            (string) ($store['driver'] ?? ''),
        );

        if ($driver === '') {
            $this->reject(
                "The deployment Cache store [{$name}] does not define a driver.",
            );
        }

        $this->validateStructure(
            storeName: $name,
            driver: $driver,
            store: $store,
        );

        return [
            'store' => $name,
            'driver' => $driver,
            'configuration' => $store,
        ];
    }

    public function test(
        ?array $target = null,
    ): CacheHealthResultData {
        $target ??= $this->resolve();

        return $this->probe->test(
            store: $target['configuration'],
            details: [
                'store' => $target['store'],
                'driver' => $target['driver'],
            ],
        );
    }

    public function safeTarget(): array
    {
        try {
            $target = $this->resolve();

            return [
                'store' => $target['store'],
                'driver' => $target['driver'],
                'available' => true,
            ];
        } catch (ValidationException $exception) {
            return [
                'store' => config(
                    'simpledesk-cache.deployment.store',
                ),
                'driver' => null,
                'available' => false,
                'message' => $this->validationMessage(
                    $exception,
                ),
            ];
        }
    }

    private function validateStructure(
        string $storeName,
        string $driver,
        array $store,
    ): void {
        match ($driver) {
            'database' => $this->validateDatabaseStore(
                $storeName,
                $store,
            ),
            'file' => $this->validateFileStore(
                $storeName,
                $store,
            ),
            'redis' => $this->validateRedisStore(
                $storeName,
                $store,
            ),
            'memcached' => $this->validateMemcachedStore(
                $storeName,
                $store,
            ),
            'dynamodb' => $this->validateDynamoDbStore(
                $storeName,
                $store,
            ),
            'octane' => $this->validateOctaneStore(
                $storeName,
            ),
            'array' => $this->reject(
                "The deployment Cache store [{$storeName}] uses the process-local array driver, which cannot provide cross-process Cache coordination.",
            ),
            'null' => $this->reject(
                "The deployment Cache store [{$storeName}] uses the null driver, which does not persist Cache state.",
            ),
            'failover' => $this->reject(
                "The deployment Cache store [{$storeName}] uses the failover driver, which is not supported by SimpleDesk Cache management yet.",
            ),
            default => $this->reject(
                "The deployment Cache store [{$storeName}] uses an unsupported driver [{$driver}].",
            ),
        };
    }

    private function validateDatabaseStore(
        string $storeName,
        array $store,
    ): void {
        $connection = trim(
            (string) (
                $store['connection']
                ?? config('database.default', '')
            ),
        );

        if (
            $connection === ''
            || ! is_array(
                config(
                    "database.connections.{$connection}",
                ),
            )
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] references an unavailable database connection.",
            );
        }

        $table = trim(
            (string) ($store['table'] ?? ''),
        );

        if ($table === '') {
            $this->reject(
                "The deployment Cache store [{$storeName}] does not define a cache table.",
            );
        }

        $lockConnection = trim(
            (string) (
                $store['lock_connection']
                ?? $connection
            ),
        );

        if (
            $lockConnection === ''
            || ! is_array(
                config(
                    "database.connections.{$lockConnection}",
                ),
            )
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] references an unavailable lock database connection.",
            );
        }

        $lockTable = trim(
            (string) (
                $store['lock_table']
                ?? 'cache_locks'
            ),
        );

        if ($lockTable === '') {
            $this->reject(
                "The deployment Cache store [{$storeName}] has an invalid lock table.",
            );
        }
    }

    private function validateFileStore(
        string $storeName,
        array $store,
    ): void {
        if (
            trim(
                (string) ($store['path'] ?? ''),
            ) === ''
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] does not define a filesystem path.",
            );
        }

        if (
            isset($store['lock_path'])
            && trim(
                (string) $store['lock_path'],
            ) === ''
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] has an invalid lock path.",
            );
        }
    }

    private function validateRedisStore(
        string $storeName,
        array $store,
    ): void {
        $connection = trim(
            (string) (
                $store['connection']
                ?? 'default'
            ),
        );

        if (
            $connection === ''
            || ! is_array(
                config(
                    "database.redis.{$connection}",
                ),
            )
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] references an unavailable Redis connection.",
            );
        }

        $lockConnection = trim(
            (string) (
                $store['lock_connection']
                ?? $connection
            ),
        );

        if (
            $lockConnection === ''
            || ! is_array(
                config(
                    "database.redis.{$lockConnection}",
                ),
            )
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] references an unavailable Redis lock connection.",
            );
        }
    }

    private function validateMemcachedStore(
        string $storeName,
        array $store,
    ): void {
        if (! class_exists('Memcached')) {
            $this->reject(
                "The deployment Cache store [{$storeName}] requires the PHP Memcached extension, which is unavailable.",
            );
        }

        $servers = $store['servers'] ?? null;

        if (! is_array($servers) || $servers === []) {
            $this->reject(
                "The deployment Cache store [{$storeName}] does not define any Memcached servers.",
            );
        }

        foreach ($servers as $server) {
            if (
                ! is_array($server)
                || trim(
                    (string) ($server['host'] ?? ''),
                ) === ''
                || filter_var(
                    $server['port'] ?? null,
                    FILTER_VALIDATE_INT,
                    [
                        'options' => [
                            'min_range' => 1,
                            'max_range' => 65535,
                        ],
                    ],
                ) === false
            ) {
                $this->reject(
                    "The deployment Cache store [{$storeName}] contains an invalid Memcached server definition.",
                );
            }
        }
    }

    private function validateDynamoDbStore(
        string $storeName,
        array $store,
    ): void {
        if (
            ! class_exists(
                'Aws\\DynamoDb\\DynamoDbClient',
            )
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] requires the AWS SDK, which is unavailable.",
            );
        }

        if (
            trim(
                (string) ($store['region'] ?? ''),
            ) === ''
            || trim(
                (string) ($store['table'] ?? ''),
            ) === ''
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] has incomplete DynamoDB configuration.",
            );
        }
    }

    private function validateOctaneStore(
        string $storeName,
    ): void {
        if (
            ! class_exists(
                'Laravel\\Octane\\OctaneServiceProvider',
            )
        ) {
            $this->reject(
                "The deployment Cache store [{$storeName}] requires Laravel Octane, which is unavailable.",
            );
        }
    }

    private function validationMessage(
        ValidationException $exception,
    ): string {
        foreach ($exception->errors() as $messages) {
            foreach ($messages as $message) {
                if (
                    is_string($message)
                    && trim($message) !== ''
                ) {
                    return trim($message);
                }
            }
        }

        return 'The deployment Cache store is invalid.';
    }

    private function reject(
        string $message,
    ): never {
        throw ValidationException::withMessages([
            'activation' => $message,
        ]);
    }
}
