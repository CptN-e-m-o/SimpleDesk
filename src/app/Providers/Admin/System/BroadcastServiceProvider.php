<?php

namespace App\Providers\Admin\System;

use App\Services\Admin\System\Broadcasting\BroadcastDriverRegistry;
use App\Services\Admin\System\Broadcasting\BroadcastRuntimeConfigurator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class BroadcastServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('simpledesk-broadcasting.php'), 'simpledesk-broadcasting');
        $this->app->singleton(BroadcastDriverRegistry::class, fn (Application $app) => new BroadcastDriverRegistry($app, config('simpledesk-broadcasting.adapters', [])));
    }

    public function boot(BroadcastRuntimeConfigurator $configurator): void
    {
        $configurator->apply();
    }
}
