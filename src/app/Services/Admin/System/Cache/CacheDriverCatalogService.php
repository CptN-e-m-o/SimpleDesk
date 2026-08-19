<?php

namespace App\Services\Admin\System\Cache;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\CacheDriverType;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Exceptions\Admin\System\Cache\ActiveCacheDriverConfigurationMutationException;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CacheDriverCatalogService
{
    public function __construct(private readonly CacheDriverRegistry $registry, private readonly SystemAuditLogger $audit) {}
    public function create(array $data, User $actor): CacheDriverConfiguration
    {
        return DB::transaction(function () use ($data, $actor) { $type = CacheDriverType::tryFrom((string) ($data['driver'] ?? '')); if (! $type) throw ValidationException::withMessages(['driver' => 'Unknown cache driver.']); $this->registry->adapter($type); $infra = $this->resolveInfrastructure($type, $data['infrastructure_connection_id'] ?? null); $normalized = $this->normalize($type, $data['configuration'] ?? []); $model = CacheDriverConfiguration::query()->create(['name' => $data['name'], 'driver' => $type, 'infrastructure_connection_id' => $infra?->id, 'configuration' => $normalized, 'is_enabled' => $data['is_enabled'] ?? true, 'created_by' => $actor->id, 'updated_by' => $actor->id]); $this->log('create', $model, null, $this->safe($model), $actor); return $model; });
    }
    public function update(CacheDriverConfiguration $model, array $data, User $actor): CacheDriverConfiguration
    {
        return DB::transaction(function () use ($model, $data, $actor) { $settings = $this->lockSettings(); $model = CacheDriverConfiguration::query()->lockForUpdate()->findOrFail($model->id); $this->guardInactive($model, $settings); if (isset($data['driver']) && $data['driver'] !== $model->driver->value) throw ValidationException::withMessages(['driver' => 'Cache driver cannot be changed after creation.']); $before = $this->safe($model); $infra = $this->resolveInfrastructure($model->driver, array_key_exists('infrastructure_connection_id', $data) ? $data['infrastructure_connection_id'] : $model->infrastructure_connection_id); $model->update(['name' => $data['name'], 'infrastructure_connection_id' => $infra?->id, 'configuration' => $this->normalize($model->driver, $data['configuration'] ?? []), 'is_enabled' => $data['is_enabled'] ?? $model->is_enabled, 'updated_by' => $actor->id]); $this->log('update', $model, $before, $this->safe($model->refresh()), $actor); return $model; });
    }
    public function setEnabled(CacheDriverConfiguration $model, bool $enabled, User $actor): CacheDriverConfiguration
    {
        return DB::transaction(function () use ($model, $enabled, $actor) { $settings = $this->lockSettings(); $model = CacheDriverConfiguration::query()->lockForUpdate()->findOrFail($model->id); if (! $enabled) $this->guardInactive($model, $settings); $before = $this->safe($model); $model->update(['is_enabled' => $enabled, 'updated_by' => $actor->id]); $this->log($enabled ? 'enable' : 'disable', $model, $before, $this->safe($model->refresh()), $actor); return $model; });
    }
    public function archive(CacheDriverConfiguration $model, User $actor): void { DB::transaction(function () use ($model, $actor) { $settings = $this->lockSettings(); $model = CacheDriverConfiguration::query()->lockForUpdate()->findOrFail($model->id); $this->guardInactive($model, $settings); $before = $this->safe($model); $model->update(['is_enabled' => false, 'updated_by' => $actor->id]); $model->delete(); $this->log('archive', $model, $before, $this->safe($model), $actor); }); }
    public function restore(int $id, User $actor): CacheDriverConfiguration { return DB::transaction(function () use ($id, $actor) { $model = CacheDriverConfiguration::onlyTrashed()->lockForUpdate()->findOrFail($id); $model->restore(); $model->update(['is_enabled' => false, 'updated_by' => $actor->id]); $this->log('restore', $model, null, $this->safe($model), $actor); return $model; }); }
    public function forceDelete(int $id, User $actor): void { DB::transaction(function () use ($id, $actor) { $settings = $this->lockSettings(); $model = CacheDriverConfiguration::onlyTrashed()->lockForUpdate()->findOrFail($id); $this->guardInactive($model, $settings); $before = $this->safe($model); $subject = $model->id; $model->forceDelete(); $this->audit->log('cache_driver_configurations', 'force_delete', CacheDriverConfiguration::class, $subject, $before, null, actor: $actor); }); }
    public function safe(CacheDriverConfiguration $model): array
    {
        $model->loadMissing(['latestHealthCheck', 'infrastructureConnection']); $health = $model->latestHealthCheck;
        return ['id' => $model->id, 'name' => $model->name, 'driver' => $model->driver->value, 'infrastructure_connection_id' => $model->infrastructure_connection_id, 'configuration' => array_diff_key($model->configuration ?? [], ['infrastructure_connection_id' => true]), 'is_enabled' => $model->is_enabled, 'deleted_at' => $model->deleted_at?->toIso8601String(), 'is_active' => $this->isActive($model), 'latest_health_check' => $health ? ['status' => $health->status->value, 'latency_ms' => $health->latency_ms, 'message' => $health->message, 'details' => $health->details, 'created_at' => $health->created_at?->toIso8601String()] : null, 'infrastructure_connection' => $model->infrastructureConnection ? ['id' => $model->infrastructureConnection->id, 'name' => $model->infrastructureConnection->name, 'type' => $model->infrastructureConnection->type->value, 'source' => $model->infrastructureConnection->source->value, 'is_enabled' => $model->infrastructureConnection->is_enabled, 'deleted_at' => $model->infrastructureConnection->deleted_at?->toIso8601String()] : null];
    }
    private function normalize(CacheDriverType $type, array $configuration): array { unset($configuration['infrastructure_connection_id']); try { return $this->registry->adapter($type)->validateAndNormalize($configuration); } catch (ValidationException $e) { $messages = []; foreach ($e->errors() as $key => $values) $messages[str_starts_with($key, 'configuration.') ? $key : 'configuration.'.$key] = $values; throw ValidationException::withMessages($messages); } }
    private function resolveInfrastructure(CacheDriverType $type, mixed $id): ?InfrastructureConnection
    {
        if ($type !== CacheDriverType::Redis) { if ($id !== null && $id !== '') throw ValidationException::withMessages(['infrastructure_connection_id' => 'Only Redis cache configurations may reference infrastructure.']); return null; }
        $id = filter_var($id, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]); if ($id === false) throw ValidationException::withMessages(['infrastructure_connection_id' => 'A Redis infrastructure connection is required.']); $connection = InfrastructureConnection::withTrashed()->find($id);
        if (! $connection) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The selected Redis infrastructure connection does not exist.']); if ($connection->trashed()) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The selected Redis infrastructure connection is archived.']); if ($connection->type !== InfrastructureConnectionType::Redis) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The selected infrastructure connection is not Redis.']); if (! $connection->is_enabled) throw ValidationException::withMessages(['infrastructure_connection_id' => 'The selected Redis infrastructure connection is disabled.']); return $connection;
    }
    private function lockSettings(): ?CacheDriverSettings { return CacheDriverSettings::query()->whereKey(CacheDriverSettings::SINGLETON_ID)->lockForUpdate()->first(); }
    private function guardInactive(CacheDriverConfiguration $model, ?CacheDriverSettings $settings): void { if ($settings?->mode === CacheConfigurationMode::Managed && $settings->active_configuration_id === $model->id) throw new ActiveCacheDriverConfigurationMutationException('The active managed cache configuration cannot be mutated.'); }
    private function isActive(CacheDriverConfiguration $model): bool { return CacheDriverSettings::query()->whereKey(1)->where('mode', CacheConfigurationMode::Managed->value)->where('active_configuration_id', $model->id)->exists(); }
    private function log(string $action, CacheDriverConfiguration $model, ?array $before, ?array $after, User $actor): void { $this->audit->log('cache_driver_configurations', $action, CacheDriverConfiguration::class, $model->id, $before, $after, actor: $actor); }
}
