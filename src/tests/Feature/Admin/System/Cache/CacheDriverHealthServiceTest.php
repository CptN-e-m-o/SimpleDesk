<?php

namespace Tests\Feature\Admin\System\Cache;

use App\Contracts\Admin\System\Cache\CacheDriverAdapter;
use App\Data\Admin\System\Cache\CacheDriverDefinitionData;
use App\Data\Admin\System\Cache\CacheHealthResultData;
use App\Data\Admin\System\Cache\CacheRuntimeConfigurationData;
use App\Enums\Admin\System\CacheDriverType;
use App\Enums\Admin\System\CacheHealthStatus;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\SystemAuditLog;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use App\Services\Admin\System\Cache\CacheDriverHealthService;
use App\Services\Admin\System\Cache\CacheDriverRegistry;
use App\Services\Admin\System\Infrastructure\InfrastructureSecretRedactor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class CacheDriverHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_health_check_is_persisted_and_audited(): void
    {
        $actor = User::factory()->create();
        $configuration = $this->configuration();

        $result = $this->service(
            CacheHealthyTestAdapter::class,
        )->test(
            $configuration,
            $actor,
        );

        $this->assertSame(
            CacheHealthStatus::Healthy,
            $result->status,
        );

        $health = $configuration
            ->healthChecks()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            CacheHealthStatus::Healthy,
            $health->status,
        );

        $this->assertSame(
            $actor->id,
            $health->tested_by,
        );

        $audit = SystemAuditLog::query()
            ->where('area', 'cache_driver_configurations')
            ->where('action', 'test')
            ->firstOrFail();

        $this->assertSame(
            'healthy',
            $audit->metadata['status'],
        );
    }

    public function test_activation_preflight_has_distinct_audit_action(): void
    {
        $configuration = $this->configuration();

        $this->service(
            CacheHealthyTestAdapter::class,
        )->preflight(
            $configuration,
        );

        $this->assertSame(
            1,
            SystemAuditLog::query()
                ->where(
                    'action',
                    'activation_preflight',
                )
                ->count(),
        );
    }

    public function test_validation_failure_returns_specific_safe_message(): void
    {
        $configuration = $this->configuration();

        $result = $this->service(
            CacheValidationFailureTestAdapter::class,
        )->test(
            $configuration,
        );

        $this->assertSame(
            CacheHealthStatus::Unavailable,
            $result->status,
        );

        $this->assertSame(
            'The Cache profile is structurally invalid.',
            $result->message,
        );

        $health = $configuration
            ->healthChecks()
            ->latest('id')
            ->firstOrFail();

        $this->assertSame(
            $result->message,
            $health->message,
        );
    }

    public function test_unexpected_exception_is_sanitized(): void
    {
        $configuration = $this->configuration();

        $result = $this->service(
            CacheUnexpectedFailureTestAdapter::class,
        )->test(
            $configuration,
        );

        $this->assertSame(
            CacheHealthStatus::Unavailable,
            $result->status,
        );

        $this->assertSame(
            'Cache target could not be verified.',
            $result->message,
        );

        $this->assertStringNotContainsString(
            'internal-host',
            $result->message,
        );
    }

    public function test_infrastructure_secrets_are_redacted_before_persistence(): void
    {
        $secret = 'super-secret-cache-password';

        $connection = InfrastructureConnection::factory()->create([
            'credentials' => [
                'password' => $secret,
            ],
        ]);

        $configuration = $this->configuration(
            infrastructureConnectionId: $connection->id,
        );

        $result = $this->service(
            CacheSecretLeakingTestAdapter::class,
        )->test(
            $configuration,
        );

        $this->assertStringNotContainsString(
            $secret,
            $result->message,
        );

        $this->assertSame(
            '[REDACTED]',
            $result->details['password'],
        );

        $health = $configuration
            ->healthChecks()
            ->latest('id')
            ->firstOrFail();

        $this->assertStringNotContainsString(
            $secret,
            $health->message,
        );

        $this->assertSame(
            '[REDACTED]',
            $health->details['password'],
        );
    }

    private function service(
        string $adapter,
    ): CacheDriverHealthService {
        $registry = new CacheDriverRegistry(
            container: $this->app,
            adapters: [
                CacheDriverType::Redis->value => $adapter,
            ],
        );

        return new CacheDriverHealthService(
            registry: $registry,
            redactor: app(
                InfrastructureSecretRedactor::class,
            ),
            audit: app(
                SystemAuditLogger::class,
            ),
        );
    }

    private function configuration(
        ?int $infrastructureConnectionId = null,
    ): CacheDriverConfiguration {
        return CacheDriverConfiguration::query()->create([
            'name' => 'Cache Health Test',
            'driver' => CacheDriverType::Redis,
            'infrastructure_connection_id' => $infrastructureConnectionId,
            'configuration' => [],
            'is_enabled' => true,
        ]);
    }
}

class CacheHealthyTestAdapter implements CacheDriverAdapter
{
    public function type(): CacheDriverType
    {
        return CacheDriverType::Redis;
    }

    public function definition(): CacheDriverDefinitionData
    {
        return new CacheDriverDefinitionData(
            type: CacheDriverType::Redis,
            label: 'Redis',
            description: 'Test adapter.',
            requiresInfrastructure: false,
            infrastructureType: null,
            recommendedForProduction: true,
        );
    }

    public function validateAndNormalize(
        array $configuration,
    ): array {
        return $configuration;
    }

    public function runtimeConfiguration(
        CacheDriverConfiguration $configuration,
    ): CacheRuntimeConfigurationData {
        return new CacheRuntimeConfigurationData([
            'driver' => 'array',
        ]);
    }

    public function test(
        CacheDriverConfiguration $configuration,
    ): CacheHealthResultData {
        return new CacheHealthResultData(
            status: CacheHealthStatus::Healthy,
            latencyMs: 3,
            message: 'Cache target verified.',
            details: [
                'safe' => true,
            ],
        );
    }
}

class CacheValidationFailureTestAdapter extends CacheHealthyTestAdapter
{
    public function test(
        CacheDriverConfiguration $configuration,
    ): CacheHealthResultData {
        throw ValidationException::withMessages([
            'configuration' => 'The Cache profile is structurally invalid.',
        ]);
    }
}

class CacheUnexpectedFailureTestAdapter extends CacheHealthyTestAdapter
{
    public function test(
        CacheDriverConfiguration $configuration,
    ): CacheHealthResultData {
        throw new \RuntimeException(
            'Unable to reach internal-host:6379.',
        );
    }
}

class CacheSecretLeakingTestAdapter extends CacheHealthyTestAdapter
{
    public function test(
        CacheDriverConfiguration $configuration,
    ): CacheHealthResultData {
        $secret = $configuration
            ->infrastructureConnection
            ?->secrets()['password'] ?? '';

        return new CacheHealthResultData(
            status: CacheHealthStatus::Unhealthy,
            latencyMs: 7,
            message: 'Redis authentication failed with '.$secret,
            details: [
                'password' => $secret,
            ],
        );
    }
}
