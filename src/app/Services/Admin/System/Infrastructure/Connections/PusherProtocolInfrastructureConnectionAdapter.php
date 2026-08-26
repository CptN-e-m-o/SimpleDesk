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
use Illuminate\Validation\ValidationException;
use Pusher\Pusher;
use Throwable;

abstract class PusherProtocolInfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    abstract public function type(): InfrastructureConnectionType;

    abstract protected function label(): string;

    public function definition(): InfrastructureConnectionDefinitionData
    {
        return new InfrastructureConnectionDefinitionData(
            $this->type(),
            $this->label(),
            $this->label().' Pusher-protocol publisher endpoint.',
            [InfrastructureConnectionSource::Managed],
            class_exists(Pusher::class),
        );
    }

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array
    {
        Validator::make(
            ['source' => $source],
            ['source' => ['required', Rule::in([InfrastructureConnectionSource::Managed->value])]],
        )->validate();

        $validated = Validator::make(
            [
                'configuration' => $configuration,
                'credentials' => $credentials,
            ],
            [
                'configuration.app_id' => ['required', 'string', 'max:255'],
                'configuration.host' => ['nullable', 'string', 'max:255'],
                'configuration.port' => ['nullable', 'integer', 'between:1,65535'],
                'configuration.scheme' => ['required', Rule::in(['http', 'https'])],
                'configuration.cluster' => ['nullable', 'string', 'max:100'],
                'configuration.public_host' => ['nullable', 'string', 'max:255'],
                'configuration.public_port' => ['nullable', 'integer', 'between:1,65535'],
                'configuration.public_scheme' => ['nullable', Rule::in(['http', 'https'])],
                'credentials.app_key' => ['required', 'string', 'max:255'],
                'credentials.app_secret' => ['required', 'string', 'max:4096'],
            ],
        )->validate();

        $config = $validated['configuration'];
        $host = trim((string) ($config['host'] ?? ''));
        $cluster = trim((string) ($config['cluster'] ?? ''));
        $scheme = (string) $config['scheme'];

        if ($this->type() === InfrastructureConnectionType::Reverb && $host === '') {
            throw ValidationException::withMessages([
                'configuration.host' => 'A Reverb publisher host is required.',
            ]);
        }

        if (
            $this->type() === InfrastructureConnectionType::Pusher
            && $host === ''
            && $cluster === ''
        ) {
            throw ValidationException::withMessages([
                'configuration.cluster' => 'A Pusher cluster or custom publisher host is required.',
            ]);
        }

        $publicHost = trim((string) ($config['public_host'] ?? ''));
        $publicScheme = trim((string) ($config['public_scheme'] ?? ''));

        if ($publicHost === '' && (
                isset($config['public_port'])
                || $publicScheme !== ''
            )) {
            throw ValidationException::withMessages([
                'configuration.public_host' => 'A public host is required when public client endpoint settings are supplied.',
            ]);
        }

        if ($publicHost !== '' && $publicScheme === '') {
            $publicScheme = $scheme;
        }

        $port = isset($config['port'])
            ? (int) $config['port']
            : ($scheme === 'https' ? 443 : 80);

        $publicPort = $publicHost !== ''
            ? (isset($config['public_port'])
                ? (int) $config['public_port']
                : ($publicScheme === 'https' ? 443 : 80))
            : null;

        return [
            'configuration' => [
                'app_id' => trim((string) $config['app_id']),
                'host' => $host,
                'port' => $port,
                'scheme' => $scheme,
                'cluster' => $cluster,
                'public_host' => $publicHost,
                'public_port' => $publicPort,
                'public_scheme' => $publicScheme,
            ],
            'credentials' => [
                'app_key' => $validated['credentials']['app_key'],
                'app_secret' => $validated['credentials']['app_secret'],
            ],
        ];
    }

    public function secretFields(): array
    {
        return ['app_key', 'app_secret'];
    }

    public function publicRepresentation(InfrastructureConnection $connection): array
    {
        $secrets = $connection->secrets();

        return [
            'configuration' => $connection->configuration ?? [],
            'credential_flags' => [
                'app_key_configured' => isset($secrets['app_key']),
                'app_secret_configured' => isset($secrets['app_secret']),
            ],
            'client' => $this->safeClient($connection),
        ];
    }

    public function safeClient(InfrastructureConnection $connection): array
    {
        $configuration = $connection->configuration ?? [];
        $key = $connection->secrets()['app_key'] ?? null;

        if (! is_string($key) || trim($key) === '') {
            return [
                'available' => false,
                'message' => 'A public application key is not configured.',
            ];
        }

        $publicHost = trim((string) ($configuration['public_host'] ?? ''));
        $cluster = trim((string) ($configuration['cluster'] ?? ''));

        if ($this->type() === InfrastructureConnectionType::Reverb && $publicHost === '') {
            return [
                'available' => false,
                'message' => 'A public Reverb client endpoint is not configured.',
            ];
        }

        if (
            $this->type() === InfrastructureConnectionType::Pusher
            && $publicHost === ''
            && $cluster === ''
        ) {
            return [
                'available' => false,
                'message' => 'Pusher client connection metadata is incomplete.',
            ];
        }

        return [
            'available' => true,
            'app_key' => $key,
            'public_host' => $publicHost !== '' ? $publicHost : null,
            'public_port' => $publicHost !== ''
                ? ($configuration['public_port'] ?? null)
                : null,
            'public_scheme' => $publicHost !== ''
                ? ($configuration['public_scheme'] ?: null)
                : null,
            'cluster' => $cluster !== '' ? $cluster : null,
        ];
    }

    public function publisherConnection(InfrastructureConnection $connection, string $driver): array
    {
        if (! in_array($driver, ['reverb', 'pusher'], true)) {
            throw ValidationException::withMessages([
                'driver' => 'The Pusher-protocol broadcaster driver is invalid.',
            ]);
        }

        $configuration = $connection->configuration ?? [];
        $credentials = $connection->secrets();

        $appId = trim((string) ($configuration['app_id'] ?? ''));
        $appKey = $credentials['app_key'] ?? null;
        $appSecret = $credentials['app_secret'] ?? null;
        $host = trim((string) ($configuration['host'] ?? ''));
        $cluster = trim((string) ($configuration['cluster'] ?? ''));
        $scheme = trim((string) ($configuration['scheme'] ?? ''));

        if (
            $appId === ''
            || ! is_string($appKey)
            || trim($appKey) === ''
            || ! is_string($appSecret)
            || trim($appSecret) === ''
            || ! in_array($scheme, ['http', 'https'], true)
        ) {
            throw ValidationException::withMessages([
                'configuration' => 'The infrastructure publisher configuration is incomplete.',
            ]);
        }

        if ($driver === 'reverb' && $host === '') {
            throw ValidationException::withMessages([
                'configuration.host' => 'The Reverb publisher host is unavailable.',
            ]);
        }

        if ($driver === 'pusher' && $host === '' && $cluster === '') {
            throw ValidationException::withMessages([
                'configuration.cluster' => 'The Pusher publisher endpoint is unavailable.',
            ]);
        }

        $options = [
            'scheme' => $scheme,
            'useTLS' => $scheme === 'https',
        ];

        if ($host !== '') {
            $port = filter_var(
                $configuration['port'] ?? null,
                FILTER_VALIDATE_INT,
                ['options' => ['min_range' => 1, 'max_range' => 65535]],
            );

            if ($port === false) {
                throw ValidationException::withMessages([
                    'configuration.port' => 'The publisher port is invalid.',
                ]);
            }

            $options['host'] = $host;
            $options['port'] = $port;
        }

        if ($cluster !== '') {
            $options['cluster'] = $cluster;
        }

        return [
            'driver' => $driver,
            'key' => $appKey,
            'secret' => $appSecret,
            'app_id' => $appId,
            'options' => $options,
        ];
    }

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData
    {
        if (! class_exists(Pusher::class)) {
            return new InfrastructureHealthResultData(
                InfrastructureHealthStatus::Unavailable,
                null,
                'The Pusher PHP capability is unavailable.',
            );
        }

        $started = hrtime(true);

        try {
            $response = $this->client($connection)->get('/channels', ['limit' => 1]);

            if (! is_object($response) || ! property_exists($response, 'channels')) {
                throw new \RuntimeException('Provider returned an unexpected authenticated response.');
            }

            return new InfrastructureHealthResultData(
                InfrastructureHealthStatus::Healthy,
                $this->elapsed($started),
                'Authenticated provider API access verified.',
                ['operation' => 'list_channels'],
            );
        } catch (Throwable $exception) {
            return new InfrastructureHealthResultData(
                InfrastructureHealthStatus::Unhealthy,
                $this->elapsed($started),
                $exception->getMessage(),
                ['operation' => 'list_channels'],
            );
        }
    }

    public function client(InfrastructureConnection $connection): Pusher
    {
        $runtime = $this->publisherConnection($connection, $this->type()->value);

        return new Pusher(
            $runtime['key'],
            $runtime['secret'],
            $runtime['app_id'],
            $runtime['options'],
        );
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }
}
