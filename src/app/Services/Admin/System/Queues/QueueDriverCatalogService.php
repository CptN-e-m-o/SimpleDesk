<?php

namespace App\Services\Admin\System\Queues;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\QueueConfigurationMode;
use App\Enums\Admin\System\QueueDriverType;
use App\Exceptions\Admin\System\Queues\ActiveQueueDriverConfigurationMutationException;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QueueDriverCatalogService
{
    public function __construct(
        private readonly QueueDriverRegistry $registry,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function create(
        array $data,
        User $actor,
    ): QueueDriverConfiguration {
        return DB::transaction(
            function () use (
                $data,
                $actor,
            ): QueueDriverConfiguration {
                $type = QueueDriverType::tryFrom(
                    (string) (
                        $data['driver']
                        ?? ''
                    ),
                );

                if (! $type) {
                    throw ValidationException::withMessages([
                        'driver' => 'Unknown queue driver.',
                    ]);
                }

                $this->assertRegistered(
                    $type,
                );

                $infrastructure = $this
                    ->resolveInfrastructureConnection(
                        $type,
                        $data[
                        'infrastructure_connection_id'
                        ] ?? null,
                    );

                $normalized = $this->normalize(
                    $type,
                    $data['configuration'] ?? [],
                );

                $model = QueueDriverConfiguration::query()
                    ->create([
                        'name' => $data['name'],
                        'driver' => $type,

                        'infrastructure_connection_id' => $infrastructure?->id,

                        'configuration' => $normalized,

                        'is_enabled' => $data[
                            'is_enabled'
                            ] ?? true,

                        'created_by' => $actor->id,
                        'updated_by' => $actor->id,
                    ]);

                $this->log(
                    action: 'create',
                    model: $model,
                    before: null,
                    after: $this->safe(
                        $model,
                    ),
                    actor: $actor,
                );

                return $model;
            },
        );
    }

    public function update(
        QueueDriverConfiguration $model,
        array $data,
        User $actor,
    ): QueueDriverConfiguration {
        return DB::transaction(
            function () use (
                $model,
                $data,
                $actor,
            ): QueueDriverConfiguration {
                $settings = $this->lockSettings();

                $model = QueueDriverConfiguration::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $model->id,
                    );

                $this->guardInactive(
                    $model,
                    $settings,
                );

                $this->assertRegistered(
                    $model->driver,
                );

                if (
                    isset($data['driver'])
                    && $data['driver']
                    !== $model
                        ->driver
                        ->value
                ) {
                    throw ValidationException::withMessages([
                        'driver' => 'Queue driver cannot be changed after creation.',
                    ]);
                }

                $before = $this->safe(
                    $model,
                );

                $infrastructureInput =
                    array_key_exists(
                        'infrastructure_connection_id',
                        $data,
                    )
                        ? $data[
                    'infrastructure_connection_id'
                    ]
                        : $model
                            ->infrastructure_connection_id;

                $infrastructure = $this
                    ->resolveInfrastructureConnection(
                        $model->driver,
                        $infrastructureInput,
                    );

                $model->update([
                    'name' => $data['name'],

                    'infrastructure_connection_id' => $infrastructure?->id,

                    'configuration' => $this->normalize(
                        $model->driver,
                        $data['configuration'] ?? [],
                    ),

                    'is_enabled' => $data[
                        'is_enabled'
                        ] ?? $model->is_enabled,

                    'updated_by' => $actor->id,
                ]);

                $model->refresh();

                $this->log(
                    action: 'update',
                    model: $model,
                    before: $before,
                    after: $this->safe(
                        $model,
                    ),
                    actor: $actor,
                );

                return $model;
            },
        );
    }

    public function setEnabled(
        QueueDriverConfiguration $model,
        bool $enabled,
        User $actor,
    ): QueueDriverConfiguration {
        return DB::transaction(
            function () use (
                $model,
                $enabled,
                $actor,
            ): QueueDriverConfiguration {
                $settings = $this->lockSettings();

                $model = QueueDriverConfiguration::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $model->id,
                    );

                if (! $enabled) {
                    $this->guardInactive(
                        $model,
                        $settings,
                    );
                }

                if (
                    $model->is_enabled
                    === $enabled
                ) {
                    return $model;
                }

                $before = $this->safe(
                    $model,
                );

                $model->update([
                    'is_enabled' => $enabled,
                    'updated_by' => $actor->id,
                ]);

                $model->refresh();

                $this->log(
                    action: $enabled
                        ? 'enable'
                        : 'disable',
                    model: $model,
                    before: $before,
                    after: $this->safe(
                        $model,
                    ),
                    actor: $actor,
                );

                return $model;
            },
        );
    }

    public function archive(
        QueueDriverConfiguration $model,
        User $actor,
    ): void {
        DB::transaction(
            function () use (
                $model,
                $actor,
            ): void {
                $settings = $this->lockSettings();

                $model = QueueDriverConfiguration::query()
                    ->lockForUpdate()
                    ->findOrFail(
                        $model->id,
                    );

                $this->guardInactive(
                    $model,
                    $settings,
                );

                $before = $this->safe(
                    $model,
                );

                $model->update([
                    'is_enabled' => false,
                    'updated_by' => $actor->id,
                ]);

                $model->delete();

                $this->log(
                    action: 'archive',
                    model: $model,
                    before: $before,
                    after: $this->safe(
                        $model,
                    ),
                    actor: $actor,
                );
            },
        );
    }

    public function restore(
        int $id,
        User $actor,
    ): QueueDriverConfiguration {
        return DB::transaction(
            function () use (
                $id,
                $actor,
            ): QueueDriverConfiguration {
                $model = QueueDriverConfiguration::onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail(
                        $id,
                    );

                $before = $this->safe(
                    $model,
                );

                $model->restore();

                $model->update([
                    'is_enabled' => false,
                    'updated_by' => $actor->id,
                ]);

                $model->refresh();

                $this->log(
                    action: 'restore',
                    model: $model,
                    before: $before,
                    after: $this->safe(
                        $model,
                    ),
                    actor: $actor,
                );

                return $model;
            },
        );
    }

    public function forceDelete(
        int $id,
        User $actor,
    ): void {
        DB::transaction(
            function () use (
                $id,
                $actor,
            ): void {
                $settings = $this->lockSettings();

                $model = QueueDriverConfiguration::onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail(
                        $id,
                    );

                $this->guardInactive(
                    $model,
                    $settings,
                );

                $before = $this->safe(
                    $model,
                );

                $model->forceDelete();

                $this->log(
                    action: 'force_delete',
                    model: $model,
                    before: $before,
                    after: null,
                    actor: $actor,
                );
            },
        );
    }

    public function safe(
        QueueDriverConfiguration $model,
    ): array {
        $model->loadMissing([
            'latestHealthCheck',
            'infrastructureConnection',
        ]);

        $health =
            $model->latestHealthCheck;

        $configuration =
            $model->configuration ?? [];

        unset(
            $configuration[
            'infrastructure_connection_id'
            ],
        );

        $data = [
            'id' => $model->id,
            'name' => $model->name,

            'driver' => $model
                ->driver
                ->value,

            'infrastructure_connection_id' => $model->infrastructure_connection_id,

            'configuration' => $configuration,

            'is_enabled' => $model->is_enabled,

            'deleted_at' => $model
                ->deleted_at
                ?->toIso8601String(),

            'created_at' => $model
                ->created_at
                ?->toIso8601String(),

            'updated_at' => $model
                ->updated_at
                ?->toIso8601String(),

            'is_active' => $this->isActive(
                $model,
            ),

            'latest_health_check' => $health
                ? [
                    'status' => $health
                        ->status
                        ->value,

                    'latency_ms' => $health
                        ->latency_ms,

                    'message' => $health
                        ->message,

                    'details' => $health
                        ->details,

                    'tested_by' => $health
                        ->tested_by,

                    'created_at' => $health
                        ->created_at
                        ?->toIso8601String(),
                ]
                : null,
        ];

        if (
            $model->driver ===
            QueueDriverType::Redis
        ) {
            $infrastructure =
                $model->infrastructureConnection;

            $data[
            'infrastructure_connection'
            ] = $infrastructure
                ? [
                    'id' => $infrastructure->id,

                    'name' => $infrastructure->name,

                    'type' => $infrastructure
                        ->type
                        ->value,

                    'source' => $infrastructure
                        ->source
                        ->value,

                    'is_enabled' => $infrastructure->is_enabled,

                    'deleted_at' => $infrastructure
                        ->deleted_at
                        ?->toIso8601String(),
                ]
                : null;
        }

        return $data;
    }

    private function normalize(
        QueueDriverType $type,
        array $configuration,
    ): array {
        unset(
            $configuration[
            'infrastructure_connection_id'
            ],
        );

        try {
            return $this
                ->registry
                ->adapter(
                    $type,
                )
                ->validateAndNormalize(
                    $configuration,
                );
        } catch (
            ValidationException $exception
        ) {
            $messages = [];

            foreach (
                $exception->errors() as $key => $values
            ) {
                $messages[
                str_starts_with(
                    $key,
                    'configuration.',
                )
                    ? $key
                    : 'configuration.'.$key
                ] = $values;
            }

            throw ValidationException::withMessages(
                $messages,
            );
        }
    }

    private function resolveInfrastructureConnection(
        QueueDriverType $type,
        mixed $connectionId,
    ): ?InfrastructureConnection {
        if (
            $type !==
            QueueDriverType::Redis
        ) {
            if (
                $connectionId !== null
                && $connectionId !== ''
            ) {
                throw ValidationException::withMessages([
                    'infrastructure_connection_id' => 'Only Redis queue configurations may reference an infrastructure connection.',
                ]);
            }

            return null;
        }

        $normalizedId = filter_var(
            $connectionId,
            FILTER_VALIDATE_INT,
            [
                'options' => [
                    'min_range' => 1,
                ],
            ],
        );

        if ($normalizedId === false) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'A Redis infrastructure connection is required.',
            ]);
        }

        $connection = InfrastructureConnection::withTrashed()
            ->find(
                $normalizedId,
            );

        if (! $connection) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The selected Redis infrastructure connection does not exist.',
            ]);
        }

        if ($connection->trashed()) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The selected Redis infrastructure connection is archived.',
            ]);
        }

        if (
            $connection->type !==
            InfrastructureConnectionType::Redis
        ) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The selected infrastructure connection is not Redis.',
            ]);
        }

        if (! $connection->is_enabled) {
            throw ValidationException::withMessages([
                'infrastructure_connection_id' => 'The selected Redis infrastructure connection is disabled.',
            ]);
        }

        return $connection;
    }

    private function assertRegistered(
        QueueDriverType $type,
    ): void {
        if (
            ! in_array(
                $type,
                $this
                    ->registry
                    ->registeredTypes(),
                true,
            )
        ) {
            throw ValidationException::withMessages([
                'driver' => "Queue driver [{$type->value}] is not currently available.",
            ]);
        }
    }

    private function isActive(
        QueueDriverConfiguration $model,
    ): bool {
        return QueueDriverSettings::query()
            ->whereKey(
                QueueDriverSettings::SINGLETON_ID,
            )
            ->where(
                'mode',
                QueueConfigurationMode::Managed->value,
            )
            ->where(
                'active_configuration_id',
                $model->id,
            )
            ->exists();
    }

    private function lockSettings(): ?QueueDriverSettings
    {
        return QueueDriverSettings::query()
            ->whereKey(
                QueueDriverSettings::SINGLETON_ID,
            )
            ->lockForUpdate()
            ->first();
    }

    private function guardInactive(
        QueueDriverConfiguration $model,
        ?QueueDriverSettings $settings,
    ): void {
        if (
            $settings !== null
            && $settings->mode ===
            QueueConfigurationMode::Managed
            && $settings
                ->active_configuration_id
            === $model->id
        ) {
            throw new ActiveQueueDriverConfigurationMutationException(
                'The active managed queue configuration cannot be changed until restart orchestration is available.',
            );
        }
    }

    private function log(
        string $action,
        QueueDriverConfiguration $model,
        ?array $before,
        ?array $after,
        User $actor,
    ): void {
        $this->audit->log(
            area: 'queue_driver_configurations',
            action: $action,
            subjectType: QueueDriverConfiguration::class,
            subjectId: $model->id,
            before: $before,
            after: $after,
            actor: $actor,
        );
    }
}
