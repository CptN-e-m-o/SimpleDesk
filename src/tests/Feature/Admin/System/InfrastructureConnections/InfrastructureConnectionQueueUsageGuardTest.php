<?php

namespace Tests\Feature\Admin\System\InfrastructureConnections;

use App\Enums\Admin\System\QueueConfigurationMode;
use App\Enums\Admin\System\QueueDriverType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class InfrastructureConnectionQueueUsageGuardTest extends TestCase
{
    use RefreshDatabase;

    private InfrastructureConnectionCatalogService $service;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(
            InfrastructureConnectionCatalogService::class,
        );

        $this->actor = User::factory()->create();
    }

    public function test_active_managed_queue_connection_cannot_be_disabled(): void
    {
        [$connection] = $this->activeManagedRedisQueue();

        try {
            $this->service->setEnabled(
                $connection,
                false,
                $this->actor,
            );

            $this->fail(
                'Active managed Queue infrastructure should not be disabled.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'is_enabled',
                $exception->errors(),
            );
        }

        $this->assertTrue(
            $connection->refresh()->is_enabled,
        );
    }

    public function test_active_managed_queue_connection_cannot_be_archived(): void
    {
        [$connection] = $this->activeManagedRedisQueue();

        try {
            $this->service->archive(
                $connection,
                $this->actor,
            );

            $this->fail(
                'Active managed Queue infrastructure should not be archived.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'connection',
                $exception->errors(),
            );
        }

        $this->assertFalse(
            $connection->refresh()->trashed(),
        );
    }

    public function test_update_cannot_bypass_active_queue_disable_guard(): void
    {
        [$connection] = $this->activeManagedRedisQueue();

        try {
            $this->service->update(
                $connection,
                [
                    'name' => $connection->name,
                    'source' => $connection->source->value,
                    'configuration' => $connection->configuration,
                    'credentials' => [],
                    'is_enabled' => false,
                ],
                $this->actor,
            );

            $this->fail(
                'Update should not bypass the active Queue disable guard.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'is_enabled',
                $exception->errors(),
            );
        }

        $this->assertTrue(
            $connection->refresh()->is_enabled,
        );
    }

    public function test_active_managed_queue_connection_allows_metadata_only_update(): void
    {
        [$connection] = $this->activeManagedRedisQueue();

        $updated = $this->service->update(
            $connection,
            [
                'name' => 'Renamed Redis connection',
                'source' => $connection->source->value,
                'configuration' => $connection->configuration,
                'credentials' => [],
                'is_enabled' => true,
            ],
            $this->actor,
        );

        $this->assertSame(
            'Renamed Redis connection',
            $updated->name,
        );

        $this->assertTrue(
            $updated->is_enabled,
        );
    }

    public function test_active_managed_queue_connection_runtime_settings_cannot_be_changed(): void
    {
        [$connection] = $this->activeManagedRedisQueue();

        $configuration = $connection->configuration;
        $configuration['host'] = 'other-redis.internal';

        try {
            $this->service->update(
                $connection,
                [
                    'name' => $connection->name,
                    'source' => $connection->source->value,
                    'configuration' => $configuration,
                    'credentials' => [],
                    'is_enabled' => true,
                ],
                $this->actor,
            );

            $this->fail(
                'Runtime settings of active Queue infrastructure should be immutable.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'configuration',
                $exception->errors(),
            );
        }

        $this->assertSame(
            '127.0.0.1',
            $connection->refresh()->configuration['host'],
        );
    }

    public function test_inactive_queue_reference_does_not_block_disable_or_archive(): void
    {
        $connection = InfrastructureConnection::factory()
            ->create();

        $this->queueUsing(
            $connection,
        );

        $disabled = $this->service->setEnabled(
            $connection,
            false,
            $this->actor,
        );

        $this->assertFalse(
            $disabled->is_enabled,
        );

        $this->service->archive(
            $disabled,
            $this->actor,
        );

        $this->assertSoftDeleted(
            'infrastructure_connections',
            [
                'id' => $connection->id,
            ],
        );
    }

    public function test_force_delete_is_blocked_while_any_queue_configuration_references_connection(): void
    {
        $connection = InfrastructureConnection::factory()
            ->create();

        $queue = $this->queueUsing(
            $connection,
        );

        $queue->delete();

        $this->service->archive(
            $connection,
            $this->actor,
        );

        try {
            $this->service->forceDelete(
                $connection->id,
                $this->actor,
            );

            $this->fail(
                'Referenced Infrastructure Connection should not be permanently deleted.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'connection',
                $exception->errors(),
            );
        }

        $this->assertSoftDeleted(
            'infrastructure_connections',
            [
                'id' => $connection->id,
            ],
        );
    }

    public function test_unreferenced_archived_connection_can_be_force_deleted(): void
    {
        $connection = InfrastructureConnection::factory()
            ->create();

        $this->service->archive(
            $connection,
            $this->actor,
        );

        $this->service->forceDelete(
            $connection->id,
            $this->actor,
        );

        $this->assertDatabaseMissing(
            'infrastructure_connections',
            [
                'id' => $connection->id,
            ],
        );
    }

    /**
     * @return array{
     *     0: InfrastructureConnection,
     *     1: QueueDriverConfiguration
     * }
     */
    private function activeManagedRedisQueue(): array
    {
        $connection = InfrastructureConnection::factory()
            ->create();

        $queue = $this->queueUsing(
            $connection,
        );

        QueueDriverSettings::query()->create([
            'id' => QueueDriverSettings::SINGLETON_ID,
            'mode' => QueueConfigurationMode::Managed,
            'active_configuration_id' => $queue->id,
            'worker_restart_required' => false,
        ]);

        return [
            $connection,
            $queue,
        ];
    }

    private function queueUsing(
        InfrastructureConnection $connection,
    ): QueueDriverConfiguration {
        return QueueDriverConfiguration::query()
            ->create([
                'name' => 'Redis Queue',
                'driver' => QueueDriverType::Redis,
                'infrastructure_connection_id' => $connection->id,
                'configuration' => [
                    'retry_after' => 360,
                    'block_for' => 5,
                    'after_commit' => false,
                ],
                'is_enabled' => true,
            ]);
    }
}
