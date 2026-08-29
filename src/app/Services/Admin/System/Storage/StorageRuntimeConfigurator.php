<?php

namespace App\Services\Admin\System\Storage;

use App\Enums\Admin\System\StorageConfigurationMode;
use App\Exceptions\Admin\System\Storage\InvalidManagedStorageConfigurationException;
use App\Models\Admin\System\StorageDriverConfiguration;
use App\Models\Admin\System\StorageDriverSettings;
use App\Services\Admin\System\Runtime\SystemRuntimeBootstrapPolicy;
use Illuminate\Support\Facades\Schema;
use Throwable;

class StorageRuntimeConfigurator
{
    public function __construct(private readonly StorageDriverRegistry $registry, private readonly SystemRuntimeBootstrapPolicy $bootstrapPolicy) {}

    public function apply(): void
    {
        try {
            $tablesExist = Schema::hasTable('storage_driver_settings') && Schema::hasTable('storage_driver_configurations');
        } catch (Throwable $exception) {
            if ($this->bootstrapPolicy->maySkipDatabaseInspectionFailure()) {
                return;
            }
            throw new InvalidManagedStorageConfigurationException('Unable to determine Storage ownership because its settings tables could not be inspected.', previous: $exception);
        }
        if (! $tablesExist) {
            return;
        }
        $settings = StorageDriverSettings::query()->find(StorageDriverSettings::SINGLETON_ID);
        if (! $settings || $settings->getRawOriginal('mode') === StorageConfigurationMode::Deployment->value) {
            return;
        }
        if (! $settings->active_configuration_id) {
            throw new InvalidManagedStorageConfigurationException('Managed Storage mode requires an active configuration.');
        }
        $configuration = StorageDriverConfiguration::withTrashed()->find($settings->active_configuration_id);
        if (! $configuration || $configuration->trashed() || ! $configuration->is_enabled) {
            throw new InvalidManagedStorageConfigurationException('The active managed Storage configuration is missing, archived, or disabled.');
        }
        try {
            $runtime = $this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration);
        } catch (Throwable $exception) {
            throw new InvalidManagedStorageConfigurationException('The active managed Storage runtime configuration is invalid.', previous: $exception);
        }
        $managed = trim((string) config('simpledesk-storage.runtime.disk_name', 'simpledesk-managed'));
        if ($managed === '' || array_key_exists($managed, (array) config('filesystems.disks', []))) {
            throw new InvalidManagedStorageConfigurationException('The managed Storage disk name conflicts with a deployment disk.');
        }
        config()->set("filesystems.disks.{$managed}", $runtime->disk);
        config()->set('filesystems.default', $managed);
    }
}
