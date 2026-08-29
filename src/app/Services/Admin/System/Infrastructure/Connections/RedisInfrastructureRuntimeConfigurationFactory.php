<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Exceptions\Admin\System\Infrastructure\InvalidRedisInfrastructureRuntimeConfigurationException;
use App\Models\Admin\System\InfrastructureConnection;
use Illuminate\Support\Facades\Validator;

class RedisInfrastructureRuntimeConfigurationFactory
{
    public function make(
        InfrastructureConnection $connection,
    ): array {
        if (
            $connection->type !==
            InfrastructureConnectionType::Redis
        ) {
            throw new InvalidRedisInfrastructureRuntimeConfigurationException(
                "Infrastructure connection [{$connection->id}] is not Redis.",
            );
        }

        if (
            $connection->source !==
            InfrastructureConnectionSource::Managed
        ) {
            throw new InvalidRedisInfrastructureRuntimeConfigurationException(
                "Infrastructure connection [{$connection->id}] is not managed by SimpleDesk.",
            );
        }

        if ($connection->trashed()) {
            throw new InvalidRedisInfrastructureRuntimeConfigurationException(
                "Infrastructure connection [{$connection->id}] is archived.",
            );
        }

        if (! $connection->is_enabled) {
            throw new InvalidRedisInfrastructureRuntimeConfigurationException(
                "Infrastructure connection [{$connection->id}] is disabled.",
            );
        }

        $validator =
            Validator::make(
                [
                    'configuration' => $connection->configuration ?? [],
                ],
                [
                    'configuration.host' => [
                        'required',
                        'string',
                        'max:255',
                    ],

                    'configuration.port' => [
                        'required',
                        'integer',
                        'between:1,65535',
                    ],

                    'configuration.database' => [
                        'required',
                        'integer',
                        'min:0',
                    ],

                    'configuration.username' => [
                        'nullable',
                        'string',
                        'max:255',
                    ],

                    'configuration.tls' => [
                        'required',
                        'boolean',
                    ],

                    'configuration.connect_timeout_seconds' => [
                        'required',
                        'numeric',
                        'between:0.1,60',
                    ],
                ],
            );

        if ($validator->fails()) {
            throw new InvalidRedisInfrastructureRuntimeConfigurationException(
                "Infrastructure connection [{$connection->id}] has invalid Redis runtime configuration: "
                .$validator
                    ->errors()
                    ->first(),
            );
        }

        $configuration =
            $validator
                ->validated()[
            'configuration'
            ];

        $credentials =
            $connection->secrets();

        $runtime = [
            'url' => null,

            'host' => $configuration[
                'host'
                ],

            'port' => (int) $configuration[
                'port'
                ],

            'database' => (int) $configuration[
                'database'
                ],

            'username' => (
                $configuration[
                'username'
                ] ?? ''
            ) !== ''
                    ? $configuration[
                'username'
                ]
                    : null,

            'password' => $credentials[
                'password'
                ] ?? null,

            'timeout' => (float) $configuration[
                'connect_timeout_seconds'
                ],

            'scheme' => $configuration[
                'tls'
                ]
                    ? 'tls'
                    : 'tcp',
        ];

        $defaultRedis =
            (array) config(
                'database.redis.default',
                [],
            );

        foreach (
            [
                'max_retries',
                'backoff_algorithm',
                'backoff_base',
                'backoff_cap',
            ] as $option
        ) {
            if (
                array_key_exists(
                    $option,
                    $defaultRedis,
                )
            ) {
                $runtime[$option] =
                    $defaultRedis[
                    $option
                    ];
            }
        }

        return $runtime;
    }
}
