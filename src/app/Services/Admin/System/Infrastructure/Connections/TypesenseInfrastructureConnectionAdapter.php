<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Search\Clients\TypesenseClientFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;
use Typesense\Client;

class TypesenseInfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    public function __construct(private readonly TypesenseClientFactory $clients) {}

    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Typesense;
    }

    public function definition(): InfrastructureConnectionDefinitionData
    {
        return new InfrastructureConnectionDefinitionData($this->type(), 'Typesense', 'Managed Typesense cluster connection.', [InfrastructureConnectionSource::Managed], class_exists(Client::class));
    }

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array
    {
        $validated = Validator::make(['configuration' => $configuration, 'credentials' => $credentials, 'source' => $source], [
            'source' => ['required', Rule::in([InfrastructureConnectionSource::Managed->value])],
            'configuration.nodes' => ['required', 'array', 'min:1', 'max:20'],
            'configuration.nodes.*.host' => ['required', 'string', 'max:255'],
            'configuration.nodes.*.port' => ['required', 'integer', 'between:1,65535'],
            'configuration.nodes.*.protocol' => ['required', Rule::in(['http', 'https'])],
            'configuration.nodes.*.path' => ['nullable', 'string', 'max:255'],
            'configuration.connection_timeout_seconds' => ['required', 'numeric', 'between:0.1,60'],
            'configuration.healthcheck_interval_seconds' => ['required', 'integer', 'between:1,3600'],
            'configuration.num_retries' => ['required', 'integer', 'between:0,10'],
            'configuration.retry_interval_seconds' => ['required', 'numeric', 'between:0,60'],
            'credentials.api_key' => ['required', 'string', 'max:4096'],
        ])->validate();
        $config = $validated['configuration'];
        $nodes = array_map(fn (array $node) => ['host' => trim($node['host']), 'port' => (int) $node['port'], 'protocol' => $node['protocol'], 'path' => trim((string) ($node['path'] ?? ''))], $config['nodes']);

        return ['configuration' => ['nodes' => $nodes, 'connection_timeout_seconds' => (float) $config['connection_timeout_seconds'], 'healthcheck_interval_seconds' => (int) $config['healthcheck_interval_seconds'], 'num_retries' => (int) $config['num_retries'], 'retry_interval_seconds' => (float) $config['retry_interval_seconds']], 'credentials' => ['api_key' => $validated['credentials']['api_key']]];
    }

    public function secretFields(): array
    {
        return ['api_key'];
    }

    public function publicRepresentation(InfrastructureConnection $connection): array
    {
        return ['configuration' => $this->configuration($connection), 'credential_flags' => ['api_key_configured' => isset($connection->secrets()['api_key'])]];
    }

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData
    {
        $started = hrtime(true);
        try {
            $client = $this->client($connection);
            $client->getHealth()->retrieve();
            $client->getCollections()->retrieve();

            return new InfrastructureHealthResultData(InfrastructureHealthStatus::Healthy, $this->elapsed($started), 'Authenticated Typesense API access verified.', ['operations' => ['health', 'list_collections']]);
        } catch (Throwable) {
            return new InfrastructureHealthResultData(InfrastructureHealthStatus::Unhealthy, $this->elapsed($started), 'The Typesense provider could not be verified.', ['operations' => ['health', 'list_collections']]);
        }
    }

    private function client(InfrastructureConnection $connection): object
    {
        $normalized = $this->validateAndNormalize($this->configuration($connection), $connection->secrets(), (string) $connection->getRawOriginal('source'));

        return $this->clients->make(['api_key' => $normalized['credentials']['api_key'], ...$normalized['configuration']]);
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function configuration(InfrastructureConnection $connection): array
    {
        $configuration = $connection->getAttribute('configuration');

        return is_array($configuration) ? $configuration : [];
    }
}
