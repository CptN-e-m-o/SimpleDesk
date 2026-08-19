<?php

namespace App\Services\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheActivationResultData;
use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class CacheActivationService
{
    public function __construct(private readonly CacheDriverRegistry $registry, private readonly CacheDriverHealthService $health, private readonly CacheDeploymentTargetService $deployment, private readonly QueueWorkerRestartService $restart, private readonly SystemAuditLogger $audit) {}
    public function activate(CacheDriverConfiguration $configuration, User $actor, bool $force = false): CacheActivationResultData
    {
        $observed = $this->state(); if ($observed['mode'] === 'managed' && $observed['active_configuration_id'] === $configuration->id) throw ValidationException::withMessages(['configuration' => 'This Cache configuration is already active.']);
        $this->assertStructural($configuration); $targetHealth = $this->health->preflight($configuration, $actor); $override = $targetHealth->status !== CacheHealthStatus::Healthy; if ($override && ! $force) throw ValidationException::withMessages(['activation' => $targetHealth->message]);
        DB::transaction(function () use ($configuration, $actor, $force, $override, $targetHealth, $observed) { $settings = $this->lockSettings(); $this->assertUnchanged($settings, $observed); $target = CacheDriverConfiguration::withTrashed()->whereKey($configuration->id)->lockForUpdate()->first(); if (! $target) throw ValidationException::withMessages(['configuration' => 'The Cache configuration no longer exists.']); $this->assertStructural($target); $before = $this->settingsState($settings); $settings->update(['mode' => CacheConfigurationMode::Managed, 'active_configuration_id' => $target->id, 'activated_at' => now(), 'activated_by' => $actor->id]); $this->audit->log('cache_driver_configurations', $force ? 'force_activate' : 'activate', CacheDriverConfiguration::class, $target->id, $before, $this->settingsState($settings->refresh()), ['force_requested' => $force, 'target_health_override_used' => $force && $override, 'target_health' => $targetHealth->toArray(), 'source_ownership' => $observed['mode'], 'target_ownership' => 'managed'], $actor); });
        return new CacheActivationResultData(CacheDriverSettings::query()->findOrFail(1), $force, $override && $force, $this->signalRestart($actor, $configuration->id));
    }
    public function activateDeployment(User $actor, bool $force = false): CacheActivationResultData
    {
        $observed = $this->state(); if ($observed['mode'] === 'deployment') throw ValidationException::withMessages(['activation' => 'Deployment Cache configuration is already active.']); $target = $this->deployment->resolve(); $health = $this->deployment->test($target); $this->audit->log('cache_driver_configurations', 'deployment_preflight', CacheDriverSettings::class, 1, null, null, ['target' => ['store' => $target['store'], 'driver' => $target['driver']], 'health' => $health->toArray()], $actor); $override = $health->status !== CacheHealthStatus::Healthy; if ($override && ! $force) throw ValidationException::withMessages(['activation' => $health->message]);
        DB::transaction(function () use ($actor, $force, $override, $health, $target, $observed) { $settings = $this->lockSettings(); $this->assertUnchanged($settings, $observed); $before = $this->settingsState($settings); $previous = $settings->active_configuration_id; $settings->update(['mode' => CacheConfigurationMode::Deployment, 'active_configuration_id' => null, 'activated_at' => now(), 'activated_by' => $actor->id]); $this->audit->log('cache_driver_configurations', $force ? 'force_activate_deployment' : 'activate_deployment', CacheDriverSettings::class, 1, $before, $this->settingsState($settings->refresh()), ['force_requested' => $force, 'target_health_override_used' => $force && $override, 'target_health' => $health->toArray(), 'source_ownership' => 'managed', 'target_ownership' => 'deployment', 'previous_configuration_id' => $previous, 'deployment_target' => ['store' => $target['store'], 'driver' => $target['driver']]], $actor); });
        return new CacheActivationResultData(CacheDriverSettings::query()->findOrFail(1), $force, $override && $force, $this->signalRestart($actor));
    }
    private function assertStructural(CacheDriverConfiguration $configuration): void { if ($configuration->trashed()) throw ValidationException::withMessages(['configuration' => 'Archived Cache configurations cannot be activated.']); if (! $configuration->is_enabled) throw ValidationException::withMessages(['configuration' => 'Disabled Cache configurations cannot be activated.']); $this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration); }
    private function state(): array { $settings = CacheDriverSettings::query()->find(1); return ['exists' => $settings !== null, 'mode' => $settings?->mode->value ?? 'deployment', 'active_configuration_id' => $settings?->active_configuration_id]; }
    private function lockSettings(): CacheDriverSettings { return CacheDriverSettings::query()->whereKey(1)->lockForUpdate()->first() ?? CacheDriverSettings::query()->create(['id' => 1, 'mode' => CacheConfigurationMode::Deployment]); }
    private function assertUnchanged(CacheDriverSettings $settings, array $observed): void { if ($observed['mode'] !== $settings->mode->value || $observed['active_configuration_id'] !== $settings->active_configuration_id) throw ValidationException::withMessages(['activation' => 'Cache ownership changed while activation was being prepared. Retry the operation.']); }
    private function settingsState(CacheDriverSettings $settings): array { return ['mode' => $settings->mode->value, 'active_configuration_id' => $settings->active_configuration_id, 'activated_at' => $settings->activated_at?->toIso8601String(), 'activated_by' => $settings->activated_by]; }
    private function signalRestart(User $actor, ?int $configurationId = null): bool { try { $this->restart->signal(); $success = true; } catch (Throwable) { $success = false; } $this->audit->log('cache_driver_configurations', $success ? 'worker_restart_signal_succeeded' : 'worker_restart_signal_failed', CacheDriverSettings::class, 1, null, null, ['configuration_id' => $configurationId, 'signal_issued' => $success, 'workers_restarted' => false], $actor); return $success; }
}
