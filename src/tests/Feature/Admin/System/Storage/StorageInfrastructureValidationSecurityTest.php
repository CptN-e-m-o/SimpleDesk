<?php

namespace Tests\Feature\Admin\System\Storage;

use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class StorageInfrastructureValidationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_aws_secrets_are_encrypted_redacted_and_blank_update_preserves_them(): void
    {
        $actor = User::factory()->create();
        $service = $this->app->make(InfrastructureConnectionCatalogService::class);
        $connection = $service->create(['name' => 'AWS', 'type' => 'aws', 'source' => 'managed', 'configuration' => ['region' => 'us-east-1', 'bucket' => 'private'], 'credentials' => ['access_key_id' => 'ACCESS', 'secret_access_key' => 'SECRET'], 'is_enabled' => true], $actor);
        $this->assertStringNotContainsString('SECRET', (string) $connection->getRawOriginal('credentials'));
        $this->assertArrayNotHasKey('credentials', $service->safe($connection));
        $updated = $service->update($connection, ['name' => 'Renamed', 'source' => 'managed', 'configuration' => $connection->configuration, 'credentials' => ['access_key_id' => '', 'secret_access_key' => ''], 'is_enabled' => true], $actor);
        $this->assertSame('SECRET', $updated->secrets()['secret_access_key']);
    }

    public function test_s3_compatible_endpoint_rejects_embedded_credentials(): void
    {
        $this->expectException(ValidationException::class);
        $this->app->make(InfrastructureConnectionCatalogService::class)->create(['name' => 'Bad', 'type' => InfrastructureConnectionType::S3Compatible->value, 'source' => InfrastructureConnectionSource::Managed->value, 'configuration' => ['endpoint' => 'https://user:pass@example.com', 'region' => 'us-east-1', 'bucket' => 'private', 'use_path_style_endpoint' => true], 'credentials' => ['access_key_id' => 'id', 'secret_access_key' => 'secret'], 'is_enabled' => true], User::factory()->create());
    }
}
