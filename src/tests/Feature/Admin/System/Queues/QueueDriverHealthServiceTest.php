<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\SystemAuditLog;
use App\Services\Admin\System\Queues\QueueDriverHealthService;
use App\Services\Admin\System\Queues\QueueDriverRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\Fakes\Admin\System\LeakyQueueDriverAdapter;
use Tests\TestCase;

class QueueDriverHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_and_sync_health_are_persisted_and_audited(): void
    {
        if (
            ! Schema::hasTable(
                'jobs',
            )
        ) {
            Schema::create(
                'jobs',
                function ($table): void {
                    $table->id();
                },
            );
        }

        $cases = [
            [
                QueueDriverType::Database,

                [
                    'database_connection' => config(
                        'database.default',
                    ),

                    'retry_after' => 360,

                    'after_commit' => false,
                ],
            ],

            [
                QueueDriverType::Sync,
                [],
            ],
        ];

        foreach (
            $cases as [
                $driver,
                $values,
            ]
        ) {
            $configuration =
                QueueDriverConfiguration::query()
                    ->create([
                        'name' => $driver->value,

                        'driver' => $driver,

                        'configuration' => $values,

                        'is_enabled' => true,
                    ]);

            $result =
                app(
                    QueueDriverHealthService::class,
                )->test(
                    $configuration,
                );

            $this->assertSame(
                'healthy',
                $result
                    ->status
                    ->value,
            );

            $this->assertNotNull(
                $configuration
                    ->latestHealthCheck()
                    ->first(),
            );
        }

        $this->assertSame(
            2,
            SystemAuditLog::query()
                ->where(
                    'area',
                    'queue_driver_configurations',
                )
                ->where(
                    'action',
                    'test',
                )
                ->count(),
        );
    }

    public function test_health_result_persistence_and_audit_never_expose_infrastructure_secrets(): void
    {
        $this->app->instance(
            QueueDriverRegistry::class,
            new QueueDriverRegistry(
                $this->app,
                [
                    'redis' => LeakyQueueDriverAdapter::class,
                ],
            ),
        );

        $secret =
            'queue-health-super-secret';

        $infrastructure =
            InfrastructureConnection::factory()
                ->create([
                    'credentials' => [
                        'password' => $secret,
                    ],

                    'is_enabled' => true,
                ]);

        $configuration =
            QueueDriverConfiguration::query()
                ->create([
                    'name' => 'Leaky health test',

                    'driver' => QueueDriverType::Redis,

                    'configuration' => [
                        'infrastructure_connection_id' => $infrastructure->id,
                    ],

                    'is_enabled' => true,
                ]);

        $result =
            app(
                QueueDriverHealthService::class,
            )->test(
                $configuration,
            );

        $health =
            $configuration
                ->latestHealthCheck()
                ->firstOrFail();

        $audit =
            SystemAuditLog::query()
                ->where(
                    'area',
                    'queue_driver_configurations',
                )
                ->where(
                    'action',
                    'test',
                )
                ->latest('id')
                ->firstOrFail();

        $serialized =
            json_encode(
                [
                    'result' => $result->toArray(),

                    'health' => $health->toArray(),

                    'audit' => $audit->toArray(),
                ],
                JSON_THROW_ON_ERROR,
            );

        $this->assertStringNotContainsString(
            $secret,
            $serialized,
        );

        $this->assertStringContainsString(
            '[REDACTED]',
            $serialized,
        );

        $this->assertSame(
            'healthy',
            $health
                ->status
                ->value,
        );
    }
}
