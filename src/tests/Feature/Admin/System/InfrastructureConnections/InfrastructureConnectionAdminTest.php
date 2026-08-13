<?php

namespace Tests\Feature\Admin\System\InfrastructureConnections;

use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Fakes\Admin\System\FakeInfrastructureConnectionAdapter;
use Tests\TestCase;

class InfrastructureConnectionAdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_creates_updates_archives_and_restores_connection(): void
    {
        $this->app->instance(
            InfrastructureConnectionRegistry::class,
            new InfrastructureConnectionRegistry(
                $this->app,
                [
                    'redis' =>
                        FakeInfrastructureConnectionAdapter::class,
                ],
            ),
        );

        $service =
            $this->app->make(
                InfrastructureConnectionCatalogService::class,
            );

        $actor =
            User::factory()->create();

        $connection =
            $service->create(
                [
                    'name' =>
                        'Redis',
                    'type' =>
                        'redis',
                    'source' =>
                        'managed',
                    'configuration' => [
                        'host' =>
                            'redis',
                    ],
                    'credentials' => [
                        'password' =>
                            'first-secret',
                    ],
                    'is_enabled' =>
                        true,
                ],
                $actor,
            );

        $service->update(
            $connection,
            [
                'name' =>
                    'Redis updated',
                'source' =>
                    'managed',
                'configuration' => [
                    'host' =>
                        'redis-new',
                ],
                'credentials' => [
                    'password' =>
                        '',
                ],
                'is_enabled' =>
                    false,
            ],
            $actor,
        );

        $connection->refresh();

        $this->assertSame(
            'first-secret',
            $connection
                ->secrets()[
            'password'
            ],
        );

        $this->assertFalse(
            $connection->is_enabled,
        );

        $service->update(
            $connection,
            [
                'name' =>
                    'Redis updated',
                'source' =>
                    'managed',
                'configuration' =>
                    [],
                'remove_credentials' => [
                    'password',
                ],
                'is_enabled' =>
                    false,
            ],
            $actor,
        );

        $this->assertArrayNotHasKey(
            'password',
            $connection
                ->refresh()
                ->secrets(),
        );

        $service->archive(
            $connection,
            $actor,
        );

        $this->assertSoftDeleted(
            $connection,
        );

        $restored =
            $service->restore(
                $connection->id,
                $actor,
            );

        $this->assertFalse(
            $restored->trashed(),
        );
    }
}
