<?php

namespace App\Services\Admin\System\Search;

use App\Enums\Admin\System\SearchConfigurationMode;
use App\Enums\Admin\System\SearchDriverType;
use App\Exceptions\Admin\System\Search\InvalidManagedSearchConfigurationException;
use App\Models\Admin\System\SearchDriverConfiguration;
use App\Models\Admin\System\SearchDriverSettings;
use App\Services\Admin\System\Runtime\SystemRuntimeBootstrapPolicy;
use Illuminate\Support\Facades\Schema;
use Throwable;

class SearchRuntimeConfigurator
{
    public function __construct(private readonly SearchDriverRegistry $registry, private readonly SearchRuntimeState $state, private readonly SystemRuntimeBootstrapPolicy $bootstrapPolicy) {}

    public function apply(): void
    {
        try {
            $tablesExist = Schema::hasTable('search_driver_settings') && Schema::hasTable('search_driver_configurations');
        } catch (Throwable $exception) {
            if ($this->bootstrapPolicy->maySkipDatabaseInspectionFailure()) {
                return;
            }
            throw new InvalidManagedSearchConfigurationException('Unable to determine Search ownership because its settings tables could not be inspected.', previous: $exception);
        }
        if (! $tablesExist) {
            return;
        }
        $settings = SearchDriverSettings::query()->find(SearchDriverSettings::SINGLETON_ID);
        if (! $settings || $settings->getRawOriginal('mode') === SearchConfigurationMode::Deployment->value) {
            return;
        }
        if (! $settings->active_configuration_id) {
            throw new InvalidManagedSearchConfigurationException('Managed Search mode requires an active configuration.');
        }
        $configuration = SearchDriverConfiguration::withTrashed()->find($settings->active_configuration_id);
        if (! $configuration || $configuration->trashed() || ! $configuration->is_enabled) {
            throw new InvalidManagedSearchConfigurationException('The active managed Search configuration is missing, archived, or disabled.');
        }
        try {
            $runtime = $this->registry->adapter($configuration->driver)->runtimeConfiguration($configuration);
            $this->applyConnectivity($runtime->driver, $runtime->connectivity);
        } catch (Throwable $exception) {
            throw new InvalidManagedSearchConfigurationException('The active managed Search runtime configuration is invalid.', previous: $exception);
        }
        $managed = trim((string) config('simpledesk-search.runtime.driver_name', 'simpledesk-managed'));
        if ($managed === '' || $runtime->driver->value === $managed) {
            throw new InvalidManagedSearchConfigurationException('The managed Search driver name is invalid.');
        }
        $this->state->setDriver($runtime->driver->value);
        config()->set('scout.driver', $managed);
    }

    private function applyConnectivity(SearchDriverType $driver, array $connectivity): void
    {
        match ($driver) {
            SearchDriverType::Database => null,
            SearchDriverType::Meilisearch => config()->set('scout.meilisearch', [...(array) config('scout.meilisearch', []), ...$connectivity]),
            SearchDriverType::Typesense => config()->set('scout.typesense.client-settings', [...(array) config('scout.typesense.client-settings', []), ...$connectivity]),
            SearchDriverType::Algolia => config()->set('scout.algolia', [...(array) config('scout.algolia', []), ...$connectivity]),
        };
    }
}
