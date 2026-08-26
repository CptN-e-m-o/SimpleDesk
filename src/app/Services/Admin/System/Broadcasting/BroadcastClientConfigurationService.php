<?php

namespace App\Services\Admin\System\Broadcasting;

use App\Enums\Admin\System\BroadcastConfigurationMode;
use App\Models\Admin\System\BroadcastDriverConfiguration;
use App\Models\Admin\System\BroadcastDriverSettings;
use Throwable;

class BroadcastClientConfigurationService
{
    public function __construct(
        private readonly BroadcastDriverRegistry $registry,
    ) {}

    public function effective(): array
    {
        $settings = BroadcastDriverSettings::query()->find(
            BroadcastDriverSettings::SINGLETON_ID,
        );

        if (! $settings || $settings->mode === BroadcastConfigurationMode::Deployment) {
            return [
                'available' => false,
                'ownership' => 'deployment',
                'message' => 'Deployment client metadata is not managed by SimpleDesk.',
            ];
        }

        if (! $settings->active_configuration_id) {
            return $this->managedUnavailable();
        }

        try {
            $configuration = BroadcastDriverConfiguration::withTrashed()->find(
                $settings->active_configuration_id,
            );

            if (
                ! $configuration
                || $configuration->trashed()
                || ! $configuration->is_enabled
            ) {
                return $this->managedUnavailable();
            }

            $client = $this->registry
                ->adapter($configuration->driver)
                ->runtimeConfiguration($configuration)
                ->client;
        } catch (Throwable) {
            return $this->managedUnavailable();
        }

        if (! ($client['available'] ?? false)) {
            return [
                'available' => false,
                'ownership' => 'managed',
                'message' => is_string($client['message'] ?? null)
                    ? $client['message']
                    : 'Managed client metadata is unavailable.',
            ];
        }

        $appKey = $client['app_key'] ?? null;
        $broadcaster = $client['broadcaster'] ?? null;

        if (
            ! is_string($appKey)
            || trim($appKey) === ''
            || ! is_string($broadcaster)
            || ! in_array($broadcaster, ['reverb', 'pusher'], true)
        ) {
            return $this->managedUnavailable();
        }

        if (
            $broadcaster === 'reverb'
            && (! is_string($client['public_host'] ?? null) || trim($client['public_host']) === '')
        ) {
            return $this->managedUnavailable(
                'A public Reverb client endpoint is not configured.',
            );
        }

        return [
            'available' => true,
            'ownership' => 'managed',
            'broadcaster' => $broadcaster,
            'app_key' => $appKey,
            'public_host' => $this->nullableString($client['public_host'] ?? null),
            'public_port' => is_int($client['public_port'] ?? null)
                ? $client['public_port']
                : null,
            'public_scheme' => $this->nullableString($client['public_scheme'] ?? null),
            'cluster' => $this->nullableString($client['cluster'] ?? null),
        ];
    }

    private function managedUnavailable(
        string $message = 'Managed client metadata is unavailable.',
    ): array {
        return [
            'available' => false,
            'ownership' => 'managed',
            'message' => $message,
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        return trim($value);
    }
}
