<?php

namespace App\Services\Admin\System\Queues;

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
                $type =
                    QueueDriverType::tryFrom(
                        (string) (
                            $data['driver']
                            ?? ''
                        ),
                    );

                if (! $type) {
                    throw ValidationException::withMessages([
                        'driver' =>
                            'Unknown queue driver.',
                    ]);
                }

                $this->assertRegistered(
                    $type,
                );

                $normalized =
                    $this->normalize(
                        $type,
                        $data[
                        'configuration'
                        ] ?? [],
                    );

                $model =
                    QueueDriverConfiguration::query()
                        ->create([
                            'name' =>
                                $data['name'],

                            'driver' =>
                                $type,

                            'configuration' =>
                                $normalized,

                            'is_enabled' =>
                                $data[
                                'is_enabled'
                                ] ?? true,

                            'created_by' =>
                                $actor->id,

                            'updated_by' =>
                                $actor->id,
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
                $model =
                    QueueDriverConfiguration::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $model->id,
                        );

                $this->guardInactive(
                    $model,
                );

                $this->assertRegistered(
                    $model->driver,
                );

                if (
                    isset(
                        $data['driver'],
                    )
                    && $data['driver']
                    !== $model
                        ->driver
                        ->value
                ) {
                    throw ValidationException::withMessages([
                        'driver' =>
                            'Queue driver cannot be changed after creation.',
                    ]);
                }

                $before =
                    $this->safe(
                        $model,
                    );

                $model->update([
                    'name' =>
                        $data['name'],

                    'configuration' =>
                        $this->normalize(
                            $model->driver,
                            $data[
                            'configuration'
                            ] ?? [],
                        ),

                    'is_enabled' =>
                        $data[
                        'is_enabled'
                        ] ?? $model->is_enabled,

                    'updated_by' =>
                        $actor->id,
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
                $model =
                    QueueDriverConfiguration::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $model->id,
                        );

                if (! $enabled) {
                    $this->guardInactive(
                        $model,
                    );
                }

                if (
                    $model->is_enabled
                    === $enabled
                ) {
                    return $model;
                }

                $before =
                    $this->safe(
                        $model,
                    );

                $model->update([
                    'is_enabled' =>
                        $enabled,

                    'updated_by' =>
                        $actor->id,
                ]);

                $model->refresh();

                $this->log(
                    action:
                    $enabled
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
                $model =
                    QueueDriverConfiguration::query()
                        ->lockForUpdate()
                        ->findOrFail(
                            $model->id,
                        );

                $this->guardInactive(
                    $model,
                );

                $before =
                    $this->safe(
                        $model,
                    );

                $model->update([
                    'is_enabled' =>
                        false,

                    'updated_by' =>
                        $actor->id,
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
                $model =
                    QueueDriverConfiguration::onlyTrashed()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id,
                        );

                $before =
                    $this->safe(
                        $model,
                    );

                $model->restore();

                $model->update([
                    'is_enabled' =>
                        false,

                    'updated_by' =>
                        $actor->id,
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
                $model =
                    QueueDriverConfiguration::onlyTrashed()
                        ->lockForUpdate()
                        ->findOrFail(
                            $id,
                        );

                $this->guardInactive(
                    $model,
                );

                $before =
                    $this->safe(
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
        $model->loadMissing(
            'latestHealthCheck',
        );

        $health =
            $model
                ->latestHealthCheck;

        $data = [
            'id' =>
                $model->id,

            'name' =>
                $model->name,

            'driver' =>
                $model
                    ->driver
                    ->value,

            'configuration' =>
                $model->configuration
                ?? [],

            'is_enabled' =>
                $model->is_enabled,

            'deleted_at' =>
                $model
                    ->deleted_at
                    ?->toIso8601String(),

            'created_at' =>
                $model
                    ->created_at
                    ?->toIso8601String(),

            'updated_at' =>
                $model
                    ->updated_at
                    ?->toIso8601String(),

            'is_active' =>
                $this->isActive(
                    $model,
                ),

            'latest_health_check' =>
                $health
                    ? [
                    'status' =>
                        $health
                            ->status
                            ->value,

                    'latency_ms' =>
                        $health
                            ->latency_ms,

                    'message' =>
                        $health
                            ->message,

                    'details' =>
                        $health
                            ->details,

                    'tested_by' =>
                        $health
                            ->tested_by,

                    'created_at' =>
                        $health
                            ->created_at
                            ?->toIso8601String(),
                ]
                    : null,
        ];

        if (
            $model->driver ===
            QueueDriverType::Redis
        ) {
            $id =
                $model
                    ->configuration[
                'infrastructure_connection_id'
                ]
                ?? null;

            $infrastructure =
                $id
                    ? InfrastructureConnection::withTrashed()
                    ->find(
                        $id,
                    )
                    : null;

            $data[
            'infrastructure_connection'
            ] =
                $infrastructure
                    ? [
                    'id' =>
                        $infrastructure->id,

                    'name' =>
                        $infrastructure->name,

                    'type' =>
                        $infrastructure
                            ->type
                            ->value,

                    'source' =>
                        $infrastructure
                            ->source
                            ->value,

                    'is_enabled' =>
                        $infrastructure
                            ->is_enabled,

                    'deleted_at' =>
                        $infrastructure
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
                $exception->errors()
                as $key => $values
            ) {
                $messages[
                str_starts_with(
                    $key,
                    'configuration.',
                )
                    ? $key
                    : 'configuration.'
                    .$key
                ] = $values;
            }

            throw ValidationException::withMessages(
                $messages,
            );
        }
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
                'driver' =>
                    "Queue driver [{$type->value}] is not currently available.",
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

    private function guardInactive(
        QueueDriverConfiguration $model,
    ): void {
        $settings =
            QueueDriverSettings::query()
                ->whereKey(
                    QueueDriverSettings::SINGLETON_ID,
                )
                ->lockForUpdate()
                ->first();

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
            area:
            'queue_driver_configurations',

            action:
            $action,

            subjectType:
            QueueDriverConfiguration::class,

            subjectId:
            $model->id,

            before:
            $before,

            after:
            $after,

            actor:
            $actor,
        );
    }
}
