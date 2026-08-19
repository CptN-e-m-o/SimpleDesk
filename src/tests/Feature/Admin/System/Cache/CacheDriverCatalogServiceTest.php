<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Models\User\User;
use App\Services\Admin\System\Cache\CacheDriverCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CacheDriverCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_profile_uses_allowlisted_connection(): void
    {
        config()->set('simpledesk-cache.database.allowed_connections', ['sqlite']); config()->set('database.connections.sqlite', config('database.connections.sqlite'));
        $model = app(CacheDriverCatalogService::class)->create(['name' => 'Primary cache', 'driver' => 'database', 'configuration' => ['database_connection' => 'sqlite'], 'is_enabled' => true], User::factory()->create());
        $this->assertSame('sqlite', $model->configuration['database_connection']); $this->assertNull($model->infrastructure_connection_id);
    }

    public function test_file_profile_rejects_arbitrary_paths(): void
    {
        $this->expectException(ValidationException::class);
        app(CacheDriverCatalogService::class)->create(['name' => 'Unsafe file', 'driver' => 'file', 'configuration' => ['path' => 'C:\\outside'], 'is_enabled' => true], User::factory()->create());
    }
}
