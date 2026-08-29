<?php

namespace App\Services\Admin\System\Search;

use App\Enums\Admin\System\SearchConfigurationMode;
use App\Enums\Admin\System\SearchDriverType;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\Admin\System\SearchDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SearchDriverCatalogService
{
    public function __construct(private readonly SearchDriverRegistry $registry, private readonly SystemAuditLogger $audit) {}

    public function create(array $data, User $actor): SearchDriverConfiguration
    {
        return DB::transaction(function () use ($data, $actor) {
            $type = SearchDriverType::tryFrom((string) ($data['driver'] ?? ''));
            if (! $type) {
                throw ValidationException::withMessages(['driver' => 'The selected Search driver is unavailable.']);
            }
            $normalized = $this->registry->adapter($type)->validateAndNormalize($data['configuration'] ?? [], $data['infrastructure_connection_id'] ?? null);
            $model = SearchDriverConfiguration::query()->create(['name' => $data['name'], 'driver' => $type, ...$normalized, 'is_enabled' => $data['is_enabled'] ?? true, 'created_by' => $actor->id]);
            $this->log('create', $model, null, $this->safe($model), $actor);

            return $model;
        });
    }

    public function update(SearchDriverConfiguration $model, array $data, User $actor): SearchDriverConfiguration
    {
        return DB::transaction(function () use ($model, $data, $actor) {
            $settings = $this->lockSettings();
            $model = SearchDriverConfiguration::withTrashed()->lockForUpdate()->findOrFail($model->id);
            $this->guardInactive($model, $settings);
            if (isset($data['driver']) && $data['driver'] !== $model->getRawOriginal('driver')) {
                throw ValidationException::withMessages(['driver' => 'The Search driver cannot be changed.']);
            }
            $normalized = $this->registry->adapter($model->driver)->validateAndNormalize($data['configuration'] ?? [], $data['infrastructure_connection_id'] ?? $model->infrastructure_connection_id);
            $before = $this->safe($model);
            $model->update(['name' => $data['name'], ...$normalized, 'is_enabled' => $data['is_enabled'] ?? $model->is_enabled]);
            $this->log('update', $model, $before, $this->safe($model->refresh()), $actor);

            return $model;
        });
    }

    public function setEnabled(SearchDriverConfiguration $model, bool $enabled, User $actor): SearchDriverConfiguration
    {
        return $this->mutate($model, $actor, $enabled ? 'enable' : 'disable', fn (SearchDriverConfiguration $locked) => $locked->update(['is_enabled' => $enabled]));
    }

    public function archive(SearchDriverConfiguration $model, User $actor): void
    {
        $this->mutate($model, $actor, 'archive', fn (SearchDriverConfiguration $locked) => $locked->delete());
    }

    public function restore(int $id, User $actor): SearchDriverConfiguration
    {
        return DB::transaction(function () use ($id, $actor) {
            $model = SearchDriverConfiguration::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $this->registry->adapter($model->driver)->validateAndNormalize($model->configuration ?? [], $model->infrastructure_connection_id);
            $model->restore();
            $model->update(['is_enabled' => false]);
            $this->log('restore', $model, null, $this->safe($model), $actor);

            return $model;
        });
    }

    public function forceDelete(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor) {
            $model = SearchDriverConfiguration::onlyTrashed()->lockForUpdate()->findOrFail($id);
            $this->guardInactive($model, $this->lockSettings());
            $before = $this->safe($model);
            $subjectId = $model->id;
            $model->forceDelete();
            $this->audit->log('search_driver_configurations', 'force_delete', SearchDriverConfiguration::class, $subjectId, $before, null, actor: $actor);
        });
    }

    public function safe(SearchDriverConfiguration $model): array
    {
        $configuration = $model->getAttribute('configuration');
        $deletedAt = $model->getAttribute('deleted_at');

        return ['id' => $model->id, 'name' => $model->name, 'driver' => $model->getRawOriginal('driver'), 'infrastructure_connection_id' => $model->infrastructure_connection_id, 'configuration' => is_array($configuration) ? $configuration : [], 'is_enabled' => $model->is_enabled, 'archived_at' => $deletedAt instanceof \DateTimeInterface ? $deletedAt->format(DATE_ATOM) : null];
    }

    private function mutate(SearchDriverConfiguration $model, User $actor, string $action, callable $callback): SearchDriverConfiguration
    {
        return DB::transaction(function () use ($model, $actor, $action, $callback) {
            $locked = SearchDriverConfiguration::withTrashed()->lockForUpdate()->findOrFail($model->id);
            $this->guardInactive($locked, $this->lockSettings());
            $before = $this->safe($locked);
            $callback($locked);
            $this->log($action, $locked, $before, $this->safe($locked), $actor);

            return $locked;
        });
    }

    private function lockSettings(): ?SearchDriverSettings
    {
        return SearchDriverSettings::query()->whereKey(1)->lockForUpdate()->first();
    }

    private function guardInactive(SearchDriverConfiguration $model, ?SearchDriverSettings $settings): void
    {
        if ($settings?->getRawOriginal('mode') === SearchConfigurationMode::Managed->value && $settings->active_configuration_id === $model->id) {
            throw ValidationException::withMessages(['configuration' => 'The active managed Search configuration cannot be mutated.']);
        }
    }

    private function log(string $action, SearchDriverConfiguration $model, ?array $before, ?array $after, User $actor): void
    {
        $this->audit->log('search_driver_configurations', $action, SearchDriverConfiguration::class, $model->id, $before, $after, actor: $actor);
    }
}
