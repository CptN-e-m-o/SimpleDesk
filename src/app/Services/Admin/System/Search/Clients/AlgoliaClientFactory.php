<?php

namespace App\Services\Admin\System\Search\Clients;

use Algolia\AlgoliaSearch\Api\SearchClient;

class AlgoliaClientFactory
{
    public function make(string $applicationId, string $apiKey): object
    {
        return SearchClient::create($applicationId, $apiKey);
    }
}
