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
        $this->service = app(QueueDriverCatalogService::class);
        $this->actor = User::factory()->create();
    }

    public function test_create_update_enable_archive_restore_and_force_delete_are_safe_and_audited(): void
    {
        $c = $this->service->create(['name' => 'DB', 'driver' => 'database', 'configuration' => [], 'is_enabled' => true], $this->actor);
        $this->assertSame(360, $c->configuration['retry_after']);
        $c = $this->service->update($c, ['name' => 'DB2', 'driver' => 'database', 'configuration' => [], 'is_enabled' => true], $this->actor);
        $this->service->setEnabled($c, false, $this->actor);
        $this->service->setEnabled($c, true, $this->actor);
        $this->service->archive($c, $this->actor);
        $this->assertFalse($c->refresh()->is_enabled);
        $restored = $this->service->restore($c->id, $this->actor);
        $this->assertFalse($restored->is_enabled);
        $this->service->archive($restored, $this->actor);
        $this->service->forceDelete($restored->id, $this->actor);
        $this->assertSame(['create', 'update', 'disable', 'enable', 'archive', 'restore', 'archive', 'force_delete'], SystemAuditLog::orderBy('id')->pluck('action')->all());
    }

    public function test_driver_is_immutable_and_adapter_errors_are_nested(): void
    {
        $c = $this->service->create(['name' => 'DB', 'driver' => 'database', 'configuration' => [], 'is_enabled' => true], $this->actor);
        try {
            $this->service->update($c, ['name' => 'DB', 'driver' => 'sync', 'configuration' => [], 'is_enabled' => true], $this->actor);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('driver', $e->errors());
        }try {
            $this->service->create(['name' => 'Bad', 'driver' => 'database', 'configuration' => ['retry_after' => 1], 'is_enabled' => true], $this->actor);
            $this->fail();
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('configuration.retry_after', $e->errors());
        }
    }

    public function test_redis_safe_representation_never_exposes_credentials(): void
    {
        $infra = InfrastructureConnection::factory()->create(['credentials' => ['password' => 'do-not-leak']]);
        $c = $this->service->create(['name' => 'Redis', 'driver' => 'redis', 'configuration' => ['infrastructure_connection_id' => $infra->id], 'is_enabled' => true], $this->actor);
        $json = json_encode($this->service->safe($c));
        $this->assertStringNotContainsString('do-not-leak', $json);
        $this->assertArrayNotHasKey('credentials', $this->service->safe($c)['infrastructure_connection']);
    }

    public function test_active_managed_configuration_cannot_be_mutated(): void
    {
        foreach (['update', 'disable', 'archive'] as $operation) {
            $c = $this->service->create(['name' => $operation, 'driver' => 'sync', 'configuration' => [], 'is_enabled' => true], $this->actor);
            QueueDriverSettings::query()->create(['id' => 1, 'mode' => QueueConfigurationMode::Managed, 'active_configuration_id' => $c->id]);
            try {
                match ($operation) {
                    'update' => $this->service->update($c, ['name' => 'x', 'driver' => 'sync', 'configuration' => [], 'is_enabled' => true], $this->actor),'disable' => $this->service->setEnabled($c, false, $this->actor),'archive' => $this->service->archive($c, $this->actor)
                };
                $this->fail();
            } catch (ActiveQueueDriverConfigurationMutationException) {
                $this->addToAssertionCount(1);
            }QueueDriverSettings::query()->delete();
            $c->forceDelete();
        }
    }
}
