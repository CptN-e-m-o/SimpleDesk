<?php

namespace App\Http\Resources\Admin\Mail;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MailProviderConnectionResource extends JsonResource
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
                    && (! is_string($value) || trim($value) !== '')
            )
        );

        sort($configuredSecretKeys);

        return [
            'id' => $this->id,
            'name' => $this->name,
            'provider' => $this->provider->value,
            'auth_type' => $this->auth_type->value,
            'account_identifier' => $this->account_identifier,
            'tenant_identifier' => $this->tenant_identifier,
            'configuration' => $this->configuration ?? [],
            'has_secret_configuration' => $configuredSecretKeys !== [],
            'configured_secret_keys' => $configuredSecretKeys,
            'scopes' => $this->scopes ?? [],
            'token_expires_at' => $this->token_expires_at?->toIso8601String(),
            'is_active' => $this->is_active,
            'health_status' => $this->health_status->value,
            'channels_count' => $this->whenCounted('channels'),
            'last_checked_at' => $this->last_checked_at?->toIso8601String(),
            'last_success_at' => $this->last_success_at?->toIso8601String(),
            'last_error_at' => $this->last_error_at?->toIso8601String(),
            'last_error_code' => $this->last_error_code,
            'last_error_message' => $this->last_error_message,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'deleted_at' => $this->deleted_at?->toIso8601String(),
        ];
    }
}
