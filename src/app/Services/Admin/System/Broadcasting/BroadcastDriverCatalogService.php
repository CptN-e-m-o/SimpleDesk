<?php

namespace App\Services\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Enums\Admin\System\BroadcastDriverType;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class BroadcastDriverCatalogService
{
    public function __construct(private readonly BroadcastDriverRegistry $registry, private readonly SystemAuditLogger $audit) {}

    public function create(array $data, User $actor): BroadcastDriverConfiguration
    {
        return DB::transaction(function () use ($data, $actor): BroadcastDriverConfiguration {
            $type = BroadcastDriverType::tryFrom((string) ($data['driver'] ?? ''));
            if (! $type || $type === BroadcastDriverType::Ably) {
                throw ValidationException::withMessages(['driver' => 'The selected Broadcast driver is unavailable.']);
            }
            $normalized = $this->registry->adapter($type)->validateAndNormalize($data['configuration'] ?? [], $data['infrastructure_connection_id'] ?? null);
            $model = BroadcastDriverConfiguration::query()->create(['name' => $data['name'], 'driver' => $type, ...$normalized, 'is_enabled' => $data['is_enabled'] ?? true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
            $this->log('create', $model, null, $this->safe($model), $actor);

            return $model;
        });
    }

    public function update(BroadcastDriverConfiguration $model, array $data, User $actor): BroadcastDriverConfiguration
    {
        return DB::transaction(function () use ($model, $data, $actor): BroadcastDriverConfiguration {
            $settings = $this->lockSettings();
            $model = BroadcastDriverConfiguration::withTrashed()->lockForUpdate()->findOrFail($model->id);
            $this->guardInactive($model, $settings);
            if (isset($data['driver']) && $data['driver'] !== $model->driver->value) {
                throw ValidationException::withMessages(['driver' => 'The Broadcast driver cannot be changed.']);
            }
            $normalized = $this->registry->adapter($model->driver)->validateAndNormalize($data['configuration'] ?? [], $data['infrastructure_connection_id'] ?? $model->infrastructure_connection_id);
            $before = $this->safe($model);
            $model->update(['name' => $data['name'], ...$normalized, 'is_enabled' => $data['is_enabled'] ?? $model->is_enabled, 'updated_by' => $actor->id]);
            $this->log('update', $model, $before, $this->safe($model->refresh()), $actor);

            return $model;
        });
    }

    public function setEnabled(BroadcastDriverConfiguration $model, bool $enabled, User $actor): BroadcastDriverConfiguration
    {
        return $this->mutate($model, $actor, $enabled ? 'enable' : 'disable', function (BroadcastDriverConfiguration $locked) use ($enabled, $actor): void {
            $locked->update(['is_enabled' => $enabled, 'updated_by' => $actor->id]);
        });
    }

    public function archive(BroadcastDriverConfiguration $model, User $actor): void
    {
        $this->mutate($model, $actor, 'archive', function (BroadcastDriverConfiguration $locked) use ($actor): void {
            $locked->update(['updated_by' => $actor->id]);
            $locked->delete();
        });
    }

    public function restore(int $id, User $actor): BroadcastDriverConfiguration
    {
        return DB::transaction(function () use ($id, $actor): BroadcastDriverConfiguration {
            $model = BroadcastDriverConfiguration::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $connection = $model->infrastructureConnection;
            if (! $connection || $connection->trashed() || ! $connection->is_enabled) {
                throw ValidationException::withMessages(['configuration' => 'The referenced infrastructure connection is unavailable; this profile cannot be restored.']);
            }
            $model->restore();
            $model->update(['is_enabled' => false, 'updated_by' => $actor->id]);
            $this->log('restore', $model, null, $this->safe($model), $actor);

            return $model;
        });
    }

    public function forceDelete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor): void {
            $model = BroadcastDriverConfiguration::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $settings = $this->lockSettings();
            $this->guardInactive($model, $settings);
            $before = $this->safe($model);
            $subjectId = $model->id;
            $model->forceDelete();
            $this->audit->log('broadcast_driver_configurations', 'force-delete', BroadcastDriverConfiguration::class, $subjectId, $before, null, actor: $actor);
        });
    }

    public function safe(BroadcastDriverConfiguration $model): array
    {
        return ['id' => $model->id, 'name' => $model->name, 'driver' => $model->driver->value, 'infrastructure_connection_id' => $model->infrastructure_connection_id, 'configuration' => $model->configuration, 'is_enabled' => $model->is_enabled, 'archived_at' => $model->deleted_at?->toIso8601String()];
    }

    private function mutate(BroadcastDriverConfiguration $model, User $actor, string $action, callable $callback): BroadcastDriverConfiguration
    {
        return DB::transaction(function () use ($model, $actor, $action, $callback): BroadcastDriverConfiguration {
            $settings = $this->lockSettings();
            $locked = BroadcastDriverConfiguration::withTrashed()->lockForUpdate()->findOrFail($model->id);
            $this->guardInactive($locked, $settings);
            $before = $this->safe($locked);
            $callback($locked);
            $this->log($action, $locked, $before, $this->safe($locked), $actor);

            return $locked;
        });
    }

    private function lockSettings(): ?BroadcastDriverSettings
    {
        return BroadcastDriverSettings::query()->whereKey(1)->lockForUpdate()->first();
    }

    private function guardInactive(BroadcastDriverConfiguration $model, ?BroadcastDriverSettings $settings): void
    {
        if ($settings?->mode === BroadcastConfigurationMode::Managed && $settings->active_configuration_id === $model->id) {
            throw ValidationException::withMessages(['configuration' => 'The active managed Broadcast configuration cannot be mutated.']);
        }
    }

    private function log(string $action, BroadcastDriverConfiguration $model, ?array $before, ?array $after, User $actor): void
    {
        $this->audit->log('broadcast_driver_configurations', $action, BroadcastDriverConfiguration::class, $model->id, $before, $after, actor: $actor);
    }
}
