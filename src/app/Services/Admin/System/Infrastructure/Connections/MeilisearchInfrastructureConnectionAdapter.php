<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Search\Clients\MeilisearchClientFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Meilisearch\Client;
use Throwable;

class MeilisearchInfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    public function __construct(private readonly MeilisearchClientFactory $clients) {}

    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Meilisearch;
    }

    public function definition(): InfrastructureConnectionDefinitionData
    {
        return new InfrastructureConnectionDefinitionData($this->type(), 'Meilisearch', 'Managed Meilisearch server connection.', [InfrastructureConnectionSource::Managed], class_exists(Client::class));
    }

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array
    {
        $validated = Validator::make(['configuration' => $configuration, 'credentials' => $credentials, 'source' => $source], [
            'source' => ['required', Rule::enum(InfrastructureConnectionSource::class), Rule::in([InfrastructureConnectionSource::Managed->value])],
            'configuration.host' => ['required', 'url:http,https', 'max:2048'],
            'credentials.api_key' => ['required', 'string', 'max:4096'],
        ])->validate();
        $host = rtrim($validated['configuration']['host'], '/');
        $parts = parse_url($host);
        if (isset($parts['user']) || isset($parts['pass'])) {
            throw ValidationException::withMessages(['configuration.host' => 'Credentials are not allowed in the Meilisearch host URL.']);
        }

        return ['configuration' => ['host' => $host], 'credentials' => ['api_key' => $validated['credentials']['api_key']]];
    }

    public function secretFields(): array
    {
        return ['api_key'];
    }

    public function publicRepresentation(InfrastructureConnection $connection): array
    {
        $configuration = $this->configuration($connection);

        return ['configuration' => ['host' => $configuration['host'] ?? ''], 'credential_flags' => ['api_key_configured' => isset($connection->secrets()['api_key'])]];
    }

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData
    {
        $started = hrtime(true);
        try {
            $client = $this->client($connection);
            $client->health();
            $client->stats();

            return new InfrastructureHealthResultData(InfrastructureHealthStatus::Healthy, $this->elapsed($started), 'Authenticated Meilisearch API access verified.', ['operations' => ['health', 'stats']]);
        } catch (Throwable) {
            return new InfrastructureHealthResultData(InfrastructureHealthStatus::Unhealthy, $this->elapsed($started), 'The Meilisearch provider could not be verified.', ['operations' => ['health', 'stats']]);
        }
    }

    private function client(InfrastructureConnection $connection): object
    {
        $normalized = $this->validateAndNormalize($this->configuration($connection), $connection->secrets(), (string) $connection->getRawOriginal('source'));

        return $this->clients->make($normalized['configuration']['host'], $normalized['credentials']['api_key']);
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
