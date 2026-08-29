<?php

namespace App\Services\Admin\System\Search\Clients;

use GuzzleHttp\Client as GuzzleClient;
use Meilisearch\Client;
use Psr\Http\Client\ClientInterface;

class MeilisearchClientFactory
{
    public function __construct(private readonly ?ClientInterface $httpClient = null) {}

    public function make(string $host, string $apiKey): object
    {
        return new Client($host, $apiKey, $this->httpClient ?? $this->makeHttpClient());
    }

    protected function makeHttpClient(): ClientInterface
    {
        return new GuzzleClient([
            'connect_timeout' => (float) config('simpledesk-search.provider_health.connect_timeout_seconds', 3.0),
            'timeout' => (float) config('simpledesk-search.provider_health.request_timeout_seconds', 5.0),
        ]);
    }
}
