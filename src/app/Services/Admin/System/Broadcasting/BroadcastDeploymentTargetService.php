<?php

namespace App\Services\Admin\System\Broadcasting;

use App\Data\Admin\System\Broadcasting\BroadcastHealthResultData;
use App\Enums\Admin\System\BroadcastHealthStatus;
use Illuminate\Validation\ValidationException;
use Pusher\Pusher;
use Throwable;

class BroadcastDeploymentTargetService
{
    public function resolve(): array
    {
        $name = trim((string) config('simpledesk-broadcasting.deployment.connection', ''));
        $managed = trim((string) config('simpledesk-broadcasting.runtime.connection_name', 'simpledesk-managed'));
        if ($name === '' || $name === $managed) {
            $this->reject('The deployment Broadcast connection is missing or uses the reserved managed name.');
        }
        $connection = config("broadcasting.connections.{$name}");
        if (! is_array($connection) || trim((string) ($connection['driver'] ?? '')) === '') {
            $this->reject("The deployment Broadcast connection [{$name}] is unavailable or has no driver.");
        }
        $driver = (string) $connection['driver'];
        if (in_array($driver, ['pusher', 'reverb'], true)) {
            if (! class_exists(Pusher::class)) {
                $this->reject('The Pusher PHP capability is unavailable.');
            }
            foreach (['key', 'secret', 'app_id'] as $field) {
                if (trim((string) ($connection[$field] ?? '')) === '') {
                    $this->reject("The deployment Broadcast connection [{$name}] is missing [{$field}].");
                }
            }
        } elseif (! in_array($driver, ['log', 'null'], true)) {
            $this->reject("The deployment Broadcast driver [{$driver}] is not supported by this management layer.");
        }

        return ['connection' => $name, 'driver' => $driver, 'configuration' => $connection, 'externally_delivering' => ! in_array($driver, ['log', 'null'], true)];
    }

    public function test(?array $target = null): BroadcastHealthResultData
    {
        $target ??= $this->resolve();
        if (! $target['externally_delivering']) {
            return new BroadcastHealthResultData(BroadcastHealthStatus::Healthy, 0, 'Deployment intentionally uses the '.$target['driver'].' driver; no external delivery probe is applicable.', ['externally_delivering' => false]);
        }
        $started = hrtime(true);
        try {
            $config = $target['configuration'];
            $client = new Pusher($config['key'], $config['secret'], $config['app_id'], $config['options'] ?? []);
            $response = $client->get('/channels', ['limit' => 1]);
            if (! is_object($response) || ! property_exists($response, 'channels')) {
                throw new \RuntimeException('Unexpected authenticated provider response.');
            }

            return new BroadcastHealthResultData(BroadcastHealthStatus::Healthy, $this->elapsed($started), 'Authenticated deployment provider API access verified.', ['operation' => 'list_channels']);
        } catch (Throwable) {
            return new BroadcastHealthResultData(BroadcastHealthStatus::Unhealthy, $this->elapsed($started), 'The deployment Broadcast provider could not be verified.', ['operation' => 'list_channels']);
        }
    }

    public function safeTarget(): array
    {
        try {
            $target = $this->resolve();

            return ['connection' => $target['connection'], 'driver' => $target['driver'], 'available' => true, 'externally_delivering' => $target['externally_delivering']];
        } catch (ValidationException $exception) {
            return ['connection' => config('simpledesk-broadcasting.deployment.connection'), 'driver' => null, 'available' => false, 'message' => collect($exception->errors())->flatten()->first()];
        }
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages(['activation' => $message]);
    }
}
