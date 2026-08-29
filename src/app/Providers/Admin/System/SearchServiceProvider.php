<?php

namespace App\Providers\Admin\System;

use App\Exceptions\Admin\System\Search\InvalidManagedSearchConfigurationException;
use App\Services\Admin\System\Search\SearchDeploymentConfigurationSnapshot;
use App\Services\Admin\System\Search\SearchDriverRegistry;
use App\Services\Admin\System\Search\SearchRuntimeConfigurator;
use App\Services\Admin\System\Search\SearchRuntimeState;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Laravel\Scout\EngineManager;

class SearchServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('simpledesk-search.php'), 'simpledesk-search');
        $this->app->singleton(SearchDriverRegistry::class, fn (Application $app) => new SearchDriverRegistry($app, config('simpledesk-search.adapters', [])));
        $this->app->singleton(SearchDeploymentConfigurationSnapshot::class);
        $this->app->singleton(SearchRuntimeState::class);
    }

    public function boot(SearchDeploymentConfigurationSnapshot $snapshot, SearchRuntimeState $state, EngineManager $engines, SearchRuntimeConfigurator $configurator): void
    {
        $snapshot->capture((array) config('scout', []));
        $managed = trim((string) config('simpledesk-search.runtime.driver_name', 'simpledesk-managed'));
        $engines->extend($managed, function () use ($managed, $state, $engines) {
            $driver = $state->driver();
            if ($driver === $managed) {
                throw new InvalidManagedSearchConfigurationException('The managed Search engine cannot delegate to itself.');
            }

            return $engines->engine($driver);
        });
        $configurator->apply();
    }
}
