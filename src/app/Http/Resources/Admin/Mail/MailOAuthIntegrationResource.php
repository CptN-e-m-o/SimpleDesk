<?php

namespace App\Http\Resources\Admin\Mail;

use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Enums\Admin\Mail\MailProvider;
use App\Models\Admin\Mail\MailProviderConnection;
use App\Services\Admin\Mail\MailSensitiveDataRedactor;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MailOAuthIntegrationResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $connection = $this->resource;

        if (! $connection instanceof MailProviderConnection) {
            return [];
        }

        $redactor = app(MailSensitiveDataRedactor::class);
        $secrets = $connection->secrets();
        $configuration = $connection->publicConfiguration();
        $connected = isset($secrets['refresh_token']) || isset($secrets['access_token']);
        $provider = $connection->getAttribute('provider');
        $health = $connection->getAttribute('health_status');
        $lastErrorMessage = $connection->getAttribute('last_error_message');

        return [
            'id' => $connection->getKey(),
            'name' => (string) $connection->getAttribute('name'),
            'provider' => $provider instanceof MailProvider ? $provider->value : (string) $provider,
            'client_id' => $configuration['client_id'] ?? '',
            'tenant_mode' => $configuration['tenant_mode'] ?? 'common',
            'tenant_id' => $connection->getAttribute('tenant_identifier'),
            'is_active' => (bool) $connection->getAttribute('is_active'),
            'has_client_secret' => isset($secrets['client_secret']),
            'connected' => $connected,
            'connected_email' => $connection->getAttribute('account_identifier'),
            'scopes' => $connection->getAttribute('scopes') ?? [],
            'token_expires_at' => $connection->dateAttribute('token_expires_at')?->toIso8601String(),
            'connected_at' => $connection->dateAttribute('connected_at')?->toIso8601String(),
            'last_refreshed_at' => $connection->dateAttribute('last_refreshed_at')?->toIso8601String(),
            'last_checked_at' => $connection->dateAttribute('last_checked_at')?->toIso8601String(),
            'health_status' => $health instanceof MailboxHealthStatus ? $health->value : (string) $health,
            'last_error_code' => $connection->getAttribute('last_error_code'),
            'last_error_message' => is_string($lastErrorMessage) ? $redactor->redactString($lastErrorMessage) : null,
            'channels_count' => $this->whenCounted('channels'),
            'deleted_at' => $connection->dateAttribute('deleted_at')?->toIso8601String(),
        ];
    }
}
