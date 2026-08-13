<?php

namespace App\Services\Admin\System\Infrastructure\Connections;

use App\Contracts\Admin\System\Infrastructure\InfrastructureConnectionAdapter;
use App\Data\Admin\System\Infrastructure\InfrastructureConnectionDefinitionData;
use App\Data\Admin\System\Infrastructure\InfrastructureHealthResultData;
use App\Enums\Admin\System\InfrastructureConnectionSource;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\InfrastructureHealthStatus;
use App\Models\Admin\System\InfrastructureConnection;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Redis\RedisManager;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Throwable;

class RedisInfrastructureConnectionAdapter implements InfrastructureConnectionAdapter
{
    public function __construct(private readonly Application $app) {}

    public function type(): InfrastructureConnectionType
    {
        return InfrastructureConnectionType::Redis;
    }

    public function definition(): InfrastructureConnectionDefinitionData
    {
        $reserved = ['client', 'options', 'clusters'];
        $connections = array_values(array_filter(array_keys((array) config('database.redis', [])), fn (string $key) => ! in_array($key, $reserved, true) && is_array(config("database.redis.{$key}"))));

        return new InfrastructureConnectionDefinitionData($this->type(), 'Redis', 'Redis server or an existing Laravel Redis connection.', [InfrastructureConnectionSource::Managed, InfrastructureConnectionSource::Deployment], true, ['deployment_connections' => $connections]);
    }

    public function validateAndNormalize(array $configuration, array $credentials, string $source): array
    {
        $sourceEnum = InfrastructureConnectionSource::tryFrom($source);
        if (! $sourceEnum || ! in_array($sourceEnum, $this->definition()->sources, true)) {
            throw ValidationException::withMessages(['source' => 'The selected connection source is not supported.']);
        }
        if ($sourceEnum === InfrastructureConnectionSource::Deployment) {
            $names = $this->definition()->options['deployment_connections'];
            $validated = Validator::make($configuration, ['connection_name' => ['required', 'string', Rule::in($names)]])->validate();

            return ['configuration' => ['connection_name' => $validated['connection_name']], 'credentials' => []];
        }
        $validated = Validator::make([...$configuration, 'password' => $credentials['password'] ?? null], [
            'host' => ['required', 'string', 'max:255'], 'port' => ['required', 'integer', 'between:1,65535'],
            'database' => ['required', 'integer', 'min:0'], 'username' => ['nullable', 'string', 'max:255'],
            'tls' => ['required', 'boolean'], 'connect_timeout_seconds' => ['required', 'numeric', 'between:0.1,60'],
            'password' => ['nullable', 'string', 'max:4096'],
        ])->validate();
        $password = $validated['password'] ?? null;
        unset($validated['password']);

        return ['configuration' => $validated, 'credentials' => $password !== null && $password !== '' ? ['password' => $password] : []];
    }

    public function secretFields(): array
    {
        return ['password'];
    }

    public function publicRepresentation(InfrastructureConnection $connection): array
    {
        return ['configuration' => $connection->configuration ?? [], 'credential_flags' => ['password_configured' => isset($connection->secrets()['password'])]];
    }

    public function test(InfrastructureConnection $connection): InfrastructureHealthResultData
    {
        $started = hrtime(true);
        $redis = null;
        $key = 'simpledesk:health:'.Str::random(32);
        $expected = Str::random(32);
        try {
            if ($connection->source === InfrastructureConnectionSource::Deployment) {
                $redis = Redis::connection((string) ($connection->configuration['connection_name'] ?? ''));
            } else {
                $configuration = $connection->configuration ?? [];
                $secrets = $connection->secrets();
                $scheme = ($configuration['tls'] ?? false) ? 'tls' : 'tcp';
                $manager = new RedisManager($this->app, (string) config('database.redis.client', 'phpredis'), ['temporary' => [
                    'url' => null, 'host' => $configuration['host'], 'port' => $configuration['port'], 'database' => $configuration['database'],
                    'username' => $configuration['username'] ?: null, 'password' => $secrets['password'] ?? null,
                    'timeout' => $configuration['connect_timeout_seconds'], 'read_timeout' => $configuration['connect_timeout_seconds'], 'scheme' => $scheme,
                ]]);
                $redis = $manager->connection('temporary');
            }
            $redis->ping();
            $redis->setex($key, 30, $expected);
            if ((string) $redis->get($key) !== $expected) {
                throw new \RuntimeException('Redis write/read verification failed.');
            }
            $latency = (int) round((hrtime(true) - $started) / 1_000_000);

            return new InfrastructureHealthResultData(InfrastructureHealthStatus::Healthy, $latency, 'Redis connection verified successfully.', ['operations' => ['ping', 'write', 'read', 'delete']]);
        } catch (Throwable $exception) {
            $status = str_contains(strtolower($exception->getMessage()), 'class') || str_contains(strtolower($exception->getMessage()), 'extension') ? InfrastructureHealthStatus::Unavailable : InfrastructureHealthStatus::Unhealthy;

            return new InfrastructureHealthResultData($status, (int) round((hrtime(true) - $started) / 1_000_000), $exception->getMessage());
        } finally {
            if ($redis !== null) {
                try {
                    $redis->del($key);
                } catch (Throwable) {
                }
            }
        }
    }
}
