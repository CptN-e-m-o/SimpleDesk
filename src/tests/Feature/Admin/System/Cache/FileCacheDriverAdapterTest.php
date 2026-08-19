<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Enums\Admin\System\CacheDriverType;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Services\Admin\System\Cache\CacheStoreHealthProbe;
use App\Services\Admin\System\Cache\Drivers\FileCacheDriverAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class FileCacheDriverAdapterTest extends TestCase
{
    use RefreshDatabase;

    public function test_arbitrary_cache_path_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()
            ->validateAndNormalize([
                'path' => '/tmp/simpledesk-cache',
            ]);
    }

    public function test_arbitrary_lock_path_is_rejected(): void
    {
        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()
            ->validateAndNormalize([
                'lock_path' => '/tmp/simpledesk-locks',
            ]);
    }

    public function test_unpersisted_configuration_cannot_build_runtime_store(): void
    {
        $configuration = new CacheDriverConfiguration([
            'name' => 'Unsaved File Cache',
            'driver' => CacheDriverType::File,
            'configuration' => [],
            'is_enabled' => true,
        ]);

        $this->expectException(
            ValidationException::class,
        );

        $this->adapter()
            ->runtimeConfiguration(
                $configuration,
            );
    }

    public function test_file_profile_uses_isolated_data_and_lock_directories(): void
    {
        $configuration = $this->configuration();

        $runtime = $this->adapter()
            ->runtimeConfiguration(
                $configuration,
            );

        $base = storage_path(
            'framework/cache/simpledesk/'.$configuration->id,
        );

        $this->assertSame(
            'file',
            $runtime->store['driver'],
        );

        $this->assertSame(
            $base.'/data',
            $runtime->store['path'],
        );

        $this->assertSame(
            $base.'/locks',
            $runtime->store['lock_path'],
        );

        $this->assertNotSame(
            $runtime->store['path'],
            $runtime->store['lock_path'],
        );
    }

    public function test_file_profiles_use_different_directories(): void
    {
        $first = $this->configuration(
            'First File Cache',
        );

        $second = $this->configuration(
            'Second File Cache',
        );

        $adapter = $this->adapter();

        $firstRuntime = $adapter
            ->runtimeConfiguration(
                $first,
            );

        $secondRuntime = $adapter
            ->runtimeConfiguration(
                $second,
            );

        $this->assertNotSame(
            $firstRuntime->store['path'],
            $secondRuntime->store['path'],
        );

        $this->assertNotSame(
            $firstRuntime->store['lock_path'],
            $secondRuntime->store['lock_path'],
        );
    }

    public function test_health_check_uses_isolated_profile_store(): void
    {
        $configuration = $this->configuration();

        $base = storage_path(
            'framework/cache/simpledesk/'.$configuration->id,
        );

        $probe = $this->createMock(
            CacheStoreHealthProbe::class,
        );

        $probe
            ->expects($this->once())
            ->method('test')
            ->with(
                [
                    'driver' => 'file',
                    'path' => $base.'/data',
                    'lock_path' => $base.'/locks',
                ],
                [],
                [
                    'profile_isolated' => true,
                    'separate_lock_store' => true,
                ],
            )
            ->willReturn(
                new CacheHealthResultData(
                    status: CacheHealthStatus::Healthy,
                    latencyMs: 3,
                    message: 'Cache target verified.',
                ),
            );

        $adapter = new FileCacheDriverAdapter(
            $probe,
        );

        $result = $adapter->test(
            $configuration,
        );

        $this->assertSame(
            CacheHealthStatus::Healthy,
            $result->status,
        );
    }

    private function adapter(): FileCacheDriverAdapter
    {
        return new FileCacheDriverAdapter(
            $this->createMock(
                CacheStoreHealthProbe::class,
            ),
        );
    }

    private function configuration(
        string $name = 'File Cache',
    ): CacheDriverConfiguration {
        return CacheDriverConfiguration::query()->create([
            'name' => $name,
            'driver' => CacheDriverType::File,
            'infrastructure_connection_id' => null,
            'configuration' => [],
            'is_enabled' => true,
        ]);
    }
}
