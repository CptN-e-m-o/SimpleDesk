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
