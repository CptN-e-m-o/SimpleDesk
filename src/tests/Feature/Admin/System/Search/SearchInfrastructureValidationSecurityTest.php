<?php

namespace Tests\Feature\Admin\System\Search;

use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Infrastructure\InfrastructureConnectionCatalogService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SearchInfrastructureValidationSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_meilisearch_secret_is_encrypted_and_never_public(): void
    {
        $actor = User::factory()->create();
        $connection = app(InfrastructureConnectionCatalogService::class)->create(['name' => 'Meili', 'type' => 'meilisearch', 'source' => 'managed', 'configuration' => ['host' => 'https://search.example.test/'], 'credentials' => ['api_key' => 'super-secret-key'], 'is_enabled' => true], $actor);
        $safe = app(InfrastructureConnectionCatalogService::class)->safe($connection);
        $this->assertSame('https://search.example.test', $safe['configuration']['host']);
        $this->assertTrue($safe['credential_flags']['api_key_configured']);
        $this->assertStringNotContainsString('super-secret-key', json_encode($safe));
        $raw = InfrastructureConnection::query()->getQuery()->where('id', $connection->id)->value('credentials');
        $this->assertStringNotContainsString('super-secret-key', $raw);
    }

    public function test_meilisearch_rejects_credentials_in_host_url(): void
    {
        $actor = User::factory()->create();
        $this->expectException(ValidationException::class);
        app(InfrastructureConnectionCatalogService::class)->create(['name' => 'Invalid', 'type' => 'meilisearch', 'source' => 'managed', 'configuration' => ['host' => 'https://user:pass@search.example.test'], 'credentials' => ['api_key' => 'key'], 'is_enabled' => true], $actor);
    }
}
