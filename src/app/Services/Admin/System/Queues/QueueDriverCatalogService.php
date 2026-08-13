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
    public function __construct(private readonly QueueDriverRegistry $registry, private readonly SystemAuditLogger $audit) {}

    public function create(array $data, User $actor): QueueDriverConfiguration
    {
        return DB::transaction(function () use ($data, $actor) {
            $type = QueueDriverType::tryFrom((string) $data['driver']);
            if (! $type) {
                throw ValidationException::withMessages(['driver' => 'Unknown queue driver.']);
            }$normalized = $this->normalize($type, $data['configuration'] ?? []);
            $model = QueueDriverConfiguration::query()->create(['name' => $data['name'], 'driver' => $type, 'configuration' => $normalized, 'is_enabled' => $data['is_enabled'] ?? true, 'created_by' => $actor->id, 'updated_by' => $actor->id]);
            $this->log('create', $model, null, $this->safe($model), $actor);

            return $model;
        });
    }

    public function update(QueueDriverConfiguration $model, array $data, User $actor): QueueDriverConfiguration
    {
        return DB::transaction(function () use ($model, $data, $actor) {
            $model = QueueDriverConfiguration::query()->lockForUpdate()->findOrFail($model->id);
            $this->guardInactive($model);
            if (isset($data['driver']) && $data['driver'] !== $model->driver->value) {
                throw ValidationException::withMessages(['driver' => 'Queue driver cannot be changed after creation.']);
            }$before = $this->safe($model);
            $model->update(['name' => $data['name'], 'configuration' => $this->normalize($model->driver, $data['configuration'] ?? []), 'is_enabled' => $data['is_enabled'] ?? $model->is_enabled, 'updated_by' => $actor->id]);
            $this->log('update', $model, $before, $this->safe($model->refresh()), $actor);

            return $model;
        });
    }

    public function setEnabled(QueueDriverConfiguration $model, bool $enabled, User $actor): QueueDriverConfiguration
    {
        return DB::transaction(function () use ($model, $enabled, $actor) {
            $model = QueueDriverConfiguration::query()->lockForUpdate()->findOrFail($model->id);
            if (! $enabled) {
                $this->guardInactive($model);
            }$before = $this->safe($model);
            $model->update(['is_enabled' => $enabled, 'updated_by' => $actor->id]);
            $this->log($enabled ? 'enable' : 'disable', $model, $before, $this->safe($model->refresh()), $actor);

            return $model;
        });
    }

    public function archive(QueueDriverConfiguration $model, User $actor): void
    {
        DB::transaction(function () use ($model, $actor) {
            $model = QueueDriverConfiguration::query()->lockForUpdate()->findOrFail($model->id);
            $this->guardInactive($model);
            $before = $this->safe($model);
            $model->update(['is_enabled' => false, 'updated_by' => $actor->id]);
            $model->delete();
            $this->log('archive', $model, $before, $this->safe($model), $actor);
        });
    }

    public function restore(int $id, User $actor): QueueDriverConfiguration
    {
        return DB::transaction(function () use ($id, $actor) {
            $model = QueueDriverConfiguration::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $model->restore();
            $model->update(['is_enabled' => false, 'updated_by' => $actor->id]);
            $this->log('restore', $model, null, $this->safe($model), $actor);

            return $model;
        });
    }

    public function forceDelete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor) {
            $model = QueueDriverConfiguration::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $this->guardInactive($model);
            $before = $this->safe($model);
            $model->forceDelete();
            $this->log('force_delete', $model, $before, null, $actor);
        });
    }

    public function safe(QueueDriverConfiguration $model): array
    {
        $model->loadMissing(['latestHealthCheck']);
        $active = $this->isActive($model);
        $data = ['id' => $model->id, 'name' => $model->name, 'driver' => $model->driver->value, 'configuration' => $model->configuration ?? [], 'is_enabled' => $model->is_enabled, 'deleted_at' => $model->deleted_at?->toIso8601String(), 'created_at' => $model->created_at?->toIso8601String(), 'updated_at' => $model->updated_at?->toIso8601String(), 'is_active' => $active, 'latest_health_check' => $model->latestHealthCheck?->toArray()];
        if ($model->driver === QueueDriverType::Redis) {
            $id = $model->configuration['infrastructure_connection_id'] ?? null;
            $infra = $id ? InfrastructureConnection::withTrashed()->find($id) : null;
            $data['infrastructure_connection'] = $infra ? ['id' => $infra->id, 'name' => $infra->name, 'type' => $infra->type->value, 'source' => $infra->source->value, 'is_enabled' => $infra->is_enabled, 'deleted_at' => $infra->deleted_at?->toIso8601String()] : null;
        }

return $data;
    }

    private function normalize(QueueDriverType $type, array $configuration): array
    {
        try {
            return $this->registry->adapter($type)->validateAndNormalize($configuration);
        } catch (ValidationException $e) {
            $messages = [];
            foreach ($e->errors() as $key => $values) {
                $messages[str_starts_with($key, 'configuration.') ? $key : 'configuration.'.$key] = $values;
            }throw ValidationException::withMessages($messages);
        }
    }

    private function isActive(QueueDriverConfiguration $model): bool
    {
        return QueueDriverSettings::query()->whereKey(QueueDriverSettings::SINGLETON_ID)->where('mode', QueueConfigurationMode::Managed->value)->where('active_configuration_id', $model->id)->exists();
    }

    private function guardInactive(QueueDriverConfiguration $model): void
    {
        if ($this->isActive($model)) {
            throw new ActiveQueueDriverConfigurationMutationException('The active managed queue configuration cannot be changed until restart orchestration is available.');
        }
    }

    private function log(string $action, QueueDriverConfiguration $model, ?array $before, ?array $after, User $actor): void
    {
        $this->audit->log('queue_driver_configurations',$action,QueueDriverConfiguration::class,$model->id,$before,$after,actor: $actor);
    }
}
