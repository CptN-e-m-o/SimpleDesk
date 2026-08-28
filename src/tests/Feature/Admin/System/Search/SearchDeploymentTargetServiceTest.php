<?php

namespace Tests\Feature\Admin\System\Search;

use App\Services\Admin\System\Search\SearchDeploymentConfigurationSnapshot;
use App\Services\Admin\System\Search\SearchDeploymentTargetService;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SearchDeploymentTargetServiceTest extends TestCase
{
    public function test_snapshot_drives_target_after_runtime_config_mutation(): void
    {
        $snapshot = new SearchDeploymentConfigurationSnapshot;
        $snapshot->capture([
            'driver' => 'collection',
        ]);

        app()->instance(
            SearchDeploymentConfigurationSnapshot::class,
            $snapshot,
        );

        config()->set(
            'scout.driver',
            'simpledesk-managed',
        );

        $this->assertSame(
            'collection',
            app(SearchDeploymentTargetService::class)
                ->resolve()['driver'],
        );
    }

    public function test_unknown_deployment_driver_is_structurally_rejected(): void
    {
        $this->bindSnapshot([
            'driver' => 'unknown',
        ]);

        $this->expectException(
            ValidationException::class,
        );

        app(SearchDeploymentTargetService::class)
            ->resolve();
    }

    public function test_malformed_meilisearch_host_is_structurally_rejected(): void
    {
        $this->bindSnapshot([
            'driver' => 'meilisearch',
            'meilisearch' => [
                'host' => 'not-a-url',
                'key' => 'secret',
            ],
        ]);

        $this->expectException(
            ValidationException::class,
        );

        app(SearchDeploymentTargetService::class)
            ->resolve();
    }

    public function test_meilisearch_host_with_credentials_is_structurally_rejected(): void
    {
        $this->bindSnapshot([
            'driver' => 'meilisearch',
            'meilisearch' => [
                'host' => 'https://user:password@search.example.test',
                'key' => 'secret',
            ],
        ]);

        $this->expectException(
            ValidationException::class,
        );

        app(SearchDeploymentTargetService::class)
            ->resolve();
    }

    public function test_malformed_typesense_node_is_structurally_rejected(): void
    {
        $this->bindSnapshot([
            'driver' => 'typesense',
            'typesense' => [
                'client-settings' => [
                    'api_key' => 'secret',
                    'nodes' => [
                        [
                            'host' => '',
                            'port' => 8108,
                            'protocol' => 'http',
                            'path' => '',
                        ],
                    ],
                    'connection_timeout_seconds' => 2,
                    'healthcheck_interval_seconds' => 30,
                    'num_retries' => 1,
                    'retry_interval_seconds' => 1,
                ],
            ],
        ]);

        $this->expectException(
            ValidationException::class,
        );

        app(SearchDeploymentTargetService::class)
            ->resolve();
    }

    public function test_invalid_typesense_protocol_is_structurally_rejected(): void
    {
        $this->bindSnapshot([
            'driver' => 'typesense',
            'typesense' => [
                'client-settings' => [
                    'api_key' => 'secret',
                    'nodes' => [
                        [
                            'host' => 'typesense.example.test',
                            'port' => 8108,
                            'protocol' => 'ftp',
                            'path' => '',
                        ],
                    ],
                    'connection_timeout_seconds' => 2,
                    'healthcheck_interval_seconds' => 30,
                    'num_retries' => 1,
                    'retry_interval_seconds' => 1,
                ],
            ],
        ]);

        $this->expectException(
            ValidationException::class,
        );

        app(SearchDeploymentTargetService::class)
            ->resolve();
    }

    public function test_valid_typesense_target_is_normalized(): void
    {
        $this->bindSnapshot([
            'driver' => 'typesense',
            'typesense' => [
                'client-settings' => [
                    'api_key' => 'secret',
                    'nodes' => [
                        [
                            'host' => ' typesense.example.test ',
                            'port' => 8108,
                            'protocol' => 'https',
                            'path' => ' ',
                        ],
                    ],
                    'connection_timeout_seconds' => 2,
                    'healthcheck_interval_seconds' => 30,
                    'num_retries' => 1,
                    'retry_interval_seconds' => 0.5,
                ],
            ],
        ]);

        $target = app(
            SearchDeploymentTargetService::class,
        )->resolve();

        $this->assertSame(
            'typesense',
            $target['driver'],
        );

        $this->assertSame(
            'typesense.example.test',
            $target['configuration']['nodes'][0]['host'],
        );

        $this->assertSame(
            '',
            $target['configuration']['nodes'][0]['path'],
        );
    }

    public function test_incomplete_algolia_configuration_is_structurally_rejected(): void
    {
        $this->bindSnapshot([
            'driver' => 'algolia',
            'algolia' => [
                'id' => '',
                'secret' => 'secret',
            ],
        ]);

        $this->expectException(
            ValidationException::class,
        );

        app(SearchDeploymentTargetService::class)
            ->resolve();
    }

    private function bindSnapshot(array $configuration): void
    {
        $snapshot = new SearchDeploymentConfigurationSnapshot;
        $snapshot->capture($configuration);

        app()->instance(
            SearchDeploymentConfigurationSnapshot::class,
            $snapshot,
        );
    }
}
