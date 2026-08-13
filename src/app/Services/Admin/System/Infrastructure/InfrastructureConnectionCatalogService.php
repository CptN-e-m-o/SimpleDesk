<?php

namespace App\Services\Admin\System\Infrastructure;

use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\User\User;
use App\Services\Admin\System\Audit\SystemAuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class InfrastructureConnectionCatalogService
{
    public function __construct(
        private readonly InfrastructureConnectionRegistry $registry,
        private readonly SystemAuditLogger $audit,
    ) {}

    public function create(
        array $data,
        User $actor,
    ): InfrastructureConnection {
        return DB::transaction(function () use (
            $data,
            $actor,
        ) {
            $type =
                InfrastructureConnectionType::tryFrom(
                    (string) $data['type'],
                );

            if (! $type) {
                throw ValidationException::withMessages([
                    'type' => 'Unknown infrastructure connection type.',
                ]);
            }

            $adapter =
                $this->registry->adapter($type);

            $normalized =
                $adapter->validateAndNormalize(
                    $data['configuration'] ?? [],
                    $data['credentials'] ?? [],
                    $data['source'],
                );

            $connection =
                InfrastructureConnection::query()->create([
                    'name' => $data['name'],
                    'type' => $type,
                    'source' => $data['source'],
                    ...$normalized,
                    'is_enabled' =>
                        $data['is_enabled'] ?? true,
                    'created_by' => $actor->id,
                    'updated_by' => $actor->id,
                ]);

            $this->audit->log(
                'infrastructure_connections',
                'create',
                InfrastructureConnection::class,
                $connection->id,
                null,
                $this->safe(
                    $connection,
                    $adapter,
                ),
                [
                    'credentials_changed' =>
                        array_keys(
                            $normalized['credentials'],
                        ),
                ],
                $actor,
            );

            return $connection;
        });
    }

    public function update(
        InfrastructureConnection $connection,
        array $data,
        User $actor,
    ): InfrastructureConnection {
        return DB::transaction(function () use (
            $connection,
            $data,
            $actor,
        ) {
            $connection =
                InfrastructureConnection::query()
                    ->lockForUpdate()
                    ->findOrFail($connection->id);

            $adapter =
                $this->registry->adapter(
                    $connection->type,
                );

            $before = $this->safe(
                $connection,
                $adapter,
            );

            $incoming = array_filter(
                $data['credentials'] ?? [],
                fn (mixed $value): bool =>
                    $value !== null
                    && $value !== '',
            );

            $credentials = [
                ...$connection->secrets(),
                ...$incoming,
            ];

            foreach (
                $data['remove_credentials'] ?? []
                as $field
            ) {
                if (
                    in_array(
                        $field,
                        $adapter->secretFields(),
                        true,
                    )
                ) {
                    unset($credentials[$field]);
                }
            }

            $normalized =
                $adapter->validateAndNormalize(
                    $data['configuration']
                    ?? $connection->configuration,
                    $credentials,
                    $data['source']
                    ?? $connection->source->value,
                );

            $connection->update([
                'name' => $data['name'],
                'source' =>
                    $data['source']
                    ?? $connection->source,
                ...$normalized,

                'is_enabled' =>
                    $data['is_enabled']
                    ?? $connection->is_enabled,

                'updated_by' => $actor->id,
            ]);

            $changedCredentials =
                array_values(
                    array_unique([
                        ...array_keys($incoming),
                        ...(
                            $data[
                            'remove_credentials'
                            ] ?? []
                        ),
                    ]),
                );

            $this->audit->log(
                'infrastructure_connections',
                'update',
                InfrastructureConnection::class,
                $connection->id,
                $before,
                $this->safe(
                    $connection->refresh(),
                    $adapter,
                ),
                [
                    'credentials_changed' =>
                        $changedCredentials,
                ],
                $actor,
            );

            return $connection;
        });
    }

    public function setEnabled(
        InfrastructureConnection $connection,
        bool $enabled,
        User $actor,
    ): InfrastructureConnection {
        return DB::transaction(function () use (
            $connection,
            $enabled,
            $actor,
        ) {
            $adapter =
                $this->registry->adapter(
                    $connection->type,
                );

            $before =
                $this->safe(
                    $connection,
                    $adapter,
                );

            $connection->update([
                'is_enabled' => $enabled,
                'updated_by' => $actor->id,
            ]);

            $this->audit->log(
                'infrastructure_connections',
                $enabled
                    ? 'enable'
                    : 'disable',
                InfrastructureConnection::class,
                $connection->id,
                $before,
                $this->safe(
                    $connection->refresh(),
                    $adapter,
                ),
                actor: $actor,
            );

            return $connection;
        });
    }

    public function archive(
        InfrastructureConnection $connection,
        User $actor,
    ): void {
        DB::transaction(function () use (
            $connection,
            $actor,
        ) {
            $adapter =
                $this->registry->adapter(
                    $connection->type,
                );

            $before =
                $this->safe(
                    $connection,
                    $adapter,
                );

            $connection->update([
                'updated_by' => $actor->id,
            ]);

            $connection->delete();

            $this->audit->log(
                'infrastructure_connections',
                'archive',
                InfrastructureConnection::class,
                $connection->id,
                $before,
                null,
                actor: $actor,
            );
        });
    }

    public function restore(
        int $id,
        User $actor,
    ): InfrastructureConnection {
        return DB::transaction(function () use (
            $id,
            $actor,
        ) {
            $connection =
                InfrastructureConnection::onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail($id);

            $connection->restore();

            $connection->update([
                'updated_by' => $actor->id,
            ]);

            $this->audit->log(
                'infrastructure_connections',
                'restore',
                InfrastructureConnection::class,
                $connection->id,
                null,
                $this->safe(
                    $connection,
                    $this->registry->adapter(
                        $connection->type,
                    ),
                ),
                actor: $actor,
            );

            return $connection;
        });
    }

    public function forceDelete(
        int $id,
        User $actor,
    ): void {
        DB::transaction(function () use (
            $id,
            $actor,
        ) {
            $connection =
                InfrastructureConnection::onlyTrashed()
                    ->lockForUpdate()
                    ->findOrFail($id);

            $before =
                $this->safe(
                    $connection,
                    $this->registry->adapter(
                        $connection->type,
                    ),
                );

            $subjectId = $connection->id;

            $connection->forceDelete();

            $this->audit->log(
                'infrastructure_connections',
                'force-delete',
                InfrastructureConnection::class,
                $subjectId,
                $before,
                null,
                actor: $actor,
            );
        });
    }

    public function safe(
        InfrastructureConnection $connection,
        mixed $adapter = null,
    ): array {
        $adapter ??=
            $this->registry->adapter(
                $connection->type,
            );

        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'type' => $connection->type->value,
            'source' =>
                $connection->source->value,
            'is_enabled' =>
                $connection->is_enabled,
            ...$adapter->publicRepresentation(
                $connection,
            ),
        ];
    }
}
