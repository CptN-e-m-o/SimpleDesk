<?php

namespace App\Services\Admin\Mail\OAuth;

use App\Enums\Admin\Mail\MailAuthenticationType;
use App\Enums\Admin\Mail\MailboxHealthStatus;
use App\Models\Admin\Mail\MailboxChannel;
use App\Models\Admin\Mail\MailProviderConnection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MailOAuthIntegrationService
{
    public function create(
        array $data
    ): MailProviderConnection {
        return MailProviderConnection::query()
            ->create(
                $this->values(
                    $data,
                    null
                )
            );
    }

    public function update(
        MailProviderConnection $connection,
        array $data
    ): MailProviderConnection {
        return DB::transaction(
            function () use (
                $connection,
                $data
            ): MailProviderConnection {
                $currentConfiguration =
                    $connection->publicConfiguration();

                $providerChanged =
                    (string) $connection->getRawOriginal(
                        'provider'
                    )
                    !== $data['provider'];

                $clientIdChanged =
                    (
                        $currentConfiguration['client_id']
                        ?? null
                    )
                    !== trim(
                        (string) $data['client_id']
                    );

                if (
                    (
                        $providerChanged
                        || $clientIdChanged
                    )
                    && ! $this->hasClientSecret(
                        $data
                    )
                ) {
                    throw ValidationException::withMessages([
                        'client_secret' => 'A new client secret is required when the OAuth provider or client ID changes.',
                    ]);
                }

                $values = $this->values(
                    $data,
                    $connection
                );

                $tenantModeChanged =
                    (
                        $currentConfiguration['tenant_mode']
                        ?? null
                    )
                    !== (
                        $values['configuration']['tenant_mode']
                        ?? null
                    );

                $tenantIdentifierChanged =
                    $connection->tenant_identifier
                    !== $values['tenant_identifier'];

                $configurationChanged =
                    $providerChanged
                    || $clientIdChanged
                    || $tenantModeChanged
                    || $tenantIdentifierChanged;

                if ($configurationChanged) {
                    $secrets =
                        $values['secret_configuration'];

                    unset(
                        $secrets['access_token'],
                        $secrets['refresh_token']
                    );

                    $values['secret_configuration'] =
                        $secrets;

                    $values['account_identifier'] =
                        null;

                    $values['token_expires_at'] =
                        null;

                    $values['connected_at'] =
                        null;

                    $values['last_refreshed_at'] =
                        null;

                    $values['health_status'] =
                        MailboxHealthStatus::Unknown;

                    $connection
                        ->channels()
                        ->where(
                            'auth_type',
                            MailAuthenticationType::OAuth2
                        )
                        ->get()
                        ->each(
                            static function (
                                MailboxChannel $channel
                            ): void {
                                $channel
                                    ->forceFill([
                                        'is_enabled' => false,
                                    ])
                                    ->save();
                            }
                        );
                }

                $connection
                    ->fill($values)
                    ->save();

                $connection->refresh();

                return $connection;
            }
        );
    }

    public function delete(
        MailProviderConnection $connection
    ): void {
        DB::transaction(
            function () use (
                $connection
            ): void {
                $connection
                    ->channels()
                    ->where(
                        'auth_type',
                        MailAuthenticationType::OAuth2
                    )
                    ->update([
                        'is_enabled' => false,
                    ]);

                $connection
                    ->forceFill([
                        'is_active' => false,
                    ])
                    ->save();

                $connection->delete();
            }
        );
    }

    public function restore(
        int $id
    ): MailProviderConnection {
        $connection = MailProviderConnection::onlyTrashed()
            ->findOrFail($id);

        $connection->restore();

        $connection
            ->forceFill([
                'is_active' => false,
            ])
            ->save();

        return $connection;
    }

    public function forceDelete(
        int $id
    ): void {
        $connection = MailProviderConnection::onlyTrashed()
            ->findOrFail($id);

        if (
            $connection
                ->channels()
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'connection' => 'OAuth integration cannot be permanently deleted while channels reference it.',
            ]);
        }

        $connection->forceDelete();
    }

    public function disconnect(
        MailProviderConnection $connection
    ): void {
        DB::transaction(
            function () use (
                $connection
            ): void {
                $secrets = $connection->secrets();

                unset(
                    $secrets['access_token'],
                    $secrets['refresh_token']
                );

                $connection
                    ->channels()
                    ->where(
                        'auth_type',
                        MailAuthenticationType::OAuth2
                    )
                    ->get()
                    ->each(
                        static function (
                            MailboxChannel $channel
                        ): void {
                            $channel
                                ->forceFill([
                                    'is_enabled' => false,
                                ])
                                ->save();
                        }
                    );

                $connection
                    ->forceFill([
                        'secret_configuration' => $secrets,

                        'account_identifier' => null,

                        'scopes' => [],

                        'token_expires_at' => null,

                        'connected_at' => null,

                        'last_refreshed_at' => null,

                        'health_status' => MailboxHealthStatus::Unknown,

                        'last_checked_at' => null,

                        'last_success_at' => null,

                        'last_error_at' => null,

                        'last_error_code' => null,

                        'last_error_message' => null,
                    ])
                    ->save();
            }
        );
    }

    private function values(
        array $data,
        ?MailProviderConnection $existing
    ): array {
        $configuration = [
            'client_id' => trim(
                (string) $data['client_id']
            ),

            'tenant_mode' => $data['provider']
                === 'microsoft'
                    ? $data['tenant_mode']
                    : null,
        ];

        $secrets = $existing === null
            ? []
            : $existing->secrets();

        if (
            $this->hasClientSecret(
                $data
            )
        ) {
            $secrets['client_secret'] =
                trim(
                    (string) $data[
                    'client_secret'
                    ]
                );
        }

        return [
            'name' => trim(
                (string) $data['name']
            ),

            'provider' => $data['provider'],

            'auth_type' => MailAuthenticationType::OAuth2,

            'tenant_identifier' => $data['provider']
                === 'microsoft'
                && $data['tenant_mode']
                === 'specific'
                    ? trim(
                        (string) $data[
                        'tenant_id'
                        ]
                    )
                    : null,

            'configuration' => $configuration,

            'secret_configuration' => $secrets,

            'is_active' => (bool) $data['is_active'],

            'health_status' => $existing === null
                    ? MailboxHealthStatus::Unknown
                    : $existing->getAttribute(
                        'health_status'
                    ),
        ];
    }

    private function hasClientSecret(
        array $data
    ): bool {
        return
            isset($data['client_secret'])
            && is_string(
                $data['client_secret']
            )
            && trim(
                $data['client_secret']
            ) !== '';
    }
}
