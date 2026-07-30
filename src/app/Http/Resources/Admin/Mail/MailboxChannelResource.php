<?php

namespace App\Http\Resources\Admin\Mail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MailboxChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $secretConfiguration = is_array($this->secret_configuration)
            ? $this->secret_configuration
            : [];

        $configuredSecretKeys = array_keys(
            array_filter(
                $secretConfiguration,
                static fn (mixed $value): bool => $value !== null
                    && (!is_string($value) || trim($value) !== '')
            )
        );

        sort($configuredSecretKeys);

        return [
            'id' => $this->id,
            'mailbox_id' => $this->mailbox_id,
            'provider_connection_id' => $this->provider_connection_id,
            'provider_connection' => $this->whenLoaded(
                'providerConnection',
                fn (): ?array => $this->providerConnection === null
                    ? null
                    : [
                        'id' => $this->providerConnection->id,
                        'name' => $this->providerConnection->name,
                        'provider' => $this->providerConnection->provider->value,
                        'is_active' => $this->providerConnection->is_active,
                    ]
            ),
            'name' => $this->name,
            'direction' => $this->direction->value,
            'driver' => $this->driver->value,
            'auth_type' => $this->auth_type->value,
            'is_enabled' => $this->is_enabled,
            'is_primary' => $this->is_primary,
            'failover_order' => $this->failover_order,
            'configuration' => $this->configuration ?? [],
            'has_secret_configuration' => $configuredSecretKeys !== [],
            'configured_secret_keys' => $configuredSecretKeys,
            'health_status' => $this->health_status->value,
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'last_success_at' => $this->last_success_at?->toIso8601String(),
            'last_activity_at' => $this->last_activity_at?->toIso8601String(),
            'last_error_at' => $this->last_error_at?->toIso8601String(),
            'last_error_code' => $this->last_error_code,
            'last_error_message' => $this->last_error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
