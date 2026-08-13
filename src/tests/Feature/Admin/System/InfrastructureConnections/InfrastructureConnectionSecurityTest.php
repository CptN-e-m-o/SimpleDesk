<?php

namespace Tests\Feature\Admin\System\InfrastructureConnections;

use App\Models\Admin\System\SystemAuditLog;
use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\Fakes\Admin\System\FakeInfrastructureConnectionAdapter;
use Tests\TestCase;

class InfrastructureConnectionSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_credentials_are_encrypted_hidden_and_absent_from_audit(): void
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

        $actor =
            User::factory()->create();

        $service =
            $this->app->make(
                InfrastructureConnectionCatalogService::class,
            );

        $connection =
            $service->create(
                [
                    'name' =>
                        'Secure Redis',

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
                            'never-plaintext',
                    ],
                ],
                $actor,
            );

        $raw =
            DB::table(
                'infrastructure_connections',
            )
                ->where(
                    'id',
                    $connection->id,
                )
                ->value(
                    'credentials',
                );

        $this->assertIsString(
            $raw,
        );

        $this->assertStringNotContainsString(
            'never-plaintext',
            $raw,
        );

        $this->assertArrayNotHasKey(
            'credentials',
            $connection->toArray(),
        );

        $audit =
            SystemAuditLog::query()
                ->where(
                    'area',
                    'infrastructure_connections',
                )
                ->where(
                    'action',
                    'create',
                )
                ->latest('id')
                ->firstOrFail();

        $serializedAudit =
            json_encode(
                $audit->toArray(),
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringNotContainsString(
            'never-plaintext',
            $serializedAudit,
        );

        $this->assertSame(
            [
                'password',
            ],
            $audit->metadata[
            'credentials_changed'
            ],
        );
    }
}
