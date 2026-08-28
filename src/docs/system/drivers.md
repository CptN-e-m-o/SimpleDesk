# System drivers

SimpleDesk separates infrastructure access from subsystem runtime configuration.

Connection = how SimpleDesk obtains access to an infrastructure resource.

Driver configuration = how a specific SimpleDesk subsystem uses that resource.

The System Drivers section contains independent configuration domains:

- Queue
- Cache
- Broadcasting
- Search
- Storage

Each domain has its own contracts, registry, persistence model, health behavior, runtime configurator, activation rules, and operational constraints.

There is intentionally no universal Driver contract or generic JSON driver settings layer.

## Current implementation

Implemented System Driver domains:

- Queue
- Cache
- Broadcasting
- Search

Storage remains the next separate driver domain.

## Ownership

A subsystem may use either:

- Deployment ownership
- Managed ownership

Deployment ownership preserves the deployment-selected Laravel configuration.

Managed ownership allows SimpleDesk to select a persisted configuration and construct subsystem-specific runtime state.

Each domain owns its activation lifecycle independently.

Changing Queue ownership does not change Cache, Broadcasting, or Search ownership.

Changing Search ownership does not change Queue, Cache, or Broadcasting ownership.

## Synthetic runtime targets

Managed domains may expose a synthetic Laravel runtime target named:

`simpledesk-managed`

The meaning of this target is subsystem-specific.

Queue uses it as a managed queue connection.

Cache uses it as a managed cache store.

Broadcasting uses it as a managed broadcaster connection.

Search uses it as a synthetic Laravel Scout engine that delegates to the resolved real managed Search driver.

Subsystem runtime implementations remain independent even when they use the same synthetic name.

## Infrastructure connections

Managed driver configurations may reference Infrastructure Connections.

Infrastructure Connections contain reusable connectivity and authentication information such as:

- Redis;
- Reverb/Pusher-compatible infrastructure;
- Meilisearch;
- Typesense;
- Algolia;
- future storage providers.

Driver configurations do not duplicate infrastructure credentials.

Infrastructure credentials are encrypted and are not returned to the browser after storage.

Usage guards prevent destructive or runtime-sensitive Infrastructure Connection changes while an active managed subsystem depends on that connection.

Permanent deletion is blocked while persisted subsystem configurations still reference an Infrastructure Connection where required by that domain.

## Runtime isolation

Queue, Cache, Broadcasting, Search, and Storage have different runtime semantics.

For that reason:

- Queue has queue-specific workloads, backlog inspection, and worker lifecycle rules.
- Cache has cache-specific semantic health, atomic lock, and backend-local state concerns.
- Broadcasting has publisher, browser client, WebSocket, private-channel, and provider-delivery concerns.
- Search has Scout engine delegation, provider health, indexing lifecycle, and query-engine concerns.
- Storage has object/file persistence, visibility, integrity, and lifecycle concerns.

They must not be collapsed into a single generic driver implementation.

## Runtime bootstrap

Subsystem managed ownership is applied during application bootstrap.

Bootstrap order preserves infrastructure dependencies.

The current System runtime initialization order is conceptually:

Infrastructure
→ Cache
→ Broadcasting
→ Search
→ Queue

Managed configuration changes are not applied by mutating the activation HTTP request.

Ownership is persisted first.

Long-running queue workers are then signaled to restart so replacement processes bootstrap using the new subsystem runtime state.

Missing subsystem tables during migrations or package discovery are handled without treating incomplete installation state as managed runtime corruption.

Persisted invalid managed ownership, however, fails explicitly rather than silently falling back to deployment configuration.

## Deployment ownership

Deployment ownership remains a first-class state.

SimpleDesk does not rewrite `.env` files from the administration interface.

Deployment-owned configuration remains under deployment control.

Where runtime mutation could otherwise obscure the original deployment target, the subsystem captures or reads a stable deployment target independently from the mutable runtime default.

Returning to deployment validates the real deployment target instead of assuming that the current Laravel runtime value still represents it.

## Managed ownership

Managed ownership uses application persistence to select a subsystem profile.

Managed profiles may reference Infrastructure Connections but should contain only subsystem-specific configuration.

Infrastructure connectivity and authentication remain owned by Infrastructure Connections.

Active managed profiles are protected against unsafe mutation.

The referenced active infrastructure is also protected against runtime-sensitive mutation.

Administrators must activate another profile or return the subsystem to deployment before making changes that would invalidate the currently running managed target.

## Health

Health behavior belongs to each subsystem.

Queue health verifies queue-specific runtime behavior.

Cache health verifies cache read/write/delete and locking semantics.

Broadcasting health verifies authenticated provider publisher access and has separate browser-delivery diagnostics.

Search health verifies database or external Scout provider connectivity and authentication.

A generic infrastructure ping is not considered sufficient proof that every subsystem can safely use the same resource.

Health history and audit metadata must not contain provider secrets.

## Activation safety

Activation is an explicit administrative operation.

Normal activation requires:

- structurally valid target state;
- enabled and non-archived configuration;
- valid referenced Infrastructure Connection when required;
- subsystem-specific operational health preflight.

Force activation is a privileged recovery mechanism.

Force activation may bypass operational health failure where the subsystem explicitly supports that behavior.

It cannot bypass structural invalidity.

Ownership changes are persisted transactionally.

Subsystems may use locking, state comparison, or runtime fingerprints to protect activation from concurrent changes.

## Queue worker restart awareness

Queue workers are long-running processes and retain Laravel runtime configuration loaded during bootstrap.

Subsystem ownership changes therefore signal workers to restart after commit.

The restart signal is not proof that every worker has already restarted.

Deployment process supervision remains responsible for replacing workers.

A restart signaling failure does not roll back an ownership change that has already committed.

The failure is surfaced and audited as an operational condition.

## Audit

System Driver administrative operations use the System audit log.

Relevant operations include:

- configuration creation;
- updates;
- enable and disable;
- archive and restore;
- permanent deletion;
- health tests;
- activation;
- force activation;
- return to deployment;
- restart signaling failures.

Audit payloads must not expose infrastructure credentials.

## Permissions

Each System Driver domain defines its own permissions.

Subsystem view, create, update, archive, delete, test, activate, and privileged recovery capabilities are not inferred from a universal Driver permission.

This keeps operational authority aligned with the risks of each subsystem.

## Documentation

Subsystem-specific behavior is documented separately:

- `queues.md`
- `cache.md`
- `broadcasting.md`
- `search.md`

Storage documentation will be introduced together with the Storage driver subsystem.
