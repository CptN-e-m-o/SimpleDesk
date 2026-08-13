<?php

namespace Tests\Unit\Admin\System\Queues;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Queues\Drivers\DatabaseQueueDriverAdapter;
use App\Services\Admin\System\Queues\Drivers\RedisQueueDriverAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QueueDriverValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_rejects_retry_after_below_safe_minimum(): void
    {
        $adapter =
            $this->app->make(
                DatabaseQueueDriverAdapter::class,
            );

        $this->expectException(
            ValidationException::class,
        );

        $adapter->validateAndNormalize([
            'database_connection' => config(
                'database.default',
            ),

            'retry_after' => 329,

            'after_commit' => false,
        ]);
    }

    public function test_database_accepts_minimum_safe_retry_after(): void
    {
        $adapter =
            $this->app->make(
                DatabaseQueueDriverAdapter::class,
            );

        $configuration =
            $adapter->validateAndNormalize([
                'database_connection' => config(
                    'database.default',
                ),

                'retry_after' => 330,

                'after_commit' => false,
            ]);

        $this->assertSame(
            330,
            $configuration[
            'retry_after'
            ],
        );
    }

    public function test_database_accepts_default_retry_after(): void
    {
        $adapter =
            $this->app->make(
                DatabaseQueueDriverAdapter::class,
            );

        $configuration =
            $adapter->validateAndNormalize([
                'database_connection' => config(
                    'database.default',
                ),

                'retry_after' => 360,

                'after_commit' => false,
            ]);

        $this->assertSame(
            360,
            $configuration[
            'retry_after'
            ],
        );
    }

    public function test_redis_rejects_zero_block_for(): void
    {
        $infrastructure =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'is_enabled' => true,
                ]);

        $adapter =
            $this->app->make(
                RedisQueueDriverAdapter::class,
            );

        $this->expectException(
            ValidationException::class,
        );

        $adapter->validateAndNormalize([
            'infrastructure_connection_id' => $infrastructure->id,

            'retry_after' => 360,

            'block_for' => 0,

            'after_commit' => false,
        ]);
    }

    public function test_redis_accepts_null_block_for(): void
    {
        $infrastructure =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'is_enabled' => true,
                ]);

        $adapter =
            $this->app->make(
                RedisQueueDriverAdapter::class,
            );

        $configuration =
            $adapter->validateAndNormalize([
                'infrastructure_connection_id' => $infrastructure->id,

                'retry_after' => 360,

                'block_for' => null,

                'after_commit' => false,
            ]);

        $this->assertNull(
            $configuration[
            'block_for'
            ],
        );
    }

    public function test_redis_accepts_positive_block_for(): void
    {
        $infrastructure =
            InfrastructureConnection::factory()
                ->create([
                    'source' => InfrastructureConnectionSource::Managed,

                    'is_enabled' => true,
                ]);

        $adapter =
            $this->app->make(
                RedisQueueDriverAdapter::class,
            );

        $configuration =
            $adapter->validateAndNormalize([
                'infrastructure_connection_id' => $infrastructure->id,

                'retry_after' => 360,

                'block_for' => 5,

                'after_commit' => false,
            ]);

        $this->assertSame(
            5,
            $configuration[
            'block_for'
            ],
        );
    }
}
