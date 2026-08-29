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
        $name = trim(
            (string) config(
                'simpledesk-broadcasting.deployment.connection',
                '',
            ),
        );

        $managed = trim(
            (string) config(
                'simpledesk-broadcasting.runtime.connection_name',
                'simpledesk-managed',
            ),
        );

        if ($name === '') {
            $this->reject(
                'The deployment Broadcast connection is not configured.',
            );
        }

        if ($name === $managed) {
            $this->reject(
                'The deployment Broadcast connection cannot use the reserved managed connection name.',
            );
        }

        $connection = config(
            "broadcasting.connections.{$name}",
        );

        if (! is_array($connection)) {
            $this->reject(
                "The deployment Broadcast connection [{$name}] does not exist.",
            );
        }

        $driver = trim(
            (string) ($connection['driver'] ?? ''),
        );

        if ($driver === '') {
            $this->reject(
                "The deployment Broadcast connection [{$name}] does not define a driver.",
            );
        }

        if (in_array($driver, ['reverb', 'pusher'], true)) {
            $this->validatePusherProtocolConnection(
                $name,
                $driver,
                $connection,
            );
        } elseif (! in_array($driver, ['log', 'null'], true)) {
            $this->reject(
                "The deployment Broadcast driver [{$driver}] is not supported by this management layer.",
            );
        }

        return [
            'connection' => $name,
            'driver' => $driver,
            'configuration' => $connection,
            'externally_delivering' => ! in_array($driver, ['log', 'null'], true),
        ];
    }

    public function test(?array $target = null): BroadcastHealthResultData
    {
        $target ??= $this->resolve();

        if (! $target['externally_delivering']) {
            return new BroadcastHealthResultData(
                BroadcastHealthStatus::Healthy,
                0,
                'The deployment broadcaster intentionally performs no external delivery probe.',
                [
                    'externally_delivering' => false,
                    'driver' => $target['driver'],
                    'operation' => 'not_applicable',
                ],
            );
        }

        $started = hrtime(true);

        try {
            $configuration = $target['configuration'];

            $client = new Pusher(
                $configuration['key'],
                $configuration['secret'],
                $configuration['app_id'],
                $configuration['options'] ?? [],
            );

            $response = $client->get('/channels', ['limit' => 1]);

            if (! is_object($response) || ! property_exists($response, 'channels')) {
                throw new \RuntimeException('Unexpected authenticated provider response.');
            }

            return new BroadcastHealthResultData(
                BroadcastHealthStatus::Healthy,
                $this->elapsed($started),
                'Authenticated deployment provider API access verified.',
                [
                    'externally_delivering' => true,
                    'operation' => 'list_channels',
                ],
            );
        } catch (Throwable) {
            return new BroadcastHealthResultData(
                BroadcastHealthStatus::Unhealthy,
                $this->elapsed($started),
                'The deployment Broadcast provider could not be verified.',
                [
                    'externally_delivering' => true,
                    'operation' => 'list_channels',
                ],
            );
        }
    }

    public function safeTarget(): array
    {
        try {
            $target = $this->resolve();

            return [
                'connection' => $target['connection'],
                'driver' => $target['driver'],
                'available' => true,
                'externally_delivering' => $target['externally_delivering'],
            ];
        } catch (ValidationException $exception) {
            return [
                'connection' => config(
                    'simpledesk-broadcasting.deployment.connection',
                ),
                'driver' => null,
                'available' => false,
                'message' => $this->validationMessage($exception),
            ];
        }
    }

    private function validatePusherProtocolConnection(
        string $name,
        string $driver,
        array $connection,
    ): void {
        if (! class_exists(Pusher::class)) {
            $this->reject(
                'The Pusher PHP capability is unavailable.',
            );
        }

        foreach (['key', 'secret', 'app_id'] as $field) {
            if (trim((string) ($connection[$field] ?? '')) === '') {
                $this->reject(
                    "The deployment Broadcast connection [{$name}] is missing [{$field}].",
                );
            }
        }

        $options = $connection['options'] ?? [];

        if (! is_array($options)) {
            $this->reject(
                "The deployment Broadcast connection [{$name}] has invalid provider options.",
            );
        }

        $host = trim((string) ($options['host'] ?? ''));
        $cluster = trim((string) ($options['cluster'] ?? ''));
        $scheme = trim((string) ($options['scheme'] ?? ''));

        if ($driver === 'reverb' && $host === '') {
            $this->reject(
                "The deployment Reverb connection [{$name}] does not define a publisher host.",
            );
        }

        if ($driver === 'pusher' && $host === '' && $cluster === '') {
            $this->reject(
                "The deployment Pusher connection [{$name}] does not define a cluster or publisher host.",
            );
        }

        if ($host === '') {
            return;
        }

        $port = filter_var(
            $options['port'] ?? null,
            FILTER_VALIDATE_INT,
            ['options' => ['min_range' => 1, 'max_range' => 65535]],
        );

        if ($port === false) {
            $this->reject(
                "The deployment Broadcast connection [{$name}] has an invalid publisher port.",
            );
        }

        if (! in_array($scheme, ['http', 'https'], true)) {
            $this->reject(
                "The deployment Broadcast connection [{$name}] has an invalid publisher scheme.",
            );
        }
    }

    private function validationMessage(ValidationException $exception): string
    {
        foreach ($exception->errors() as $messages) {
            foreach ($messages as $message) {
                if (is_string($message) && trim($message) !== '') {
                    return trim($message);
                }
            }
        }

        return 'The deployment Broadcast connection is invalid.';
    }

    private function elapsed(int $started): int
    {
        return (int) round((hrtime(true) - $started) / 1_000_000);
    }

    private function reject(string $message): never
    {
        throw ValidationException::withMessages([
            'activation' => $message,
        ]);
    }
}
