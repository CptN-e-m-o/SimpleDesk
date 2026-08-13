<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class RedisInfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    public function __construct(
        private readonly Application $app,
    ) {}

    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Redis;
    }

    public function definition(): InfrastructureConnectionDefinitionData
    {
        $reserved = [
            'client',
            'options',
            'clusters',
        ];

        $connections =
            array_values(
                array_filter(
                    array_keys(
                        (array) config(
                            'database.redis',
                            [],
                        ),
                    ),
                    fn (string $key): bool => ! in_array(
                        $key,
                        $reserved,
                        true,
                    )
                        && is_array(
                            config(
                                "database.redis.{$key}",
                            ),
                        ),
                ),
            );

        return new InfrastructureConnectionDefinitionData(
            $this->type(),
            'Redis',
            'Redis server or an existing Laravel Redis connection.',
            [
                InfrastructureConnectionSource::Managed,
                InfrastructureConnectionSource::Deployment,
            ],
            true,
            [
                'deployment_connections' => $connections,
            ],
        );
    }

    public function validateAndNormalize(
        array $configuration,
        array $credentials,
        string $source,
    ): array {
        $sourceEnum =
            InfrastructureConnectionSource::tryFrom(
                $source,
            );

        if (
            ! $sourceEnum
            || ! in_array(
                $sourceEnum,
                $this->definition()->sources,
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'source' => 'The selected connection source is not supported.',
            ]);
        }

        if (
            $sourceEnum ===
            InfrastructureConnectionSource::Deployment
        ) {
            return $this->validateDeployment(
                $configuration,
            );
        }

        return $this->validateManaged(
            $configuration,
            $credentials,
        );
    }

    public function secretFields(): array
    {
        return [
            'password',
        ];
    }

    public function publicRepresentation(
        InfrastructureConnection $connection,
    ): array {
        return [
            'configuration' => $connection->configuration ?? [],

            'credential_flags' => [
                'password_configured' => isset(
                    $connection->secrets()[
                    'password'
                    ],
                ),
            ],
        ];
    }

    public function test(
        InfrastructureConnection $connection,
    ): InfrastructureHealthResultData {
        $started = hrtime(true);

        $redis = null;

        $key =
            'simpledesk:health:'
            .Str::random(32);

        $expected =
            Str::random(32);

        try {
            if (
                $connection->source ===
                InfrastructureConnectionSource::Deployment
            ) {
                $redis =
                    Redis::connection(
                        (string) (
                            $connection
                                ->configuration[
                            'connection_name'
                            ]
                            ?? ''
                        ),
                    );
            } else {
                $configuration =
                    $connection->configuration ?? [];

                $secrets =
                    $connection->secrets();

                $scheme =
                    ($configuration['tls'] ?? false)
                        ? 'tls'
                        : 'tcp';

                $manager =
                    new RedisManager(
                        $this->app,
                        (string) config(
                            'database.redis.client',
                            'phpredis',
                        ),
                        [
                            'temporary' => [
                                'url' => null,
                                'host' => $configuration[
                                    'host'
                                    ],
                                'port' => $configuration[
                                    'port'
                                    ],
                                'database' => $configuration[
                                    'database'
                                    ],
                                'username' => $configuration[
                                    'username'
                                    ] ?: null,
                                'password' => $secrets[
                                    'password'
                                    ] ?? null,
                                'timeout' => $configuration[
                                    'connect_timeout_seconds'
                                    ],
                                'read_timeout' => $configuration[
                                    'connect_timeout_seconds'
                                    ],
                                'scheme' => $scheme,
                            ],
                        ],
                    );

                $redis =
                    $manager->connection(
                        'temporary',
                    );
            }

            $redis->ping();

            $redis->setex(
                $key,
                30,
                $expected,
            );

            $actual =
                (string) $redis->get(
                    $key,
                );

            if ($actual !== $expected) {
                throw new RuntimeException(
                    'Redis write/read verification failed.',
                );
            }

            /*
             * Delete является частью health-check,
             * а не просто best-effort cleanup.
             *
             * Раньше Healthy возвращался до проверки
             * удаления ключа, хотя details утверждал,
             * что операция delete успешно выполнена.
             */
            $deleted =
                (int) $redis->del(
                    $key,
                );

            if ($deleted !== 1) {
                throw new RuntimeException(
                    'Redis delete verification failed.',
                );
            }

            $key = null;

            $latency =
                $this->elapsedMilliseconds(
                    $started,
                );

            return new InfrastructureHealthResultData(
                InfrastructureHealthStatus::Healthy,
                $latency,
                'Redis connection verified successfully.',
                [
                    'operations' => [
                        'ping',
                        'write',
                        'read',
                        'delete',
                    ],
                ],
            );
        } catch (Throwable $exception) {
            return new InfrastructureHealthResultData(
                $this->failureStatus(
                    $exception,
                ),
                $this->elapsedMilliseconds(
                    $started,
                ),
                $exception->getMessage(),
            );
        } finally {
            /*
             * Здесь cleanup нужен только в случае,
             * если health-check оборвался после SETEX,
             * но до успешного DELETE.
             */
            if (
                $redis !== null
                && $key !== null
            ) {
                try {
                    $redis->del($key);
                } catch (Throwable) {

                }
            }
        }
    }

    private function validateDeployment(
        array $configuration,
    ): array {
        $names =
            $this->definition()
                ->options[
            'deployment_connections'
            ];

        /*
         * Валидируем именно вложенную структуру,
         * чтобы Laravel возвращал frontend-ключ:
         *
         * configuration.connection_name
         */
        $validated =
            Validator::make(
                [
                    'configuration' => $configuration,
                ],
                [
                    'configuration.connection_name' => [
                        'required',
                        'string',
                        Rule::in($names),
                    ],
                ],
            )->validate();

        return [
            'configuration' => [
                'connection_name' => $validated[
                    'configuration'
                    ][
                    'connection_name'
                    ],
            ],
            'credentials' => [],
        ];
    }

    private function validateManaged(
        array $configuration,
        array $credentials,
    ): array {
        /*
         * Здесь намеренно сохраняем исходную
         * вложенность payload.
         *
         * Благодаря этому validation errors имеют
         * те же имена, что использует Form.tsx:
         *
         * configuration.host
         * configuration.port
         * credentials.password
         */
        $validated =
            Validator::make(
                [
                    'configuration' => $configuration,
                    'credentials' => $credentials,
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
                    'credentials.password' => [
                        'nullable',
                        'string',
                        'max:4096',
                    ],
                ],
            )->validate();

        $validatedConfiguration =
            $validated['configuration'];

        $password =
            $validated[
            'credentials'
            ][
            'password'
            ] ?? null;

        return [
            'configuration' => [
                'host' => $validatedConfiguration[
                    'host'
                    ],
                'port' => (int) $validatedConfiguration[
                    'port'
                    ],
                'database' => (int) $validatedConfiguration[
                    'database'
                    ],
                'username' => $validatedConfiguration[
                    'username'
                    ] ?? '',
                'tls' => (bool) $validatedConfiguration[
                    'tls'
                    ],
                'connect_timeout_seconds' => (float) $validatedConfiguration[
                    'connect_timeout_seconds'
                    ],
            ],

            'credentials' => $password !== null
                && $password !== ''
                    ? [
                        'password' => $password,
                    ]
                    : [],
        ];
    }

    private function elapsedMilliseconds(
        int $started,
    ): int {
        return (int) round(
            (hrtime(true) - $started)
            / 1_000_000,
        );
    }

    private function failureStatus(
        Throwable $exception,
    ): InfrastructureHealthStatus {
        $message =
            strtolower(
                $exception->getMessage(),
            );

        if (
            str_contains(
                $message,
                'extension',
            )
            || str_contains(
                $message,
                'class',
            )
        ) {
            return InfrastructureHealthStatus::Unavailable;
        }

        return InfrastructureHealthStatus::Unhealthy;
    }
}
