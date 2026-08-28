# Search drivers

SimpleDesk manages Laravel Scout runtime ownership separately from search provider infrastructure.

A Search Driver Configuration describes which Scout engine the Search subsystem should use.

An Infrastructure Connection describes how SimpleDesk reaches and authenticates against an external search provider.

Search profiles never duplicate provider credentials.

## Scope

The current Search subsystem is the Search runtime and control plane.

It manages:

- Search ownership;
- managed Search profiles;
- Laravel Scout engine selection;
- external provider connectivity;
- health checks;
- safe activation;
- deployment return;
- runtime bootstrap;
- infrastructure dependency protection;
- permissions and audit history.

The current subsystem does not manage application search indexes or searchable domain models.

The following remain intentionally deferred:

- Ticket indexing;
- Knowledge Base indexing;
- searchable model registration;
- index schemas;
- searchable attributes;
- filterable and sortable attributes;
- index creation and deletion;
- reindex orchestration;
- Scout import jobs;
- browser search keys;
- global application search;
- domain-specific search UI.

Provider connectivity being Healthy means the provider is reachable and authenticated. It does not mean that application indexes already exist or contain current domain data.

## Ownership

Search supports two ownership modes:

- Deployment
- Managed

### Deployment ownership

Deployment ownership leaves the Laravel Scout engine selected by the deployment unchanged.

The deployment target is captured before managed Search runtime configuration mutates Laravel Scout configuration.

SimpleDesk uses a pristine in-memory deployment configuration snapshot instead of reading the already-mutated runtime configuration.

This prevents a managed process from accidentally treating `simpledesk-managed` as the deployment return target.

Deployment-owned Search may use:

- database;
- collection;
- meilisearch;
- typesense;
- algolia.

The deployment remains responsible for all provider credentials and environment configuration in this mode.

SimpleDesk does not copy deployment Search credentials into the database.

### Managed ownership

Managed ownership selects a persisted Search profile.

Laravel Scout is configured to use the synthetic engine:

`simpledesk-managed`

The synthetic engine delegates to the real Search driver resolved from the active profile.

The real managed driver may be:

- database;
- meilisearch;
- typesense;
- algolia.

The synthetic engine must never delegate to itself.

If managed ownership is persisted but its active profile or referenced infrastructure is missing, archived, disabled, unsupported, or structurally invalid, runtime bootstrap fails explicitly instead of silently falling back to deployment configuration.

## Runtime bootstrap

Search runtime configuration is applied during application bootstrap.

The Search provider boots after Infrastructure, Cache, and Broadcasting and before Queue.

This ensures that long-running queue workers start with the correct Search runtime configuration.

During bootstrap:

- missing Search tables are tolerated while migrations or package discovery are running;
- deployment ownership leaves Scout deployment configuration untouched;
- managed ownership resolves the active Search profile;
- external provider connectivity is merged into the existing Scout provider configuration;
- future Scout index and model settings are preserved;
- `scout.driver` is changed to `simpledesk-managed`;
- the resolved real driver is stored only in process memory.

Runtime bootstrap never writes Search state to the database.

Corrupt managed ownership does not fall back silently.

## Managed providers

### Database

The database Search driver uses Laravel Scout's database engine.

It does not require an Infrastructure Connection.

The managed database driver is available only on supported relational application databases.

SQLite is not treated as an operational production Search target.

Database health verifies application database connectivity without modifying application data.

### Meilisearch

Meilisearch uses a managed Meilisearch Infrastructure Connection.

The Infrastructure Connection contains:

- host;
- encrypted API key.

The Search profile stores only the Infrastructure Connection reference.

Runtime Search configuration receives the provider host and API key from the Infrastructure Connection.

Existing Scout Meilisearch index settings and model settings are preserved.

### Typesense

Typesense uses a managed Typesense Infrastructure Connection.

The Infrastructure Connection contains:

- one or more nodes;
- connection timeout;
- healthcheck interval;
- retry count;
- retry interval;
- encrypted API key.

A node contains:

- host;
- port;
- protocol;
- optional path.

The Search profile stores only the Infrastructure Connection reference.

Normal Typesense runtime settings are separate from administrative health probe limits.

### Algolia

Algolia uses a managed Algolia Infrastructure Connection.

The Infrastructure Connection contains:

- application ID;
- encrypted API key.

The Search profile stores only the Infrastructure Connection reference.

Algolia is an external SaaS provider and is not provisioned by SimpleDesk.

## Infrastructure connections

External managed Search profiles reference Infrastructure Connections.

Supported Search infrastructure types are:

- Meilisearch;
- Typesense;
- Algolia.

Search provider credentials remain encrypted in Infrastructure Connections.

API keys are never returned to the browser after they have been stored.

Leaving an API key field blank while editing preserves the existing credential.

Replacing a credential requires explicitly submitting a new value.

Search profiles themselves contain no provider credentials.

An Infrastructure Connection referenced by the active managed Search profile cannot be:

- disabled;
- archived;
- moved between managed and deployment ownership;
- runtime-mutated;
- credential-rotated;
- otherwise changed in a way that changes the active Search runtime.

Metadata-only changes such as changing the connection display name remain allowed.

Permanent deletion is blocked while any Search profile references the Infrastructure Connection, including archived profiles.

The shared Infrastructure Connection usage guard also accounts for Queue, Cache, and Broadcasting dependencies.

## Search profile structure

A managed Search profile contains:

- name;
- Search driver;
- optional Infrastructure Connection reference;
- enabled state;
- lifecycle metadata.

Provider-specific Search profile configuration is intentionally empty in the current version.

Connectivity belongs to Infrastructure Connections.

Domain index configuration belongs to the future Search data plane.

The Search driver type is immutable after profile creation.

To switch a profile to another engine, create another Search profile and activate it.

## Health checks

Search profile health checks are provider-specific.

Health results are stored as append-only history.

Sensitive provider data is redacted from:

- HTTP responses;
- health history;
- audit metadata.

### Database health

Database health verifies database connectivity with a lightweight read-only query.

### Meilisearch health

Meilisearch health verifies:

- provider health;
- authenticated API access.

The Infrastructure Connection health check performs both a health request and authenticated statistics access.

This prevents an unauthenticated public health endpoint from being treated as proof that the configured API key works.

Administrative Meilisearch health clients use bounded connection and request timeouts.

### Typesense health

Typesense health uses a separate bounded administrative client.

Administrative probe timeout and retry policy are independent from the normal Search runtime client settings.

The health operation verifies authenticated read-only provider access.

### Algolia health

Algolia health verifies authenticated access using a limited index listing operation.

Health checks do not create indexes or modify provider data.

## Activation

Creating or updating a Search profile does not activate it.

Activation is a separate explicit operation.

Normal managed activation requires:

- enabled profile;
- non-archived profile;
- supported Search driver;
- structurally valid Search profile;
- structurally valid referenced Infrastructure Connection;
- fresh Healthy provider preflight.

Structural validation happens independently from operational health.

A malformed configuration cannot be converted into a health failure and then bypassed through force activation.

### Force activation

Force activation may bypass an operational health failure only.

It cannot bypass:

- missing Search profiles;
- archived profiles;
- disabled profiles;
- invalid driver types;
- malformed provider configuration;
- missing Infrastructure Connections;
- wrong Infrastructure Connection types;
- archived Infrastructure Connections;
- disabled Infrastructure Connections;
- recursive synthetic Search engine delegation.

Force activation requires a separate permission.

## Concurrency protection

Search activation protects against configuration changes between preflight and commit.

Before the transaction, SimpleDesk records fingerprints of:

- the target Search profile;
- the referenced Infrastructure Connection when applicable.

The Infrastructure fingerprint includes the encrypted credential representation without decrypting or exposing the secret.

During activation the subsystem locks:

- Search settings;
- the target Search profile;
- the referenced Infrastructure Connection.

The current fingerprints are compared with the preflight fingerprints.

If Search runtime configuration changed while activation was being prepared, activation is rejected and must be retried.

Ownership state is also checked for concurrent changes.

## Runtime fingerprints

Runtime fingerprints provide optimistic protection around provider preflight.

They cover runtime-sensitive Search profile and Infrastructure Connection state.

They are not persisted as secrets and do not require decrypting provider credentials.

The fingerprint allows SimpleDesk to determine that the provider configuration tested before activation is still the configuration being committed.

## Returning to deployment

Managed Search ownership may be returned to deployment explicitly.

The deployment target is resolved from the pristine deployment configuration snapshot.

The same safety model applies:

- structural validation is mandatory;
- normal return requires Healthy deployment provider preflight when applicable;
- force return may bypass operational health failure only;
- malformed deployment configuration can never be force-activated.

The stable deployment target is never inferred from the managed `scout.driver` runtime value.

## Worker lifecycle

Search ownership changes are persisted first.

After the transaction commits, SimpleDesk signals queue workers to restart.

The activation HTTP request does not mutate itself to use the newly selected Search runtime.

Replacement processes apply the new Search ownership during bootstrap.

A successful restart signal means workers were asked to restart. It does not prove that every deployment worker has already terminated and restarted.

If restart signaling fails:

- Search ownership remains committed;
- the failure is surfaced to the administrator;
- an audit event records the restart signaling failure.

Deployment process supervision remains responsible for ensuring worker replacement.

## Permissions

Search administration uses separate capabilities:

- `admin.settings.search.view`
- `admin.settings.search.create`
- `admin.settings.search.update`
- `admin.settings.search.archive`
- `admin.settings.search.delete`
- `admin.settings.search.test`
- `admin.settings.search.activate`
- `admin.settings.search.force_activate`

Force activation is intentionally separated from normal activation.

## Local Meilisearch verification

The local development environment may run Meilisearch as deployment-operated infrastructure.

The Meilisearch process is not managed by the SimpleDesk admin interface.

Typical Docker network endpoint:

`http://meilisearch:7700`

A local Infrastructure Connection should use the Docker service hostname rather than the browser host address because provider requests originate from the application container.

A complete managed Meilisearch verification is:

1. Start the Meilisearch container.
2. Create an enabled managed Meilisearch Infrastructure Connection.
3. Verify Infrastructure Connection health.
4. Create an enabled Meilisearch Search profile.
5. Select the Infrastructure Connection.
6. Verify Search profile health.
7. Activate the Search profile.
8. Restart or replace application and queue processes.
9. Verify that Scout uses `simpledesk-managed`.
10. Verify that the resolved Search engine is Meilisearch.
11. Return Search ownership to deployment.
12. Verify deployment ownership after process bootstrap.

A Healthy Meilisearch runtime does not imply that Ticket or Knowledge Base indexes exist. Domain indexing is a separate future layer.

## Security boundaries

The Search subsystem follows these boundaries:

- provider credentials remain in Infrastructure Connections;
- credentials are encrypted at rest;
- secrets are not returned to the browser;
- health and audit payloads are redacted;
- Search profiles do not duplicate infrastructure credentials;
- active Search infrastructure cannot be runtime-mutated;
- normal activation requires fresh provider health;
- force activation cannot bypass structural validation;
- deployment credentials are never copied into managed persistence;
- runtime configuration changes are process-local;
- the application does not rewrite deployment environment files;
- Search provider processes are deployment-operated.

## Operational troubleshooting

If a managed Search profile cannot be activated:

1. Verify that the profile is enabled and not archived.
2. Run the Search profile health check.
3. Verify that the referenced Infrastructure Connection is enabled.
4. Run the Infrastructure Connection health check.
5. Verify provider network reachability from the application container.
6. Verify provider credentials.
7. Verify that the Infrastructure Connection type matches the Search driver.
8. Verify that no runtime configuration changed during activation.
9. Retry activation after correcting the problem.

If ownership commits but worker restart signaling fails, restart queue workers through the deployment process supervisor.

If application bootstrap fails while Search ownership is managed, inspect the active Search profile and its Infrastructure Connection instead of relying on deployment fallback.

## Deferred Search data plane

The runtime/control plane is complete independently from domain indexing.

Future Search work will introduce domain-specific data-plane behavior when the corresponding modules are ready.

That work may include:

- searchable Ticket documents;
- searchable Knowledge Base articles;
- model-specific index schemas;
- reindex/import jobs;
- index readiness and synchronization state;
- index lifecycle operations;
- application search APIs;
- search permissions and visibility filtering;
- end-user and agent Search UI.

Those concerns should build on the current Search runtime without moving provider credentials or provider ownership into domain modules.
