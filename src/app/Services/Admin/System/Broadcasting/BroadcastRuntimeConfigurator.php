<?php

namespace App\Services\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Exceptions\Admin\System\Broadcasting\InvalidManagedBroadcastConfigurationException;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Services\Admin\System\Runtime\SystemRuntimeBootstrapPolicy;
use Illuminate\Support\Facades\Schema;
use Throwable;

class BroadcastRuntimeConfigurator
{
    public function __construct(private readonly BroadcastDriverRegistry $registry, private readonly SystemRuntimeBootstrapPolicy $bootstrapPolicy) {}

    public function apply(): void
    {
        try {
            $tablesExist = Schema::hasTable('broadcast_driver_settings') && Schema::hasTable('broadcast_driver_configurations');
        } catch (Throwable $exception) {
            if ($this->bootstrapPolicy->maySkipDatabaseInspectionFailure()) {
                return;
            }
            throw new InvalidManagedBroadcastConfigurationException('Unable to determine Broadcast ownership because its settings tables could not be inspected.', previous: $exception);
        }
        if (! $tablesExist) {
            return;
        }
        $settings = BroadcastDriverSettings::query()->find(BroadcastDriverSettings::SINGLETON_ID);
        if (! $settings || $settings->mode === BroadcastConfigurationMode::Deployment) {
            return;
        }
        if (! $settings->active_configuration_id) {
            throw new InvalidManagedBroadcastConfigurationException('Managed Broadcast mode requires an active configuration.');
        }
        $configuration = BroadcastDriverConfiguration::withTrashed()->find($settings->active_configuration_id);
        if (! $configuration || $configuration->trashed() || ! $configuration->is_enabled) {
            throw new InvalidManagedBroadcastConfigurationException('The active managed Broadcast configuration is missing, archived, or disabled.');
        }
        try {
            $runtime = $this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration);
        } catch (Throwable $exception) {
            throw new InvalidManagedBroadcastConfigurationException('The active managed Broadcast runtime configuration is invalid.', previous: $exception);
        }
        $name = trim((string) config('simpledesk-broadcasting.runtime.connection_name', 'simpledesk-managed'));
        if ($name === '' || config("broadcasting.connections.{$name}") !== null) {
            throw new InvalidManagedBroadcastConfigurationException('The managed Broadcast connection name is empty or collides with a deployment-defined connection.');
        }
        config()->set("broadcasting.connections.{$name}", $runtime->connection);
        config()->set('broadcasting.default', $name);
    }
}
