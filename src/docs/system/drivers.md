# System drivers

SimpleDesk separates infrastructure access from subsystem runtime configuration.

Connection = how SimpleDesk obtains access to an infrastructure resource.

Driver configuration = how a specific SimpleDesk subsystem uses that resource.

The System Drivers section contains five independent configuration domains:

- Queue
- Cache
- Broadcasting
- Search
- Storage

Each domain has its own contracts, registry, persistence model, health behavior, runtime configurator, activation rules, and operational constraints.

There is intentionally no universal Driver contract or generic JSON driver settings layer.

## Current implementation

All current System Driver domains are implemented:

- Queue
- Cache
- Broadcasting
- Search
- Storage

The Drivers control-plane phase is therefore complete.

Future work inside these domains should extend their domain-specific data plane or operational behavior rather than introducing a universal Driver abstraction.

## Ownership

Each subsystem may use either:

- Deployment ownership
- Managed ownership

Deployment ownership preserves the deployment-selected Laravel configuration.

Managed ownership allows SimpleDesk to select persisted subsystem configuration and construct runtime state during application bootstrap.

Each domain owns its activation lifecycle independently.

Changing one subsystem does not automatically change another subsystem.

For example:

- changing Queue ownership does not change Cache;
- changing Cache does not change Broadcasting;
- changing Broadcasting does not change Search;
- changing Search does not change Storage;
- changing Storage does not migrate or change other subsystem data.

## Synthetic runtime targets

Managed domains may expose a synthetic Laravel runtime target named:

`simpledesk-managed`

The meaning of this target is subsystem-specific.

Queue uses it as a managed queue connection.

Cache uses it as a managed cache store.

Broadcasting uses it as a managed broadcaster connection.

Search uses it as a synthetic Laravel Scout engine that delegates to the resolved real managed Search driver.

Storage uses it as a synthetic Laravel filesystem disk representing the active managed private Storage profile.

These implementations remain independent even when they use the same synthetic name.

The name is a subsystem-local runtime convention, not a universal Driver object.

## Infrastructure Connections

Managed driver configurations may reference Infrastructure Connections.

Infrastructure Connections own reusable connectivity and authentication information such as:

- Redis;
- Reverb/Pusher-compatible infrastructure;
- Meilisearch;
- Typesense;
- Algolia;
- Amazon S3;
- S3-compatible object storage.

Driver configurations do not duplicate infrastructure credentials.

Infrastructure credentials are encrypted and are not returned to the browser after storage.

Usage guards prevent destructive or runtime-sensitive Infrastructure Connection changes while an active managed subsystem depends on that connection.

Permanent deletion is blocked while persisted subsystem configurations still reference an Infrastructure Connection where required by that domain.

The shared Infrastructure Connection layer therefore owns connectivity while each System Driver owns subsystem semantics.

## Runtime isolation

Queue, Cache, Broadcasting, Search, and Storage have different runtime semantics.

For that reason:

- Queue has queue workloads, backlog inspection, worker lifecycle, and queue-specific health behavior.
- Cache has cache read/write/delete semantics, atomic locking, and backend-local state concerns.
- Broadcasting has publisher configuration, browser client metadata, WebSocket delivery, and private-channel concerns.
- Search has Scout engine delegation, provider connectivity, indexing lifecycle, and query-engine concerns.
- Storage has filesystem/object persistence, private visibility, integrity probes, prefixes, and file lifecycle concerns.

These domains must not be collapsed into one generic Driver implementation.

## Runtime bootstrap

Subsystem managed ownership is applied during application bootstrap.

Bootstrap order preserves infrastructure and long-running process dependencies.

The current System runtime initialization order is conceptually:

Infrastructure
→ Cache
→ Broadcasting
→ Search
→ Storage
→ Queue

Managed configuration changes are not applied by mutating the activation HTTP request.

Ownership is persisted first.

Long-running queue workers are then signaled to restart so replacement processes bootstrap using the new subsystem runtime state.

Missing subsystem tables during migrations or package discovery are handled without treating incomplete installation state as managed runtime corruption.

Persisted invalid managed ownership, however, fails explicitly rather than silently falling back to deployment configuration.

Runtime bootstrap does not write subsystem ownership to the database.

## Deployment ownership

Deployment ownership remains a first-class state.

SimpleDesk does not rewrite `.env` files from the administration interface.

Deployment-owned configuration remains under deployment control.

Where managed runtime mutation could obscure the original deployment target, the subsystem captures or reads a stable deployment target independently from the mutable runtime default.

Returning to deployment validates the real deployment target instead of assuming that the current Laravel runtime value still represents it.

Deployment credentials are not copied into managed subsystem persistence.

## Managed ownership

Managed ownership uses application persistence to select a subsystem profile.

Managed profiles may reference Infrastructure Connections but contain only subsystem-specific configuration.

Infrastructure connectivity and authentication remain owned by Infrastructure Connections.

Active managed profiles are protected against unsafe mutation.

Referenced active infrastructure is also protected against runtime-sensitive mutation.

Administrators must activate another profile or return the subsystem to deployment before making changes that would invalidate the currently active target.

## Health

Health behavior belongs to each subsystem.

Queue health verifies queue-specific runtime behavior.

Cache health verifies semantic cache operations and locking.

Broadcasting health verifies authenticated provider publisher access and has separate browser-delivery diagnostics.

Search health verifies database or external Scout provider connectivity and authentication.

Storage health verifies real filesystem or object-storage write, read, content comparison, delete, and cleanup behavior.

A generic infrastructure ping is not considered sufficient proof that every subsystem can safely use a resource.

Where external network health operations can block an administrative request, the subsystem should use bounded administrative timeout/retry behavior without necessarily changing normal application runtime policy.

Health history and audit metadata must not contain provider secrets.

## Activation safety

Activation is an explicit administrative operation.

Normal activation requires:

- structurally valid target state;
- enabled and non-archived configuration;
- valid referenced Infrastructure Connection when required;
- subsystem-specific operational health preflight.

Force activation is a privileged recovery mechanism.

Force activation may bypass operational health failure where the subsystem supports that behavior.

It cannot bypass structural invalidity.

Structural errors must not be converted into health errors simply so that force activation can bypass them.

Ownership changes are persisted transactionally.

Subsystems may use:

- row locking;
- ownership-state comparison;
- runtime fingerprints;
- infrastructure fingerprints;
- post-preflight structural revalidation.

Long external network probes should not be performed while holding database row locks.

## Concurrency and lock ordering

Subsystem activation must protect against configuration changes occurring between health preflight and ownership commit.

Where a domain locks both settings and configuration rows, lock ordering must remain consistent between activation and catalog mutations.

Storage, for example, uses:

settings
→ profile
→ referenced Infrastructure Connection

for activation and compatible settings-before-profile ordering for profile mutation.

This reduces deadlock risk and prevents activation from committing runtime state different from the target that was actually tested.

## Queue worker restart awareness

Queue workers are long-running processes and retain Laravel runtime configuration loaded during bootstrap.

Subsystem ownership changes therefore signal workers to restart after commit.

The restart signal is not proof that every worker has already restarted.

Deployment process supervision remains responsible for replacing workers.

A restart signaling failure does not roll back an ownership change that has already committed.

The failure is surfaced and audited as an operational condition.

## Data-plane boundaries

The Drivers area primarily owns runtime and control-plane behavior.

Domain data-plane concerns remain separate.

Examples:

Search control plane owns:

- engine ownership;
- provider connectivity;
- runtime selection.

Search does not yet imply:

- Ticket indexing;
- Knowledge Base indexing;
- reindex orchestration;
- application search UI.

Storage control plane owns:

- filesystem ownership;
- provider connectivity;
- private Storage profiles;
- runtime disk selection.

Storage activation does not imply:

- file migration;
- bucket synchronization;
- consumer migration;
- historical file relocation.

These boundaries prevent control-plane activation from silently triggering large or destructive data operations.

## Stable identities

Synthetic runtime aliases are not automatically stable persistence identities.

This is especially important for Storage.

A persisted object should not store a mutable alias such as `simpledesk-managed` as its historical location unless the application also has a stable storage-target identity model.

Existing Mail attachments therefore continue to use their existing concrete persisted disk identity.

Future consumer integration with managed Storage must preserve the ability to resolve old files after Storage ownership changes.

## Audit

System Driver administrative operations use the System audit log.

Relevant operations include:

- configuration creation;
- updates;
- enable and disable;
- archive and restore;
- permanent configuration deletion;
- health tests;
- activation;
- force activation;
- return to deployment;
- force return to deployment;
- restart signaling failures.

Audit payloads must not expose infrastructure credentials.

## Permissions

Each System Driver domain defines its own permissions.

Subsystem view, create, update, archive, delete, test, activate, and privileged recovery capabilities are not inferred from a universal Driver permission.

This keeps operational authority aligned with the risks of each subsystem.

Privileged force activation remains separated from normal subsystem administration.

## Operational ownership

SimpleDesk manages subsystem configuration.

It does not automatically become the process supervisor or infrastructure orchestrator for every provider.

Examples:

- Reverb server lifecycle remains deployment-operated.
- Meilisearch process lifecycle remains deployment-operated.
- MinIO or another S3-compatible object-storage server remains deployment-operated.
- queue process supervision remains deployment-operated.

The administration UI configures how SimpleDesk uses those resources.

It does not replace deployment-level infrastructure management.

## Current System Drivers status

The current control-plane status is:

Queue: implemented

Cache: implemented

Broadcasting: implemented

Search: implemented

Storage: implemented

All five System Driver categories are available through the System Drivers administration area.

## Documentation

Subsystem-specific behavior is documented separately:

- `queues.md`
- `cache.md`
- `broadcasting.md`
- `search.md`
- `storage.md`

Infrastructure behavior is documented separately in:

- `infrastructure-connections.md`

The System Driver documents should remain focused on subsystem runtime semantics rather than duplicating the Infrastructure Connection persistence and security model.
