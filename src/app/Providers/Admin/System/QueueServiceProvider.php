<?php

namespace App\Providers\Admin\System;

use App\Services\Admin\System\Queues\QueueDriverRegistry;
use App\Services\Admin\System\Queues\QueueRuntimeConfigurator;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class QueueServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(config_path('simpledesk-queues.php'), 'simpledesk-queues');

        $this->app->singleton(QueueDriverRegistry::class, fn (Application $app) => new QueueDriverRegistry(
            container: $app,
            adapters: config('simpledesk-queues.adapters', []),
        ));
    }

    public function boot(QueueRuntimeConfigurator $configurator): void
    {
        $configurator->apply();
    }
}
