<?php

namespace Tests\Feature\Admin\System\Queues;

use App\Enums\Admin\System\QueueConfigurationMode;
use App\Exceptions\Admin\System\Queues\ActiveQueueDriverConfigurationMutationException;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverSettings;
use App\Models\Admin\System\SystemAuditLog;
use App\Models\User\User;
use App\Services\Admin\System\Queues\QueueDriverCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class QueueDriverCatalogServiceTest extends TestCase
{
    use RefreshDatabase;

    private QueueDriverCatalogService $service;

    private User $actor;

    protected function setUp(): void
    {
        parent::setUp();

        $this->service = app(
            QueueDriverCatalogService::class,
        );

        $this->actor = User::factory()->create();
    }

    public function test_create_update_enable_archive_restore_and_force_delete_are_safe_and_audited(): void
    {
        $configuration = $this->service->create(
            [
                'name' => 'DB',
                'driver' => 'database',
                'configuration' => [],
                'is_enabled' => true,
            ],
            $this->actor,
        );

        $this->assertSame(
            360,
            $configuration->configuration['retry_after'],
        );

        $this->assertNull(
            $configuration->infrastructure_connection_id,
        );

        $configuration = $this->service->update(
            $configuration,
            [
                'name' => 'DB2',
                'driver' => 'database',
                'configuration' => [],
                'is_enabled' => true,
            ],
            $this->actor,
        );

        $this->service->setEnabled(
            $configuration,
            false,
            $this->actor,
        );

        $this->service->setEnabled(
            $configuration,
            true,
            $this->actor,
        );

        $this->service->archive(
            $configuration,
            $this->actor,
        );

        $this->assertFalse(
            $configuration->refresh()->is_enabled,
        );

        $restored = $this->service->restore(
            $configuration->id,
            $this->actor,
        );

        $this->assertFalse(
            $restored->is_enabled,
        );

        $this->service->archive(
            $restored,
            $this->actor,
        );

        $this->service->forceDelete(
            $restored->id,
            $this->actor,
        );

        $this->assertSame(
            [
                'create',
                'update',
                'disable',
                'enable',
                'archive',
                'restore',
                'archive',
                'force_delete',
            ],
            SystemAuditLog::query()
                ->orderBy('id')
                ->pluck('action')
                ->all(),
        );
    }

    public function test_driver_is_immutable_and_adapter_errors_are_nested(): void
    {
        $configuration = $this->service->create(
            [
                'name' => 'DB',
                'driver' => 'database',
                'configuration' => [],
                'is_enabled' => true,
            ],
            $this->actor,
        );

        try {
            $this->service->update(
                $configuration,
                [
                    'name' => 'DB',
                    'driver' => 'sync',
                    'configuration' => [],
                    'is_enabled' => true,
                ],
                $this->actor,
            );

            $this->fail(
                'Changing the Queue driver should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'driver',
                $exception->errors(),
            );
        }

        try {
            $this->service->create(
                [
                    'name' => 'Bad',
                    'driver' => 'database',
                    'configuration' => [
                        'retry_after' => 1,
                    ],
                    'is_enabled' => true,
                ],
                $this->actor,
            );

            $this->fail(
                'Unsafe retry_after should be rejected.',
            );
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey(
                'configuration.retry_after',
                $exception->errors(),
            );
        }
    }

    public function test_redis_safe_representation_never_exposes_credentials(): void
    {
        $infrastructure = InfrastructureConnection::factory()
            ->create([
                'credentials' => [
                    'password' => 'do-not-leak',
                ],
                'is_enabled' => true,
            ]);

        $configuration = $this->service->create(
            [
                'name' => 'Redis',
                'driver' => 'redis',

                'infrastructure_connection_id' => $infrastructure->id,

                'configuration' => [
                    'retry_after' => 360,
                    'block_for' => 5,
                    'after_commit' => false,
                ],

                'is_enabled' => true,
            ],
            $this->actor,
        );

        $safe = $this->service->safe(
            $configuration,
        );

        $json = json_encode(
            $safe,
            JSON_THROW_ON_ERROR,
        );

        $this->assertStringNotContainsString(
            'do-not-leak',
            $json,
        );

        $this->assertSame(
            $infrastructure->id,
            $safe['infrastructure_connection_id'],
        );

        $this->assertArrayNotHasKey(
            'infrastructure_connection_id',
            $safe['configuration'],
        );

        $this->assertArrayNotHasKey(
            'credentials',
            $safe['infrastructure_connection'],
        );

        $this->assertSame(
            $infrastructure->id,
            $safe['infrastructure_connection']['id'],
        );
    }

    public function test_active_managed_configuration_cannot_be_mutated(): void
    {
        foreach (
            [
                'update',
                'disable',
                'archive',
            ] as $operation
        ) {
            $configuration = $this->service->create(
                [
                    'name' => $operation,
                    'driver' => 'sync',
                    'configuration' => [],
                    'is_enabled' => true,
                ],
                $this->actor,
            );

            QueueDriverSettings::query()->create([
                'id' => QueueDriverSettings::SINGLETON_ID,
                'mode' => QueueConfigurationMode::Managed,
                'active_configuration_id' => $configuration->id,
            ]);

            try {
                match ($operation) {
                    'update' => $this->service->update(
                        $configuration,
                        [
                            'name' => 'x',
                            'driver' => 'sync',
                            'configuration' => [],
                            'is_enabled' => true,
                        ],
                        $this->actor,
                    ),

                    'disable' => $this->service->setEnabled(
                        $configuration,
                        false,
                        $this->actor,
                    ),

                    'archive' => $this->service->archive(
                        $configuration,
                        $this->actor,
                    ),
                };

                $this->fail(
                    "Active managed Queue configuration operation [{$operation}] should be rejected.",
                );
            } catch (
                ActiveQueueDriverConfigurationMutationException
            ) {
                $this->addToAssertionCount(1);
            }

            QueueDriverSettings::query()->delete();

            $configuration->forceDelete();
        }
    }
}
