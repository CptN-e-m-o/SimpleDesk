# Queue drivers

Queue drivers describe how the Queue subsystem executes jobs. They are deliberately separate from Infrastructure Connections, which describe how SimpleDesk reaches an infrastructure resource.

## Configuration ownership

Deployment mode leaves Laravel's `config/queue.php` and environment-backed `QUEUE_CONNECTION` behavior untouched. If the singleton `QueueDriverSettings` row does not exist, SimpleDesk has never taken ownership and no runtime configuration is changed. SimpleDesk never writes `.env`.

Managed mode requires an enabled, non-archived active configuration. At application boot its adapter registers `queue.connections.simpledesk-managed` and selects it as `queue.default`. Invalid managed state fails loudly instead of falling back to deployment configuration, preventing jobs from being split between backends.

## Supported managed drivers

- Database uses an existing application database connection and the standard `jobs` table.
- Redis reuses an enabled Redis Infrastructure Connection. Deployment-source connections reuse their existing Laravel Redis connection name. Managed-source connections register a runtime `database.redis.simpledesk-infrastructure-{id}` connection, including credentials read from encrypted Infrastructure Connection storage.
- Sync executes jobs in the current process and is not recommended for production.

SQS and Beanstalkd are represented in the queue domain but are not registered yet.

Queue configuration never duplicates Redis host, username, password, TLS, or database settings. Existing queue names and Mail jobs are unchanged. Worker restart tracking exists in settings, but activation and restart orchestration will be implemented in a later task.

## Management and observability

The backend management layer stores normalized queue configurations and protects the active managed configuration from editing, disabling, archiving, or permanent deletion until restart orchestration exists. Archived configurations are disabled; restored configurations remain disabled until explicitly enabled.

Each persisted configuration has append-only test history. Database tests verify connectivity and the `jobs` table without inserting rows, Redis tests reuse Infrastructure Connection health semantics, and Sync reports synchronous execution as healthy but not recommended for production. Queue mutations and tests are recorded in System Audit without infrastructure secrets.

The workload registry observes existing Mail configuration for default, incoming, outgoing, and antivirus workloads, including explicit connection overrides. Backlog inspection is read-only and deduplicates physical connection/queue pairs. Permissions separately control view, create, update, archive, permanent delete, and test operations.

Activation, return to deployment, worker restart orchestration, and the frontend are intentionally not implemented. The next frontend task can consume the `/admin/system/drivers/queues` endpoints. SimpleDesk does not write `.env` and this management layer does not change Mail dispatch behavior.
