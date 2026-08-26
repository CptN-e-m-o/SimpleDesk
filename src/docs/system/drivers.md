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

## Ownership

A subsystem may use either:

- Deployment ownership
- Managed ownership

Deployment ownership preserves the deployment-selected Laravel configuration.

Managed ownership allows SimpleDesk to select a persisted configuration and construct a synthetic runtime connection for that subsystem.

Currently implemented managed domains:

- Queue
- Cache
- Broadcasting

Search and Storage remain separate future domains.

## Infrastructure connections

Managed driver configurations may reference Infrastructure Connections.

Infrastructure Connections contain reusable connectivity and authentication information such as:

- Redis;
- Reverb/Pusher-compatible infrastructure;
- future search engines;
- future storage providers.

Driver configurations do not duplicate infrastructure credentials.

Usage guards prevent destructive infrastructure changes while a managed subsystem actively depends on that connection.

## Runtime isolation

Queue, Cache, Broadcasting, Search, and Storage have different runtime semantics.

For that reason:

- Queue has queue-specific workload and worker lifecycle rules.
- Cache has cache-specific semantic health and lock requirements.
- Broadcasting has publisher, browser client, and WebSocket concerns.
- Search will have indexing and query-engine lifecycle concerns.
- Storage will have object/file persistence and visibility concerns.

They must not be collapsed into a single generic driver implementation.
