<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Pusher\Pusher;
use Throwable;

abstract class PusherProtocolInfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    abstract public function type(): InfrastructureConnectionType;

    abstract protected function label(): string;

    public function definition(): InfrastructureConnectionDefinitionData
    {
        return new InfrastructureConnectionDefinitionData($this->type(), $this->label(), $this->label().' Pusher-protocol publisher endpoint.', [InfrastructureConnectionSource::Managed], class_exists(Pusher::class));
    }

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array
    {
        Validator::make(['source' => $source], ['source' => ['required', Rule::in([InfrastructureConnectionSource::Managed->value])]])->validate();
        $rules = [
            'configuration.app_id' => ['required', 'string', 'max:255'],
            'configuration.host' => [$this->type() === InfrastructureConnectionType::Reverb ? 'required' : 'nullable', 'string', 'max:255'],
            'configuration.port' => ['nullable', 'integer', 'between:1,65535'],
            'configuration.scheme' => ['required', Rule::in(['http', 'https'])],
            'configuration.cluster' => [$this->type() === InfrastructureConnectionType::Pusher ? 'required' : 'nullable', 'string', 'max:100'],
            'configuration.public_host' => ['nullable', 'string', 'max:255'],
            'configuration.public_port' => ['nullable', 'integer', 'between:1,65535'],
            'configuration.public_scheme' => ['nullable', Rule::in(['http', 'https'])],
            'credentials.app_key' => ['required', 'string', 'max:255'],
            'credentials.app_secret' => ['required', 'string', 'max:4096'],
        ];
        $validated = Validator::make(['configuration' => $configuration, 'credentials' => $credentials], $rules)->validate();
        $config = $validated['configuration'];
        $normalized = [
            'app_id' => $config['app_id'],
            'host' => $config['host'] ?? '',
            'port' => isset($config['port']) ? (int) $config['port'] : ($config['scheme'] === 'https' ? 443 : 80),
            'scheme' => $config['scheme'],
            'cluster' => $config['cluster'] ?? '',
            'public_host' => $config['public_host'] ?? '',
            'public_port' => isset($config['public_port']) ? (int) $config['public_port'] : null,
            'public_scheme' => $config['public_scheme'] ?? '',
        ];

        return ['configuration' => $normalized, 'credentials' => ['app_key' => $validated['credentials']['app_key'], 'app_secret' => $validated['credentials']['app_secret']]];
    }

    public function secretFields(): array
    {
        return ['app_key', 'app_secret'];
    }

    public function publicRepresentation(InfrastructureConnection $connection): array
    {
        $configuration = $connection->configuration ?? [];

        return [
            'configuration' => $configuration,
            'credential_flags' => ['app_key_configured' => isset($connection->secrets()['app_key']), 'app_secret_configured' => isset($connection->secrets()['app_secret'])],
            'client' => $this->safeClient($connection),
        ];
    }

    public function safeClient(InfrastructureConnection $connection): array
    {
        $configuration = $connection->configuration ?? [];
        $key = $connection->secrets()['app_key'] ?? null;
        if (! is_string($key) || $key === '') {
            return ['available' => false, 'message' => 'A public application key is not configured.'];
        }

        return [
            'available' => true,
            'app_key' => $key,
            'public_host' => $configuration['public_host'] ?: ($this->type() === InfrastructureConnectionType::Reverb ? null : $configuration['host']),
            'public_port' => $configuration['public_port'],
            'public_scheme' => $configuration['public_scheme'] ?: null,
            'cluster' => $configuration['cluster'] ?: null,
        ];
    }

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData
    {
        $started = hrtime(true);
        try {
            $client = $this->client($connection);
            $response = $client->get('/channels', ['limit' => 1]);
            if (! is_object($response) || ! property_exists($response, 'channels')) {
                throw new \RuntimeException('Provider returned an unexpected authenticated response.');
            }

            return new InfrastructureHealthResultData(InfrastructureHealthStatus::Healthy, $this->elapsed($started), 'Authenticated provider API access verified.', ['operation' => 'list_channels']);
        } catch (Throwable $exception) {
            return new InfrastructureHealthResultData(str_contains(strtolower($exception->getMessage()), 'class') ? InfrastructureHealthStatus::Unavailable : InfrastructureHealthStatus::Unhealthy, $this->elapsed($started), $exception->getMessage(), ['operation' => 'list_channels']);
        }
    }

    public function client(InfrastructureConnection $connection): Pusher
    {
        $configuration = $connection->configuration ?? [];
        $credentials = $connection->secrets();
        $options = ['scheme' => $configuration['scheme'], 'useTLS' => $configuration['scheme'] === 'https', 'timeout' => 5];
        if ($configuration['host'] !== '') {
            $options['host'] = $configuration['host'];
            $options['port'] = $configuration['port'];
        }
        if ($configuration['cluster'] !== '') {
            $options['cluster'] = $configuration['cluster'];
        }

        return new Pusher($credentials['app_key'], $credentials['app_secret'], $configuration['app_id'], $options);
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
