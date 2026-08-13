# Infrastructure connections

An infrastructure connection describes how SimpleDesk obtains access to an infrastructure resource. It does not select how a product subsystem uses that resource.

## Sources and secrets

Managed connections store non-secret settings in `configuration` and encrypted secrets in `credentials`. Deployment connections reference existing Laravel configuration; SimpleDesk does not copy its credentials. Credentials use Laravel's `encrypted:array` cast, are hidden on the model, and public representations expose only flags such as `password_configured`.

Redis is the first adapter. Deployment mode references a configured Laravel Redis connection. Managed mode creates an isolated temporary Laravel Redis manager and never changes `.env` or global application configuration.

## Health and audit

Redis health tests run PING and a TTL-protected namespaced write/read/delete verification. Every result is appended to health history. Messages and details are redacted before persistence. Create, update, enable, disable, archive, restore, force-delete, and test actions are written to the append-only system audit log with sanitized states.

Permissions separate viewing, creation, update, archive, permanent deletion, testing, and system audit access. `super_admin` receives agent permissions through the existing permission model; the default `admin` role is not granted these permissions.

Adapters implement `InfrastructureConnectionAdapter` and are registered through `simpledesk-infrastructure.php`. Only registered adapters are offered by the UI.
