<?php

namespace App\Services\Admin\System\Search;

use App\Data\Admin\System\Search\SearchActivationResultData;
use App\Enums\Admin\System\SearchConfigurationMode;
use App\Enums\Admin\System\SearchHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\Admin\System\SearchDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Queues\QueueWorkerRestartService;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SearchActivationService
{
    public function __construct(private readonly SearchDriverRegistry $registry, private readonly SearchDriverHealthService $health, private readonly SearchDeploymentTargetService $deployment, private readonly QueueWorkerRestartService $restart, private readonly SystemAuditLogger $audit, private readonly SearchRuntimeFingerprintService $fingerprints) {}

    public function activate(SearchDriverConfiguration $configuration, User $actor, bool $force = false): SearchActivationResultData
    {
        $observed = $this->state();
        $target = SearchDriverConfiguration::withTrashed()->findOrFail($configuration->id);
        $this->assertActivatable($target);
        $observedRuntime = $this->runtimeFingerprints($target);
        $health = $this->health->preflight($target, $actor);
        $override = $health->status !== SearchHealthStatus::Healthy;
        if ($override && ! $force) {
            throw ValidationException::withMessages(['activation' => 'Managed Search activation requires a healthy provider preflight. '.$health->message]);
        }
        DB::transaction(function () use ($configuration, $actor, $force, $health, $override, $observed, $observedRuntime) {
            $settings = $this->lockSettings();
            $this->assertUnchanged($settings, $observed);
            $target = SearchDriverConfiguration::withTrashed()->lockForUpdate()->findOrFail($configuration->id);
            $infrastructure = $this->lockInfrastructure($target);
            $this->assertRuntimeUnchanged($target, $infrastructure, $observedRuntime);
            $this->assertActivatable($target);
            $before = $this->settingsState($settings);
            $settings->update(['mode' => SearchConfigurationMode::Managed, 'active_configuration_id' => $target->id, 'activated_by' => $actor->id, 'activated_at' => now()]);
            $this->audit->log('search_driver_configurations', $force ? 'force_activate' : 'activate', SearchDriverConfiguration::class, $target->id, $before, $this->settingsState($settings->refresh()), ['target_health' => $health->toArray(), 'target_health_override_used' => $force ? $override : false], $actor);
        });

        return $this->result($force, $override, $actor);
    }

    public function activateDeployment(User $actor, bool $force = false): SearchActivationResultData
    {
        $observed = $this->state();
        $target = $this->deployment->resolve();
        $health = $this->deployment->test($target);
        $override = $health->status !== SearchHealthStatus::Healthy;
        if ($override && ! $force) {
            throw ValidationException::withMessages(['activation' => 'Deployment Search activation preflight failed. '.$health->message]);
        }
        DB::transaction(function () use ($actor, $force, $target, $health, $override, $observed) {
            $settings = $this->lockSettings();
            $this->assertUnchanged($settings, $observed);
            $this->deployment->resolve();
            $before = $this->settingsState($settings);
            $settings->update(['mode' => SearchConfigurationMode::Deployment, 'active_configuration_id' => null, 'activated_by' => $actor->id, 'activated_at' => now()]);
            $this->audit->log('search_driver_configurations', $force ? 'force_activate_deployment' : 'activate_deployment', SearchDriverSettings::class, 1, $before, $this->settingsState($settings->refresh()), ['deployment_target' => ['driver' => $target['driver']], 'target_health' => $health->toArray(), 'target_health_override_used' => $force ? $override : false], $actor);
        });

        return $this->result($force, $override, $actor);
    }

    private function result(bool $force, bool $override, User $actor): SearchActivationResultData
    {
        try {
            $this->restart->signal();
            $signaled = true;
        } catch (Throwable) {
            $signaled = false;
        }
        $settings = SearchDriverSettings::query()->findOrFail(1);
        if (! $signaled) {
            $this->audit->log('search_driver_configurations', 'restart_signal_failed', SearchDriverSettings::class, 1, null, null, ['signal_issued' => false, 'workers_restarted' => false], $actor);
        }

        return new SearchActivationResultData($settings, $force, $force ? $override : false, $signaled);
    }

    private function assertActivatable(SearchDriverConfiguration $configuration): void
    {
        if ($configuration->trashed() || ! $configuration->is_enabled) {
            throw ValidationException::withMessages(['configuration' => 'Archived or disabled Search configurations cannot be activated.']);
        }
        $this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration);
    }

    private function runtimeFingerprints(SearchDriverConfiguration $configuration): array
    {
        $infrastructure = null;
        if ($this->fingerprints->usesInfrastructure($configuration) && $configuration->infrastructure_connection_id) {
            $infrastructure = InfrastructureConnection::withTrashed()->find($configuration->infrastructure_connection_id);
        }

        return ['target' => $this->fingerprints->target($configuration), 'infrastructure' => $infrastructure ? $this->fingerprints->infrastructure($infrastructure) : null];
    }

    private function lockInfrastructure(SearchDriverConfiguration $configuration): ?InfrastructureConnection
    {
        if (! $this->fingerprints->usesInfrastructure($configuration) || ! $configuration->infrastructure_connection_id) {
            return null;
        }

        return InfrastructureConnection::withTrashed()->whereKey($configuration->infrastructure_connection_id)->lockForUpdate()->first();
    }

    private function assertRuntimeUnchanged(SearchDriverConfiguration $configuration, ?InfrastructureConnection $infrastructure, array $observed): void
    {
        $currentInfrastructure = $infrastructure ? $this->fingerprints->infrastructure($infrastructure) : null;
        if ($this->fingerprints->target($configuration) !== $observed['target'] || $currentInfrastructure !== $observed['infrastructure']) {
            throw ValidationException::withMessages(['activation' => 'Search runtime configuration changed while activation was prepared.']);
        }
    }

    private function state(): array
    {
        $settings = SearchDriverSettings::query()->find(1);

        return ['mode' => $settings?->getRawOriginal('mode') ?? 'deployment', 'active_configuration_id' => $settings?->active_configuration_id];
    }

    private function lockSettings(): SearchDriverSettings
    {
        $now = now();
        SearchDriverSettings::query()->insertOrIgnore(['id' => 1, 'mode' => 'deployment', 'created_at' => $now, 'updated_at' => $now]);

        return SearchDriverSettings::query()->whereKey(1)->lockForUpdate()->firstOrFail();
    }

    private function assertUnchanged(SearchDriverSettings $settings, array $observed): void
    {
        if ($settings->getRawOriginal('mode') !== $observed['mode'] || $settings->active_configuration_id !== $observed['active_configuration_id']) {
            throw ValidationException::withMessages(['activation' => 'Search ownership changed while activation was prepared.']);
        }
    }

    private function settingsState(SearchDriverSettings $settings): array
    {
        $activatedAt = $settings->getAttribute('activated_at');

        return ['mode' => $settings->getRawOriginal('mode'), 'active_configuration_id' => $settings->active_configuration_id, 'activated_at' => $activatedAt instanceof \DateTimeInterface ? $activatedAt->format(DATE_ATOM) : null, 'activated_by' => $settings->activated_by];
    }
}
