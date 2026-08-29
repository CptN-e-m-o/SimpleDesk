# Cache drivers

SimpleDesk separates Cache driver configurations from Infrastructure Connections. A Cache profile describes how Laravel uses cache; Redis connectivity and encrypted credentials remain owned by an `InfrastructureConnection`, referenced through `cache_driver_configurations.infrastructure_connection_id`.

## Ownership and runtime

With no settings row, or with ownership set to `deployment`, SimpleDesk leaves Laravel's deployment-selected cache store untouched. The stable return target is read directly from `CACHE_STORE` into `simpledesk-cache.deployment.store`; it is never inferred from the mutable `cache.default` value.

In `managed` mode, bootstrap validates the active profile, registers any managed Redis runtime connection, installs `cache.stores.simpledesk-managed`, and changes `cache.default` for that process only. No environment or configuration files are rewritten. Missing tables are tolerated during migrations, but corrupt managed ownership fails explicitly.

## Providers

- Database uses an allowlisted database connection and requires both `cache` and `cache_locks` tables.
- File uses a deterministic profile directory below `storage/framework/cache/simpledesk`; administrators cannot supply paths.
- Redis reuses an enabled Redis Infrastructure Connection. Managed connections are registered at runtime; deployment connections retain their Laravel Redis connection name.

Memcached is deferred because the current Infrastructure Connection registry has no Memcached type or encrypted server/credential adapter. DynamoDB is deferred because the AWS SDK is not installed. Octane, array, null, and failover are not exposed as managed production providers.

## Health and activation

Health checks use random short-lived keys and verify write, read equality, deletion, atomic lock acquisition, and lock release. They never flush a store. Results and activation/manual-test audit events are persisted without infrastructure secrets.

Activation preflights the target without changing the request's default store. Ownership is committed transactionally after rechecking locked state and target configuration. The request then issues `queue:restart` through the old cache backend. The signal means workers were asked to restart; it is not proof every worker restarted. Replacement processes apply the new ownership during bootstrap.

Returning to deployment revalidates the exact stable deployment store. Force operations may bypass operational health failure but cannot bypass a missing or malformed target or invalid managed configuration.

Cache contents, atomic locks, and rate-limit state are backend-local and are not migrated or dual-written. A generic flush action is intentionally absent because flushing shared infrastructure can delete another application's data.

## Troubleshooting

If activation is blocked, run Test and verify database cache/lock migrations, storage permissions, or the referenced Redis Infrastructure Connection. If ownership commits but the restart signal fails, restart queue workers through the deployment's process supervisor. Do not change or disable infrastructure referenced by the active managed Cache profile; activate another profile or return to deployment first.
