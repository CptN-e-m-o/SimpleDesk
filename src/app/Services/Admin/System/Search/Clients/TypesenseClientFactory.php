<?php

namespace App\Services\Admin\System\Search\Clients;

use Typesense\Client;

class TypesenseClientFactory
{
    public function make(array $configuration): object
    {
        return new Client($configuration);
    }

    public function makeForHealth(array $configuration): object
    {
        return new Client($this->healthConfiguration($configuration));
    }

    protected function healthConfiguration(array $configuration): array
    {
        return [
            ...$configuration,
            'connection_timeout_seconds' => (float) config(
                'simpledesk-search.provider_health.typesense.connection_timeout_seconds',
                2.0,
            ),
            'num_retries' => (int) config(
                'simpledesk-search.provider_health.typesense.num_retries',
                1,
            ),
            'retry_interval_seconds' => (float) config(
                'simpledesk-search.provider_health.typesense.retry_interval_seconds',
                0.25,
            ),
        ];
    }
}
