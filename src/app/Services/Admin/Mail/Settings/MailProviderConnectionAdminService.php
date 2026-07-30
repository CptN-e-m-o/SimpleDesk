<?php

namespace App\Services\Admin\Mail\Settings;

use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\MailProviderConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MailProviderConnectionAdminService
{
    public function __construct(
        private readonly SecretConfigurationMerger $secrets,
    ) {
    }

    public function create(array $data): MailProviderConnection
    {
        return DB::transaction(
            function () use ($data): MailProviderConnection {
                return MailProviderConnection::query()->create([
                    'name' => trim($data['name']),
                    'provider' => $data['provider'],
                    'auth_type' => $data['auth_type'],
                    'account_identifier' => $this->nullableString(
                        $data['account_identifier'] ?? null
                    ),
                    'tenant_identifier' => $this->nullableString(
                        $data['tenant_identifier'] ?? null
                    ),
                    'configuration' => $data['configuration'] ?? [],
                    'secret_configuration' => $this->secrets->merge(
                        existing: [],
                        incoming: $data['secret_configuration'] ?? [],
                        clearKeys: $data['clear_secret_keys'] ?? [],
                    ),
                    'scopes' => array_values(
                        $data['scopes'] ?? []
                    ),
                    'token_expires_at' => $data['token_expires_at'] ?? null,
                    'is_active' => (bool) $data['is_active'],
                    'health_status' => MailboxHealthStatus::Unknown,
                ]);
            },
            3,
        );
    }

    public function update(
        MailProviderConnection $connection,
        array $data,
    ): MailProviderConnection {
        return DB::transaction(
            function () use ($connection, $data): MailProviderConnection {
                $connection = MailProviderConnection::query()
                    ->lockForUpdate()
                    ->findOrFail($connection->id);

                $connection->fill([
                    'name' => trim($data['name']),
                    'provider' => $data['provider'],
                    'auth_type' => $data['auth_type'],
                    'account_identifier' => $this->nullableString(
                        $data['account_identifier'] ?? null
                    ),
                    'tenant_identifier' => $this->nullableString(
                        $data['tenant_identifier'] ?? null
                    ),
                    'configuration' => $data['configuration'] ?? [],
                    'secret_configuration' => $this->secrets->merge(
                        existing: $connection->secret_configuration,
                        incoming: $data['secret_configuration'] ?? [],
                        clearKeys: $data['clear_secret_keys'] ?? [],
                    ),
                    'scopes' => array_values(
                        $data['scopes'] ?? []
                    ),
                    'token_expires_at' => $data['token_expires_at'] ?? null,
                    'is_active' => (bool) $data['is_active'],
                    'health_status' => MailboxHealthStatus::Unknown,
                    'last_checked_at' => null,
                    'last_success_at' => null,
                    'last_error_at' => null,
                    'last_error_code' => null,
                    'last_error_message' => null,
                ])->save();

                return $connection->fresh();
            },
            3,
        );
    }

    public function delete(
        MailProviderConnection $connection
    ): void {
        DB::transaction(
            function () use ($connection): void {
                $connection = MailProviderConnection::query()
                    ->lockForUpdate()
                    ->findOrFail($connection->id);

                if ($connection->channels()->exists()) {
                    throw ValidationException::withMessages([
                        'provider_connection' => [
                            'The provider connection cannot be deleted while mailbox channels reference it. Disable or reassign those channels first.',
                        ],
                    ]);
                }

                $connection->forceFill([
                    'is_active' => false,
                ])->save();

                $connection->delete();
            },
            3,
        );
    }

    private function nullableString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value !== ''
            ? $value
            : null;
    }
}
