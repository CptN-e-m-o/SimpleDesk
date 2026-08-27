<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use Algolia\AlgoliaSearch\Api\SearchClient;
use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use App\Services\Admin\System\Search\Clients\AlgoliaClientFactory;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Throwable;

class AlgoliaInfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    public function __construct(private readonly AlgoliaClientFactory $clients) {}

    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Algolia;
    }

    public function definition(): InfrastructureConnectionDefinitionData
    {
        return new InfrastructureConnectionDefinitionData($this->type(), 'Algolia', 'Managed server-side Algolia connection.', [InfrastructureConnectionSource::Managed], class_exists(SearchClient::class));
    }

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array
    {
        $validated = Validator::make(['configuration' => $configuration, 'credentials' => $credentials, 'source' => $source], [
            'source' => ['required', Rule::in([InfrastructureConnectionSource::Managed->value])],
            'configuration.application_id' => ['required', 'string', 'max:255'],
            'credentials.api_key' => ['required', 'string', 'max:4096'],
        ])->validate();

        return ['configuration' => ['application_id' => trim($validated['configuration']['application_id'])], 'credentials' => ['api_key' => $validated['credentials']['api_key']]];
    }

    public function secretFields(): array
    {
        return ['api_key'];
    }

    public function publicRepresentation(InfrastructureConnection $connection): array
    {
        $configuration = $this->configuration($connection);

        return ['configuration' => ['application_id' => $configuration['application_id'] ?? ''], 'credential_flags' => ['api_key_configured' => isset($connection->secrets()['api_key'])]];
    }

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData
    {
        $started = hrtime(true);
        try {
            $normalized = $this->validateAndNormalize($this->configuration($connection), $connection->secrets(), (string) $connection->getRawOriginal('source'));
            $this->clients->make($normalized['configuration']['application_id'], $normalized['credentials']['api_key'])->listIndices(0, 1);

            return new InfrastructureHealthResultData(InfrastructureHealthStatus::Healthy, $this->elapsed($started), 'Authenticated Algolia API access verified.', ['operations' => ['list_indices']]);
        } catch (Throwable) {
            return new InfrastructureHealthResultData(InfrastructureHealthStatus::Unhealthy, $this->elapsed($started), 'The Algolia provider could not be verified.', ['operations' => ['list_indices']]);
        }
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
