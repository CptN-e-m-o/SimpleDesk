<?php

namespace App\Http\Controllers\Admin\System;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\DriverCategory;
use App\Enums\Admin\System\QueueConfigurationMode;
use App\Enums\Admin\System\SearchConfigurationMode;
use App\Enums\Admin\System\StorageConfigurationMode;
use App\Http\Controllers\Controller;
use App\Models\Admin\System\BroadcastDriverSettings;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\Admin\System\QueueDriverSettings;
use App\Models\Admin\System\SearchDriverSettings;
use App\Models\Admin\System\StorageDriverSettings;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class DriverController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('Admin/System/Drivers/Index', [
            'categories' => array_map(
                fn (DriverCategory $category) => $category->value,
                DriverCategory::cases(),
            ),
            'states' => [
                'queue' => $this->queueState(),
                'cache' => $this->cacheState(),
                'broadcasting' => $this->broadcastState(),
                'search' => $this->searchState(),
                'storage' => $this->storageState(),
            ],
        ]);
    }

    private function queueState(): array
    {
        if (! Schema::hasTable('queue_driver_settings')) {
            return $this->unavailableState();
        }

        $settings = QueueDriverSettings::query()
            ->with('activeConfiguration')
            ->find(QueueDriverSettings::SINGLETON_ID);

        if (
            ! $settings
            || $settings->mode === QueueConfigurationMode::Deployment
        ) {
            return $this->deploymentState();
        }

        return $this->managedState(
            $settings->activeConfiguration,
        );
    }

    private function cacheState(): array
    {
        if (! Schema::hasTable('cache_driver_settings')) {
            return $this->unavailableState();
        }

        $settings = CacheDriverSettings::query()
            ->with('activeConfiguration')
            ->find(CacheDriverSettings::SINGLETON_ID);

        if (
            ! $settings
            || $settings->mode === CacheConfigurationMode::Deployment
        ) {
            return $this->deploymentState();
        }

        return $this->managedState(
            $settings->activeConfiguration,
        );
    }

    private function broadcastState(): array
    {
        if (! Schema::hasTable('broadcast_driver_settings')) {
            return $this->unavailableState();
        }

        $settings = BroadcastDriverSettings::query()
            ->with('activeConfiguration')
            ->find(BroadcastDriverSettings::SINGLETON_ID);

        if (
            ! $settings
            || $settings->mode === BroadcastConfigurationMode::Deployment
        ) {
            return $this->deploymentState();
        }

        return $this->managedState(
            $settings->activeConfiguration,
        );
    }

    private function searchState(): array
    {
        if (! Schema::hasTable('search_driver_settings')) {
            return $this->unavailableState();
        }

        $settings = SearchDriverSettings::query()
            ->with('activeConfiguration')
            ->find(SearchDriverSettings::SINGLETON_ID);

        if (
            ! $settings
            || $settings->mode === SearchConfigurationMode::Deployment
        ) {
            return $this->deploymentState();
        }

        return $this->managedState(
            $settings->activeConfiguration,
        );
    }

    private function managedState(mixed $configuration): array
    {
        return [
            'mode' => 'managed',
            'active_configuration' => $configuration?->name,
            'active_driver' => $configuration?->driver->value,
            'requires_attention' => ! $configuration
                || $configuration->trashed()
                || ! $configuration->is_enabled,
        ];
    }

    private function storageState(): array
    {
        if (! Schema::hasTable('storage_driver_settings')) {
            return $this->unavailableState();
        }
        $settings = StorageDriverSettings::query()->with('activeConfiguration')->find(StorageDriverSettings::SINGLETON_ID);
        if (! $settings || $settings->mode === StorageConfigurationMode::Deployment) {
            return $this->deploymentState();
        }

        return $this->managedState($settings->activeConfiguration);
    }

    private function deploymentState(): array
    {
        return [
            'mode' => 'deployment',
            'active_configuration' => null,
            'active_driver' => null,
            'requires_attention' => false,
        ];
    }

    private function unavailableState(): array
    {
        return [
            'mode' => 'unavailable',
            'active_configuration' => null,
            'active_driver' => null,
            'requires_attention' => false,
        ];
    }
}
