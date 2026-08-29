<?php

namespace App\Services\Admin\System\Storage;

use App\Enums\Admin\System\StorageConfigurationMode;
use App\Enums\Admin\System\StorageDriverType;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Models\Admin\System\StorageDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StorageDriverCatalogService
{
    public function __construct(
        private readonly StorageDriverRegistry $registry,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function create(array $data, User $actor): StorageDriverConfiguration
    {
        return DB::transaction(function () use ($data, $actor) {
            $type = StorageDriverType::tryFrom((string) ($data['driver'] ?? ''));

            if (! $type) {
                throw ValidationException::withMessages([
                    'driver' => 'The selected Storage driver is unavailable.',
                ]);
            }

            $normalized = $this->registry
                ->adapter($type)
                ->validateAndNormalize(
                    $data['configuration'] ?? [],
                    $data['infrastructure_connection_id'] ?? null,
                );

            $model = StorageDriverConfiguration::query()->create([
                'name' => $data['name'],
                'driver' => $type,
                ...$normalized,
                'is_enabled' => $data['is_enabled'] ?? true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->log(
                'create',
                $model,
                null,
                $this->safe($model),
                $actor,
            );

            return $model;
        });
    }

    public function update(
        StorageDriverConfiguration $model,
        array $data,
        User $actor,
    ): StorageDriverConfiguration {
        return DB::transaction(function () use ($model, $data, $actor) {
            $settings = $this->lockSettings();

            $model = StorageDriverConfiguration::withTrashed()
                ->whereKey($model->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardInactive($model, $settings);

            if (
                isset($data['driver'])
                && $data['driver'] !== $model->getRawOriginal('driver')
            ) {
                throw ValidationException::withMessages([
                    'driver' => 'The Storage driver cannot be changed.',
                ]);
            }

            $normalized = $this->registry
                ->adapter($model->driver)
                ->validateAndNormalize(
                    $data['configuration'] ?? [],
                    $data['infrastructure_connection_id']
                    ?? $model->infrastructure_connection_id,
                );

            $before = $this->safe($model);

            $model->update([
                'name' => $data['name'],
                ...$normalized,
                'is_enabled' => $data['is_enabled'] ?? $model->is_enabled,
                'updated_by' => $actor->id,
            ]);

            $this->log(
                'update',
                $model,
                $before,
                $this->safe($model->refresh()),
                $actor,
            );

            return $model;
        });
    }

    public function setEnabled(
        StorageDriverConfiguration $model,
        bool $enabled,
        User $actor,
    ): StorageDriverConfiguration {
        return $this->mutate(
            $model,
            $actor,
            $enabled ? 'enable' : 'disable',
            fn (StorageDriverConfiguration $locked) => $locked->update([
                'is_enabled' => $enabled,
                'updated_by' => $actor->id,
            ]),
        );
    }

    public function archive(
        StorageDriverConfiguration $model,
        User $actor,
    ): void {
        $this->mutate(
            $model,
            $actor,
            'archive',
            fn (StorageDriverConfiguration $locked) => $locked->delete(),
        );
    }

    public function restore(int $id, User $actor): StorageDriverConfiguration
    {
        return DB::transaction(function () use ($id, $actor) {
            $model = StorageDriverConfiguration::onlyTrashed()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->registry
                ->adapter($model->driver)
                ->validateAndNormalize(
                    $model->configuration ?? [],
                    $model->infrastructure_connection_id,
                );

            $model->restore();

            $model->update([
                'is_enabled' => false,
                'updated_by' => $actor->id,
            ]);

            $this->log(
                'restore',
                $model,
                null,
                $this->safe($model),
                $actor,
            );

            return $model;
        });
    }

    public function forceDelete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor) {
            $settings = $this->lockSettings();

            $model = StorageDriverConfiguration::onlyTrashed()
                ->whereKey($id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardInactive($model, $settings);

            $before = $this->safe($model);

            $model->forceDelete();

            $this->audit->log(
                'storage_driver_configurations',
                'force_delete',
                StorageDriverConfiguration::class,
                $id,
                $before,
                null,
                actor: $actor,
            );
        });
    }

    public function safe(StorageDriverConfiguration $model): array
    {
        $deletedAt = $model->getAttribute('deleted_at');
        $configuration = $model->getAttribute('configuration');

        return [
            'id' => $model->id,
            'name' => $model->name,
            'driver' => $model->getRawOriginal('driver'),
            'infrastructure_connection_id' => $model->infrastructure_connection_id,
            'configuration' => is_array($configuration)
                ? $configuration
                : [],
            'is_enabled' => $model->is_enabled,
            'archived_at' => $deletedAt instanceof \DateTimeInterface
                ? $deletedAt->format(DATE_ATOM)
                : null,
        ];
    }

    private function mutate(
        StorageDriverConfiguration $model,
        User $actor,
        string $action,
        callable $callback,
    ): StorageDriverConfiguration {
        return DB::transaction(function () use (
            $model,
            $actor,
            $action,
            $callback,
        ) {
            $settings = $this->lockSettings();

            $locked = StorageDriverConfiguration::withTrashed()
                ->whereKey($model->id)
                ->lockForUpdate()
                ->firstOrFail();

            $this->guardInactive($locked, $settings);

            $before = $this->safe($locked);

            $callback($locked);

            $this->log(
                $action,
                $locked,
                $before,
                $this->safe($locked),
                $actor,
            );

            return $locked;
        });
    }

    private function lockSettings(): ?StorageDriverSettings
    {
        return StorageDriverSettings::query()
            ->whereKey(StorageDriverSettings::SINGLETON_ID)
            ->lockForUpdate()
            ->first();
    }

    private function guardInactive(
        StorageDriverConfiguration $model,
        ?StorageDriverSettings $settings,
    ): void {
        if (
            $settings?->getRawOriginal('mode')
            === StorageConfigurationMode::Managed->value
            && $settings->active_configuration_id === $model->id
        ) {
            throw ValidationException::withMessages([
                'configuration' => 'The active managed Storage configuration cannot be mutated.',
            ]);
        }
    }

    private function log(
        string $action,
        StorageDriverConfiguration $model,
        ?array $before,
        ?array $after,
        User $actor,
    ): void {
        $this->audit->log(
            'storage_driver_configurations',
            $action,
            StorageDriverConfiguration::class,
            $model->id,
            $before,
            $after,
            actor: $actor,
        );
    }
}
