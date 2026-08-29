<?php

namespace Tests\Unit\Admin\System\Search;

use App\Services\Admin\System\Search\Clients\MeilisearchClientFactory;
use GuzzleHttp\Client as GuzzleClient;
use Meilisearch\Client;
use Psr\Http\Client\ClientInterface;
use Tests\TestCase;

class MeilisearchClientFactoryTest extends TestCase
{
    public function test_factory_builds_bounded_psr_18_http_client(): void
    {
        config()->set('simpledesk-search.provider_health.connect_timeout_seconds', 3.0);
        config()->set('simpledesk-search.provider_health.request_timeout_seconds', 5.0);
        $factory = new class extends MeilisearchClientFactory
        {
            public function httpClient(): ClientInterface
            {
                return $this->makeHttpClient();
            }
        };
        $http = $factory->httpClient();
        $this->assertInstanceOf(GuzzleClient::class, $http);
        $this->assertSame(3.0, $http->getConfig('connect_timeout'));
        $this->assertSame(5.0, $http->getConfig('timeout'));
        $this->assertInstanceOf(Client::class, $factory->make('https://search.example.test', 'secret'));
    }
}
