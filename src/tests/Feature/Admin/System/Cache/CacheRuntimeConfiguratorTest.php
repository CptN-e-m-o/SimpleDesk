<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Services\Admin\System\Cache\CacheRuntimeConfigurator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CacheRuntimeConfiguratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_settings_row_leaves_deployment_cache_untouched(): void
    {
        config()->set('cache.default', 'database'); app(CacheRuntimeConfigurator::class)->apply(); $this->assertSame('database', config('cache.default'));
    }

    public function test_managed_file_configuration_installs_synthetic_store(): void
    {
        config()->offsetUnset('cache.stores.simpledesk-managed');
        $configuration = CacheDriverConfiguration::query()->create(['name' => 'Managed file', 'driver' => 'file', 'configuration' => [], 'is_enabled' => true]);
        CacheDriverSettings::query()->create(['id' => 1, 'mode' => CacheConfigurationMode::Managed, 'active_configuration_id' => $configuration->id]);
        app(CacheRuntimeConfigurator::class)->apply();
        $this->assertSame('simpledesk-managed', config('cache.default')); $this->assertSame('file', config('cache.stores.simpledesk-managed.driver')); $this->assertStringContainsString('framework/cache/simpledesk', str_replace('\\', '/', config('cache.stores.simpledesk-managed.path')));
    }

    public function test_stable_deployment_target_does_not_follow_runtime_default(): void
    {
        config()->set('simpledesk-cache.deployment.store', 'database'); config()->set('cache.default', 'simpledesk-managed');
        $this->assertSame('database', config('simpledesk-cache.deployment.store'));
    }
}
