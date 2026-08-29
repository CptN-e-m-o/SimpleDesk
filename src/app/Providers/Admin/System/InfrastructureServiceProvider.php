<?php

namespace App\Providers\Admin\System;

use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class InfrastructureServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('simpledesk-infrastructure.php'), 'simpledesk-infrastructure');
        $this->app->singleton(InfrastructureConnectionRegistry::class, fn (Application $app) => new InfrastructureConnectionRegistry($app, config('simpledesk-infrastructure.adapters', [])));
    }
}
