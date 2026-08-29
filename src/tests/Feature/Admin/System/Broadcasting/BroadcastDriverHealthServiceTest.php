<?php

namespace Tests\Feature\Admin\System\Broadcasting;

use App\Contracts\Admin\System\Broadcasting\BroadcastDriverAdapter;
use App\Data\Admin\System\Broadcasting\BroadcastDriverDefinitionData;
use App\Data\Admin\System\Broadcasting\BroadcastHealthResultData;
use App\Data\Admin\System\Broadcasting\BroadcastRuntimeConfigurationData;
use App\Enums\Admin\System\BroadcastDriverType;
use App\Enums\Admin\System\BroadcastHealthStatus;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SystemAuditLog;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Broadcasting\BroadcastDriverHealthService;
use App\Services\Admin\System\Broadcasting\BroadcastDriverRegistry;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class BroadcastDriverHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_health_check_is_persisted_and_audited(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $result = $this->service(
            BroadcastHealthyTestAdapter::class,
        )->test($configuration, $actor);

        $this->assertSame(BroadcastHealthStatus::Healthy, $result->status);
        $this->assertSame(3, $result->latencyMs);
        $this->assertSame('Broadcast target verified.', $result->message);

        $health = $configuration
            ->healthChecks()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(BroadcastHealthStatus::Healthy, $health->status);
        $this->assertSame(3, $health->latency_ms);
        $this->assertSame($actor->id, $health->tested_by);

        $audit = SystemAuditLog::query()
            ->where('area', 'broadcast_driver_configurations')
            ->where('action', 'test')
            ->firstOrFail();

        $this->assertSame('healthy', $audit->metadata['status']);
        $this->assertSame(3, $audit->metadata['latency_ms']);
    }

    public function test_activation_preflight_has_distinct_audit_action(): void
    {
        $configuration = $this->configuration();

        $this->service(
            BroadcastHealthyTestAdapter::class,
        )->preflight($configuration);

        $this->assertSame(
            1,
            SystemAuditLog::query()
                ->where('area', 'broadcast_driver_configurations')
                ->where('action', 'activation_preflight')
                ->count(),
        );
    }

    public function test_validation_failure_returns_specific_safe_message(): void
    {
        $configuration = $this->configuration();

        $result = $this->service(
            BroadcastValidationFailureTestAdapter::class,
        )->test($configuration);

        $this->assertSame(BroadcastHealthStatus::Unavailable, $result->status);
        $this->assertSame(
            'The Broadcast profile is structurally invalid.',
            $result->message,
        );

        $health = $configuration
            ->healthChecks()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(BroadcastHealthStatus::Unavailable, $health->status);
        $this->assertSame($result->message, $health->message);
    }

    public function test_unexpected_exception_is_sanitized(): void
    {
        $configuration = $this->configuration();

        $result = $this->service(
            BroadcastUnexpectedFailureTestAdapter::class,
        )->test($configuration);

        $this->assertSame(BroadcastHealthStatus::Unavailable, $result->status);
        $this->assertSame(
            'Broadcast target could not be verified.',
            $result->message,
        );

        $this->assertStringNotContainsString(
            'reverb.internal',
            $result->message,
        );

        $health = $configuration
            ->healthChecks()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            'Broadcast target could not be verified.',
            $health->message,
        );
    }

    public function test_infrastructure_secrets_are_redacted_before_persistence(): void
    {
        $appKey = 'super-secret-broadcast-key';
        $appSecret = 'super-secret-broadcast-secret';

        $connection = InfrastructureConnection::factory()->create([
            'credentials' => [
                'app_key' => $appKey,
                'app_secret' => $appSecret,
            ],
        ]);

        $configuration = $this->configuration(
            infrastructureConnectionId: $connection->id,
        );

        $result = $this->service(
            BroadcastSecretLeakingTestAdapter::class,
        )->test($configuration);

        $this->assertSame(BroadcastHealthStatus::Unhealthy, $result->status);
        $this->assertStringNotContainsString($appKey, $result->message);
        $this->assertStringNotContainsString($appSecret, $result->message);
        $this->assertSame('[REDACTED]', $result->details['app_key']);
        $this->assertSame('[REDACTED]', $result->details['app_secret']);

        $health = $configuration
            ->healthChecks()
            ->latest('id')
            ->firstOrFail();

        $this->assertStringNotContainsString($appKey, $health->message);
        $this->assertStringNotContainsString($appSecret, $health->message);
        $this->assertSame('[REDACTED]', $health->details['app_key']);
        $this->assertSame('[REDACTED]', $health->details['app_secret']);

        $audit = SystemAuditLog::query()
            ->where('area', 'broadcast_driver_configurations')
            ->where('action', 'test')
            ->latest('id')
            ->firstOrFail();

        $auditPayload = json_encode($audit->toArray(), JSON_THROW_ON_ERROR);

        $this->assertStringNotContainsString($appKey, $auditPayload);
        $this->assertStringNotContainsString($appSecret, $auditPayload);
    }

    public function test_corrupted_encrypted_credentials_return_unavailable_instead_of_crashing(): void
    {
        $connection = InfrastructureConnection::factory()->create([
            'credentials' => [
                'app_key' => 'valid-key',
                'app_secret' => 'valid-secret',
            ],
        ]);

        DB::table('infrastructure_connections')
            ->where('id', $connection->id)
            ->update([
                'credentials' => 'corrupted-encrypted-value',
            ]);

        $configuration = $this->configuration(
            infrastructureConnectionId: $connection->id,
        );

        $result = $this->service(
            BroadcastHealthyTestAdapter::class,
        )->test($configuration);

        $this->assertSame(BroadcastHealthStatus::Unavailable, $result->status);
        $this->assertSame(
            'Broadcast target could not be verified.',
            $result->message,
        );

        $health = $configuration
            ->healthChecks()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(BroadcastHealthStatus::Unavailable, $health->status);
        $this->assertSame(
            'Broadcast target could not be verified.',
            $health->message,
        );
    }

    private function service(string $adapter): BroadcastDriverHealthService
    {
        $registry = new BroadcastDriverRegistry(
            container: $this->app,
            adapters: [
                BroadcastDriverType::Reverb->value => $adapter,
            ],
        );

        return new BroadcastDriverHealthService(
            registry: $registry,
            redactor: app(InfrastructureSecretRedactor::class),
            audit: app(SystemAuditLogger::class),
        );
    }

    private function configuration(
        ?int $infrastructureConnectionId = null,
    ): BroadcastDriverConfiguration {
        $actor = User::factory()->create();

        return BroadcastDriverConfiguration::query()->create([
            'name' => 'Broadcast Health Test',
            'driver' => BroadcastDriverType::Reverb,
            'infrastructure_connection_id' => $infrastructureConnectionId,
            'configuration' => [],
            'is_enabled' => true,
            'created_by' => $actor->id,
            'updated_by' => $actor->id,
        ]);
    }
}

class BroadcastHealthyTestAdapter implements BroadcastDriverAdapter
{
    public function type(): BroadcastDriverType
    {
        return BroadcastDriverType::Reverb;
    }

    public function definition(): BroadcastDriverDefinitionData
    {
        return new BroadcastDriverDefinitionData(
            type: BroadcastDriverType::Reverb,
            name: 'Reverb',
            description: 'Test adapter.',
            available: true,
        );
    }

    public function validateAndNormalize(
        array $configuration,
        mixed $infrastructureConnectionId,
    ): array {
        return [
            'configuration' => $configuration,
            'infrastructure_connection_id' => $infrastructureConnectionId,
        ];
    }

    public function runtimeConfiguration(
        BroadcastDriverConfiguration $configuration,
    ): BroadcastRuntimeConfigurationData {
        return new BroadcastRuntimeConfigurationData(
            connection: [
                'driver' => 'null',
            ],
            client: [],
        );
    }

    public function test(
        BroadcastDriverConfiguration $configuration,
    ): BroadcastHealthResultData {
        return new BroadcastHealthResultData(
            status: BroadcastHealthStatus::Healthy,
            latencyMs: 3,
            message: 'Broadcast target verified.',
            details: [
                'safe' => true,
            ],
        );
    }
}

class BroadcastValidationFailureTestAdapter extends BroadcastHealthyTestAdapter
{
    public function test(
        BroadcastDriverConfiguration $configuration,
    ): BroadcastHealthResultData {
        throw ValidationException::withMessages([
            'configuration' => 'The Broadcast profile is structurally invalid.',
        ]);
    }
}

class BroadcastUnexpectedFailureTestAdapter extends BroadcastHealthyTestAdapter
{
    public function test(
        BroadcastDriverConfiguration $configuration,
    ): BroadcastHealthResultData {
        throw new \RuntimeException(
            'Unable to reach reverb.internal with private credentials.',
        );
    }
}

class BroadcastSecretLeakingTestAdapter extends BroadcastHealthyTestAdapter
{
    public function test(
        BroadcastDriverConfiguration $configuration,
    ): BroadcastHealthResultData {
        $secrets = $configuration
            ->infrastructureConnection
            ?->secrets() ?? [];

        $appKey = $secrets['app_key'] ?? '';
        $appSecret = $secrets['app_secret'] ?? '';

        return new BroadcastHealthResultData(
            status: BroadcastHealthStatus::Unhealthy,
            latencyMs: 7,
            message: 'Authentication failed with '.$appKey.' and '.$appSecret,
            details: [
                'app_key' => $appKey,
                'app_secret' => $appSecret,
            ],
        );
    }
}
