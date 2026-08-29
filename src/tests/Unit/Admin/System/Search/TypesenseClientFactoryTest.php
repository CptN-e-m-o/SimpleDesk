<?php

namespace Tests\Unit\Admin\System\Search;

use App\Services\Admin\System\Search\Clients\TypesenseClientFactory;
use Tests\TestCase;

class TypesenseClientFactoryTest extends TestCase
{
    public function test_health_configuration_uses_bounded_retry_settings(): void
    {
        config()->set(
            'simpledesk-search.provider_health.typesense.connection_timeout_seconds',
            2.0,
        );

        config()->set(
            'simpledesk-search.provider_health.typesense.num_retries',
            1,
        );

        config()->set(
            'simpledesk-search.provider_health.typesense.retry_interval_seconds',
            0.25,
        );

        $factory = new class extends TypesenseClientFactory
        {
            public function bounded(array $configuration): array
            {
                return $this->healthConfiguration(
                    $configuration,
                );
            }
        };

        $configuration = $factory->bounded([
            'api_key' => 'secret',
            'nodes' => [
                [
                    'host' => 'typesense.example.test',
                    'port' => 8108,
                    'protocol' => 'https',
                    'path' => '',
                ],
            ],
            'connection_timeout_seconds' => 60.0,
            'healthcheck_interval_seconds' => 3600,
            'num_retries' => 10,
            'retry_interval_seconds' => 60.0,
        ]);

        $this->assertSame(
            2.0,
            $configuration['connection_timeout_seconds'],
        );

        $this->assertSame(
            1,
            $configuration['num_retries'],
        );

        $this->assertSame(
            0.25,
            $configuration['retry_interval_seconds'],
        );

        $this->assertSame(
            'secret',
            $configuration['api_key'],
        );

        $this->assertSame(
            'typesense.example.test',
            $configuration['nodes'][0]['host'],
        );
    }
}
