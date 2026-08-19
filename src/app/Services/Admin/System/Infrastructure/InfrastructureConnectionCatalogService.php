<?php

namespace App\Services\Admin\System\Infrastructure;

use App\Enums\Admin\System\CacheConfigurationMode;
use App\Enums\Admin\System\InfrastructureConnectionType;
use App\Enums\Admin\System\QueueConfigurationMode;
use App\Models\Admin\System\CacheDriverConfiguration;
use App\Models\Admin\System\CacheDriverSettings;
use App\Models\Admin\System\InfrastructureConnection;
use App\Models\Admin\System\QueueDriverConfiguration;
use App\Models\Admin\System\QueueDriverSettings;
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
        return DB::transaction(function () use ($data, $actor) {
            $type = InfrastructureConnectionType::tryFrom(
                (string) $data['type'],
            );

            if (! $type) {
                throw ValidationException::withMessages([
                    'type' => 'Unknown infrastructure connection type.',
                ]);
            }

            $adapter = $this->registry->adapter($type);

            $normalized = $adapter->validateAndNormalize(
                $data['configuration'] ?? [],
                $data['credentials'] ?? [],
                $data['source'],
            );

            $connection = InfrastructureConnection::query()->create([
                'name' => $data['name'],
                'type' => $type,
                'source' => $data['source'],
                ...$normalized,
                'is_enabled' => $data['is_enabled'] ?? true,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            $this->audit->log(
                'infrastructure_connections',
                'create',
                InfrastructureConnection::class,
                $connection->id,
                null,
                $this->safe($connection, $adapter),
                [
                    'credentials_changed' => array_keys(
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
            $settings = $this->lockQueueSettings();

            $activeQueue = $this->activeManagedQueueUsingConnection(
                $connection->id,
                $settings,
            );
            $activeCache = $this->activeManagedCacheUsingConnection($connection->id, $this->lockCacheSettings());

            $connection = InfrastructureConnection::query()
                ->lockForUpdate()
                ->findOrFail($connection->id);

            $adapter = $this->registry->adapter(
                $connection->type,
            );

            $before = $this->safe(
                $connection,
                $adapter,
            );

            $incoming = array_filter(
                $data['credentials'] ?? [],
                fn (mixed $value): bool => $value !== null && $value !== '',
            );

            $credentials = [
                ...$connection->secrets(),
                ...$incoming,
            ];

            foreach (
                $data['remove_credentials'] ?? [] as $field
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

            $source = (string) (
                $data['source']
                ?? $connection->source->value
            );

            $normalized = $adapter->validateAndNormalize(
                $data['configuration']
                ?? $connection->configuration,
                $credentials,
                $source,
            );

            $enabled = array_key_exists(
                'is_enabled',
                $data,
            )
                ? (bool) $data['is_enabled']
                : $connection->is_enabled;

            if ($activeQueue) {
                $this->assertActiveQueueInfrastructureUpdateIsSafe(
                    connection: $connection,
                    activeQueue: $activeQueue,
                    source: $source,
                    normalized: $normalized,
                    enabled: $enabled,
                );
            }
            if ($activeCache) {
                $this->assertActiveCacheInfrastructureUpdateIsSafe($connection, $activeCache, $source, $normalized, $enabled);
            }

            $connection->update([
                'name' => $data['name'],
                'source' => $source,
                ...$normalized,
                'is_enabled' => $enabled,
                'updated_by' => $actor->id,
            ]);

            $changedCredentials = array_values(
                array_unique([
                    ...array_keys($incoming),
                    ...(
                        $data['remove_credentials']
                        ?? []
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
                    'credentials_changed' => $changedCredentials,
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
            $settings = $this->lockQueueSettings();

            $activeQueue = $this->activeManagedQueueUsingConnection(
                $connection->id,
                $settings,
            );
            $activeCache = $this->activeManagedCacheUsingConnection($connection->id, $this->lockCacheSettings());

            $connection = InfrastructureConnection::query()
                ->lockForUpdate()
                ->findOrFail($connection->id);

            if (! $enabled && $activeQueue) {
                throw ValidationException::withMessages([
                    'is_enabled' => "Infrastructure connection [{$connection->name}] is used by active managed Queue configuration [{$activeQueue->name}] and cannot be disabled.",
                ]);
            }
            if (! $enabled && $activeCache) {
                throw ValidationException::withMessages(['is_enabled' => "Infrastructure connection [{$connection->name}] is used by active managed Cache configuration [{$activeCache->name}] and cannot be disabled."]);
            }

            if ($connection->is_enabled === $enabled) {
                return $connection;
            }

            $adapter = $this->registry->adapter(
                $connection->type,
            );

            $before = $this->safe(
                $connection,
                $adapter,
            );

            $connection->update([
                'is_enabled' => $enabled,
                'updated_by' => $actor->id,
            ]);

            $this->audit->log(
                'infrastructure_connections',
                $enabled ? 'enable' : 'disable',
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
            $settings = $this->lockQueueSettings();

            $activeQueue = $this->activeManagedQueueUsingConnection(
                $connection->id,
                $settings,
            );
            $activeCache = $this->activeManagedCacheUsingConnection($connection->id, $this->lockCacheSettings());

            $connection = InfrastructureConnection::query()
                ->lockForUpdate()
                ->findOrFail($connection->id);

            if ($activeQueue) {
                throw ValidationException::withMessages([
                    'connection' => "Infrastructure connection [{$connection->name}] is used by active managed Queue configuration [{$activeQueue->name}] and cannot be archived.",
                ]);
            }
            if ($activeCache) {
                throw ValidationException::withMessages(['connection' => "Infrastructure connection [{$connection->name}] is used by active managed Cache configuration [{$activeCache->name}] and cannot be archived."]);
            }

            $adapter = $this->registry->adapter(
                $connection->type,
            );

            $before = $this->safe(
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
            $connection = InfrastructureConnection::onlyTrashed()
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
            /*
             * Check both active and archived Queue profiles.
             * Soft-deleted Queue rows still retain their FK.
             */
            $referencingQueue = QueueDriverConfiguration::withTrashed()
                ->where(
                    'infrastructure_connection_id',
                    $id,
                )
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($referencingQueue) {
                throw ValidationException::withMessages([
                    'connection' => "Infrastructure connection cannot be permanently deleted because Queue configuration [{$referencingQueue->name}] still references it.",
                ]);
            }
            $referencingCache = CacheDriverConfiguration::withTrashed()->where('infrastructure_connection_id', $id)->orderBy('id')->lockForUpdate()->first();
            if ($referencingCache) {
                throw ValidationException::withMessages(['connection' => "Infrastructure connection cannot be permanently deleted because Cache configuration [{$referencingCache->name}] still references it."]);
            }

            $connection = InfrastructureConnection::onlyTrashed()
                ->lockForUpdate()
                ->findOrFail($id);

            $before = $this->safe(
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
        $adapter ??= $this->registry->adapter(
            $connection->type,
        );

        return [
            'id' => $connection->id,
            'name' => $connection->name,
            'type' => $connection->type->value,
            'source' => $connection->source->value,
            'is_enabled' => $connection->is_enabled,
            ...$adapter->publicRepresentation(
                $connection,
            ),
        ];
    }

    private function lockQueueSettings(): ?QueueDriverSettings
    {
        return QueueDriverSettings::query()
            ->whereKey(
                QueueDriverSettings::SINGLETON_ID,
            )
            ->lockForUpdate()
            ->first();
    }

    private function lockCacheSettings(): ?CacheDriverSettings
    {
        return CacheDriverSettings::query()->whereKey(CacheDriverSettings::SINGLETON_ID)->lockForUpdate()->first();
    }

    private function activeManagedCacheUsingConnection(int $connectionId, ?CacheDriverSettings $settings): ?CacheDriverConfiguration
    {
        if ($settings === null || $settings->mode !== CacheConfigurationMode::Managed || ! $settings->active_configuration_id) {
            return null;
        }

        return CacheDriverConfiguration::withTrashed()->whereKey($settings->active_configuration_id)->where('infrastructure_connection_id', $connectionId)->lockForUpdate()->first();
    }

    private function assertActiveCacheInfrastructureUpdateIsSafe(InfrastructureConnection $connection, CacheDriverConfiguration $activeCache, string $source, array $normalized, bool $enabled): void
    {
        if (! $enabled) {
            throw ValidationException::withMessages(['is_enabled' => "Infrastructure connection [{$connection->name}] is used by active managed Cache configuration [{$activeCache->name}] and cannot be disabled."]);
        }
        if ($source !== $connection->source->value) {
            throw ValidationException::withMessages(['source' => "Infrastructure connection [{$connection->name}] source cannot change while Cache configuration [{$activeCache->name}] is active."]);
        }
        if (($connection->configuration ?? []) != ($normalized['configuration'] ?? [])) {
            throw ValidationException::withMessages(['configuration' => "Infrastructure runtime settings cannot change while Cache configuration [{$activeCache->name}] is active."]);
        }
        if ($connection->secrets() != ($normalized['credentials'] ?? [])) {
            throw ValidationException::withMessages(['credentials' => "Infrastructure credentials cannot change while Cache configuration [{$activeCache->name}] is active."]);
        }
    }

    private function activeManagedQueueUsingConnection(
        int $connectionId,
        ?QueueDriverSettings $settings,
    ): ?QueueDriverConfiguration {
        if (
            $settings === null
            || $settings->mode !== QueueConfigurationMode::Managed
            || ! $settings->active_configuration_id
        ) {
            return null;
        }

        return QueueDriverConfiguration::withTrashed()
            ->whereKey(
                $settings->active_configuration_id,
            )
            ->where(
                'infrastructure_connection_id',
                $connectionId,
            )
            ->lockForUpdate()
            ->first();
    }

    private function assertActiveQueueInfrastructureUpdateIsSafe(
        InfrastructureConnection $connection,
        QueueDriverConfiguration $activeQueue,
        string $source,
        array $normalized,
        bool $enabled,
    ): void {
        if (! $enabled) {
            throw ValidationException::withMessages([
                'is_enabled' => "Infrastructure connection [{$connection->name}] is used by active managed Queue configuration [{$activeQueue->name}] and cannot be disabled.",
            ]);
        }

        if ($source !== $connection->source->value) {
            throw ValidationException::withMessages([
                'source' => "Infrastructure connection [{$connection->name}] is used by active managed Queue configuration [{$activeQueue->name}]. Its source cannot be changed while the Queue configuration is active.",
            ]);
        }

        $nextConfiguration = $normalized['configuration'] ?? [];

        if (
            ($connection->configuration ?? [])
            != $nextConfiguration
        ) {
            throw ValidationException::withMessages([
                'configuration' => "Infrastructure connection [{$connection->name}] is used by active managed Queue configuration [{$activeQueue->name}]. Runtime connection settings cannot be changed while the Queue configuration is active.",
            ]);
        }

        $nextCredentials = $normalized['credentials'] ?? [];

        if (
            $connection->secrets()
            != $nextCredentials
        ) {
            throw ValidationException::withMessages([
                'credentials' => "Infrastructure connection [{$connection->name}] is used by active managed Queue configuration [{$activeQueue->name}]. Credentials cannot be changed while the Queue configuration is active.",
            ]);
        }
    }
}
