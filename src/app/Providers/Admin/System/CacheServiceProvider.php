<?php

namespace App\Providers\Admin\System;

use App\Services\Admin\System\Cache\CacheDriverRegistry;
use App\Services\Admin\System\Cache\CacheRuntimeConfigurator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class CacheServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('simpledesk-cache.php'), 'simpledesk-cache');
        $this->app->singleton(CacheDriverRegistry::class, fn (Application $app) => new CacheDriverRegistry($app, config('simpledesk-cache.adapters', [])));
    }

    public function boot(CacheRuntimeConfigurator $configurator): void
    {
        $configurator->apply();
    }
}
