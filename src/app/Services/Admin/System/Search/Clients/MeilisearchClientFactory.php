<?php

namespace App\Services\Admin\System\Search\Clients;

use Meilisearch\Client;

class MeilisearchClientFactory
{
    public function make(string $host, string $apiKey): object
    {
        return new Client($host, $apiKey);
    }
}
