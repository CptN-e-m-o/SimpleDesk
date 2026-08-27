<?php

namespace App\Services\Admin\System\Search;

use App\Data\Admin\System\Search\SearchHealthResultData;
use App\Enums\Admin\System\SearchHealthStatus;
use App\Services\Admin\System\Search\Clients\AlgoliaClientFactory;
use App\Services\Admin\System\Search\Clients\MeilisearchClientFactory;
use App\Services\Admin\System\Search\Clients\TypesenseClientFactory;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Throwable;

class SearchDeploymentTargetService
{
    public function __construct(private readonly SearchDeploymentConfigurationSnapshot $snapshot, private readonly MeilisearchClientFactory $meilisearch, private readonly TypesenseClientFactory $typesense, private readonly AlgoliaClientFactory $algolia) {}

    public function resolve(): array
    {
        $scout = $this->snapshot->configuration();
        $driver = trim((string) ($scout['driver'] ?? config('simpledesk-search.deployment.driver', 'database')));
        if (! in_array($driver, ['database', 'collection', 'meilisearch', 'typesense', 'algolia'], true)) {
            $this->reject("The deployment Scout driver [{$driver}] is not supported by this management layer.");
        }
        $configuration = match ($driver) {
            'database', 'collection' => [],
            'meilisearch' => $this->meilisearchConfiguration((array) ($scout['meilisearch'] ?? [])),
            'typesense' => $this->typesenseConfiguration((array) ($scout['typesense']['client-settings'] ?? [])),
            'algolia' => $this->algoliaConfiguration((array) ($scout['algolia'] ?? [])),
        };

        return ['driver' => $driver, 'configuration' => $configuration, 'uses_external_service' => ! in_array($driver, ['database', 'collection'], true)];
    }

    public function test(?array $target = null): SearchHealthResultData
    {
        $target ??= $this->resolve();
        $started = hrtime(true);
        try {
            if ($target['driver'] === 'database') {
                return $this->databaseHealth($started, true);
            }
            if ($target['driver'] === 'collection') {
                DB::select('SELECT 1');

                return new SearchHealthResultData(SearchHealthStatus::Healthy, $this->elapsed($started), 'Deployment collection engine database connectivity verified.', ['driver' => 'collection', 'operation' => 'select_1']);
            }
            if ($target['driver'] === 'meilisearch') {
                $client = $this->meilisearch->make($target['configuration']['host'], $target['configuration']['key']);
                $client->health();
                $client->stats();
            } elseif ($target['driver'] === 'typesense') {
                $client = $this->typesense->make($target['configuration']);
                $client->getHealth()->retrieve();
                $client->getCollections()->retrieve();
            } else {
                $this->algolia->make($target['configuration']['id'], $target['configuration']['secret'])->listIndices(0, 1);
            }

            return new SearchHealthResultData(SearchHealthStatus::Healthy, $this->elapsed($started), 'Authenticated deployment Search provider API access verified.', ['driver' => $target['driver'], 'operation' => 'read_only_provider_probe']);
        } catch (Throwable) {
            return new SearchHealthResultData(SearchHealthStatus::Unhealthy, $this->elapsed($started), 'The deployment Search target could not be verified.', ['driver' => $target['driver'], 'operation' => 'read_only_provider_probe']);
        }
    }

    public function safeTarget(): array
    {
        try {
            $target = $this->resolve();

            return ['driver' => $target['driver'], 'label' => ucfirst($target['driver']), 'available' => true, 'structurally_valid' => true, 'uses_external_service' => $target['uses_external_service']];
        } catch (ValidationException $exception) {
            return ['driver' => $this->snapshot->configuration()['driver'] ?? null, 'available' => false, 'structurally_valid' => false, 'message' => $this->validationMessage($exception)];
        }
    }

    private function databaseHealth(int $started, bool $requireSupported): SearchHealthResultData
    {
        $driver = DB::connection()->getDriverName();
        if ($requireSupported && ! in_array($driver, ['pgsql', 'mysql'], true)) {
            return new SearchHealthResultData(SearchHealthStatus::Unavailable, $this->elapsed($started), "The Scout database engine is not supported on [{$driver}].", ['database_driver' => $driver]);
        }
        try {
            DB::select('SELECT 1');

            return new SearchHealthResultData(SearchHealthStatus::Healthy, $this->elapsed($started), 'Deployment database Search connectivity verified.', ['database_driver' => $driver, 'operation' => 'select_1']);
        } catch (Throwable) {
            return new SearchHealthResultData(SearchHealthStatus::Unhealthy, $this->elapsed($started), 'Deployment database Search connectivity could not be verified.', ['database_driver' => $driver]);
        }
    }

    private function meilisearchConfiguration(array $configuration): array
    {
        if (trim((string) ($configuration['host'] ?? '')) === '' || trim((string) ($configuration['key'] ?? '')) === '') {
            $this->reject('The deployment Meilisearch configuration is incomplete.');
        }

        return ['host' => $configuration['host'], 'key' => $configuration['key']];
    }

    private function typesenseConfiguration(array $configuration): array
    {
        if (trim((string) ($configuration['api_key'] ?? '')) === '' || ! is_array($configuration['nodes'] ?? null) || $configuration['nodes'] === []) {
            $this->reject('The deployment Typesense configuration is incomplete.');
        }

        return $configuration;
    }

    private function algoliaConfiguration(array $configuration): array
    {
        if (trim((string) ($configuration['id'] ?? '')) === '' || trim((string) ($configuration['secret'] ?? '')) === '') {
            $this->reject('The deployment Algolia configuration is incomplete.');
        }

        return ['id' => $configuration['id'], 'secret' => $configuration['secret']];
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function validationMessage(ValidationException $exception): string
    {
        return collect($exception->errors())->flatten()->first() ?? 'The deployment Search target is invalid.';
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['activation' => $message]);
    }
}
