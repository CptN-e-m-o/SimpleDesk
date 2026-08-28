<?php

namespace App\Providers\Admin\System;

use App\Services\Admin\System\Storage\StorageDeploymentConfigurationSnapshot;
use App\Services\Admin\System\Storage\StorageDriverRegistry;
use App\Services\Admin\System\Storage\StorageRuntimeConfigurator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class StorageServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('simpledesk-storage.php'), 'simpledesk-storage');
        $this->app->singleton(StorageDriverRegistry::class, fn (Application $app) => new StorageDriverRegistry($app, config('simpledesk-storage.adapters', [])));
        $this->app->singleton(StorageDeploymentConfigurationSnapshot::class);
    }

    public function boot(StorageDeploymentConfigurationSnapshot $snapshot, StorageRuntimeConfigurator $configurator): void
    {
        $snapshot->capture((array) config('filesystems', []));
        $configurator->apply();
    }
}
