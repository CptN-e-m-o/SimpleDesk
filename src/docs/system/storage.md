# Storage drivers

SimpleDesk manages Laravel filesystem runtime ownership separately from storage provider infrastructure and from the lifecycle of already persisted application files.

A Storage Driver Configuration describes which private filesystem target the Storage subsystem should expose as the Laravel default filesystem.

An Infrastructure Connection describes how SimpleDesk reaches and authenticates against an external object-storage provider.

Storage profiles never contain provider credentials.

## Scope

The current Storage subsystem is the Storage runtime and control plane.

It manages:

- Storage ownership;
- managed Storage profiles;
- Laravel default filesystem selection;
- Local private storage;
- Amazon S3;
- S3-compatible object storage;
- provider connectivity;
- object-storage credentials through Infrastructure Connections;
- profile object prefixes;
- health checks;
- safe activation;
- deployment return;
- runtime bootstrap;
- infrastructure dependency protection;
- permissions;
- audit history.

The current subsystem does not migrate application data between storage backends.

The following are intentionally outside Storage v1:

- automatic file migration;
- bucket synchronization;
- dual writes;
- object replication;
- file browser functionality;
- bulk file deletion;
- public managed storage;
- FTP;
- SFTP;
- automatic Mail storage integration;
- attachment migration;
- automatic migration of existing domain files.

Changing Storage ownership changes runtime configuration only.

It does not imply that files already written to another disk are available in the newly activated storage backend.

## Ownership

Storage supports two ownership modes:

- Deployment
- Managed

### Deployment ownership

Deployment ownership leaves the filesystem disk selected by the application deployment unchanged.

The deployment target is based on the stable configured deployment disk rather than on an already-mutated managed runtime value.

By default the deployment disk is derived from:

`FILESYSTEM_DISK`

The original filesystem configuration is captured before managed Storage runtime configuration is applied.

This prevents a managed process from accidentally treating `simpledesk-managed` as the deployment return target.

SimpleDesk does not modify `.env` files when returning to deployment ownership.

Deployment credentials remain controlled by the deployment and are not copied into managed Storage persistence.

### Managed ownership

Managed ownership selects a persisted Storage profile.

Laravel receives a synthetic filesystem disk named:

`simpledesk-managed`

The active profile is converted into the synthetic disk configuration during application bootstrap.

Laravel's:

`filesystems.default`

is then changed to:

`simpledesk-managed`

for that process.

The real managed backend may be:

- Local private storage;
- Amazon S3;
- S3-compatible object storage.

The synthetic disk name is runtime-local configuration.

It is not an object-storage provider and is not a stable historical identity for already persisted files.

If managed ownership is persisted but the active profile or referenced infrastructure is missing, archived, disabled, unsupported, or structurally invalid, runtime bootstrap fails explicitly rather than silently falling back to deployment storage.

## Runtime bootstrap

Storage runtime configuration is applied during application bootstrap.

The System provider order is conceptually:

Infrastructure
→ Cache
→ Broadcasting
→ Search
→ Storage
→ Queue

Storage therefore resolves its managed runtime before long-running queue workers finish bootstrapping.

During bootstrap:

- missing Storage tables are tolerated during installation, migrations, or package discovery;
- deployment ownership leaves Laravel filesystem configuration unchanged;
- managed ownership resolves the active Storage profile;
- the active profile is structurally validated;
- referenced Infrastructure Connections are validated;
- the synthetic `simpledesk-managed` disk is created;
- `filesystems.default` is changed to `simpledesk-managed`.

Runtime bootstrap never writes Storage ownership state to the database.

The Storage provider must not silently overwrite an existing deployment disk named `simpledesk-managed`.

A name collision is treated as invalid managed Storage configuration.

## Managed providers

### Local private storage

Managed Local Storage requires no Infrastructure Connection.

Its root is deterministic and application-owned:

`storage/app/private`

Administrators cannot provide an arbitrary filesystem path.

The managed Local configuration uses private visibility.

The Storage profile configuration is empty.

This prevents the admin interface from being used to escape into arbitrary server directories.

### Amazon S3

Amazon S3 requires an AWS Infrastructure Connection.

The Infrastructure Connection owns:

- region;
- bucket;
- encrypted access key ID;
- encrypted secret access key.

The Storage profile owns only:

- the Infrastructure Connection reference;
- optional object prefix.

The profile never stores AWS credentials.

Laravel uses the S3 Flysystem driver at runtime.

### S3-compatible object storage

S3-compatible Storage requires an S3-compatible Infrastructure Connection.

The Infrastructure Connection owns:

- endpoint;
- region;
- bucket;
- path-style endpoint behavior;
- encrypted access key ID;
- encrypted secret access key.

The Storage profile owns only:

- the Infrastructure Connection reference;
- optional object prefix.

The endpoint must use HTTP or HTTPS.

Credentials embedded into the endpoint URL are rejected.

This driver may be used with compatible providers such as MinIO when their S3 API behavior is compatible with the configured Laravel/Flysystem stack.

## Infrastructure Connections

Storage introduces real managed Infrastructure Connection adapters for:

- AWS;
- S3-compatible object storage.

These connections represent access to one concrete private bucket.

### AWS connection

Safe configuration contains:

- region;
- bucket.

Encrypted credentials contain:

- access key ID;
- secret access key.

### S3-compatible connection

Safe configuration contains:

- endpoint;
- region;
- bucket;
- path-style endpoint flag.

Encrypted credentials contain:

- access key ID;
- secret access key.

Trailing slashes are removed from S3-compatible endpoints during normalization.

Provider credentials are encrypted by the existing Infrastructure Connection secret mechanism.

Stored credentials are not returned to the frontend.

The UI receives credential flags instead.

Leaving credential fields blank while editing preserves the already stored credential.

Submitting a new value rotates that credential.

Storage v1 does not expose implicit credential removal for object-storage credentials.

## Storage profile structure

A managed Storage profile contains:

- name;
- Storage driver;
- optional Infrastructure Connection reference;
- configuration;
- enabled state;
- lifecycle metadata.

The driver is immutable after profile creation.

To change from one Storage driver to another, create a new profile and activate it.

### Object prefix

External object-storage profiles may define an optional prefix.

Example:

`simpledesk`

A prefix is a relative namespace inside the configured bucket.

Normalization:

- removes leading and trailing slashes;
- normalizes repeated separators;
- normalizes backslashes;
- limits length;
- rejects control characters;
- rejects `.` path segments;
- rejects `..` path segments;
- rejects non-string persisted values.

Examples:

`/simpledesk//objects/`

becomes:

`simpledesk/objects`

The prefix does not change the bucket itself.

Local Storage does not expose a configurable prefix in Storage v1.

## Private visibility

Managed Storage v1 is private-only.

Administrators cannot switch a managed Storage profile to public visibility.

Public object delivery, CDN integration, signed browser access, and other public-storage behavior require separate security and lifecycle design and are outside the current control plane.

## Health checks

Storage uses semantic read/write/delete health probes.

A successful network connection alone is insufficient to declare a Storage target Healthy.

Health verification uses a random probe object under:

`.simpledesk-health/<uuid>.probe`

For a profile with an object prefix, the probe is performed inside that profile namespace.

Conceptually the check is:

1. generate random content;
2. write the probe object;
3. read it;
4. compare exact content;
5. delete it;
6. verify cleanup where supported;
7. attempt cleanup again in `finally`.

The probe never:

- flushes a disk;
- recursively deletes objects;
- enumerates application data;
- overwrites a deterministic user object;
- migrates files;
- modifies bucket configuration.

### Local health

Local Storage health verifies actual filesystem write, read, comparison, delete, and cleanup behavior.

### S3 health

AWS and S3-compatible Storage health verifies actual object operations against the configured bucket.

The Infrastructure Connection health check also performs a random object probe.

It does not depend on account-wide `ListBuckets` permission.

Bucket-scoped credentials may therefore be used without granting unnecessary account-wide bucket discovery privileges.

### Administrative timeout policy

Administrative S3 health checks use bounded network behavior.

The current default policy is:

- connection timeout: 2 seconds;
- request timeout: 5 seconds;
- bounded retry count.

This health policy is separate from normal application Storage runtime behavior.

A provider outage should not leave an administrator waiting indefinitely for a health operation.

## Credential failure handling

Stored Infrastructure Connection credentials may become unreadable, for example after invalid encryption state or deployment key changes.

Storage health treats unreadable stored credentials as a controlled Unavailable condition.

Credential loading and provider verification are inside an exception boundary.

Raw provider exceptions and encryption failures are not intentionally exposed to the administrator.

Health and audit payloads are redacted before persistence or HTTP exposure.

## Health history

Storage profile health results are stored as append-only history.

Health history includes safe operational information such as:

- status;
- latency;
- safe message;
- safe details;
- test actor;
- timestamp.

Provider secrets must never appear in:

- health history;
- HTTP responses;
- audit metadata.

## Activation

Creating or updating a Storage profile does not activate it.

Activation is a separate explicit administrative operation.

Normal managed activation requires:

- profile exists;
- profile is enabled;
- profile is not archived;
- supported Storage driver;
- structurally valid profile configuration;
- safe object prefix;
- valid Infrastructure Connection where required;
- matching Infrastructure Connection type;
- managed Infrastructure Connection source;
- enabled Infrastructure Connection;
- non-archived Infrastructure Connection;
- fresh Healthy Storage preflight.

Structural validation is independent from operational health.

A malformed Storage target cannot be converted into an operational failure and then bypassed through force activation.

### Force activation

Force activation may bypass operational health failure only.

It cannot bypass structural problems such as:

- missing profile;
- archived profile;
- disabled profile;
- unsupported driver;
- malformed prefix;
- missing Infrastructure Connection;
- wrong Infrastructure Connection type;
- deployment-owned connection used where managed infrastructure is required;
- archived Infrastructure Connection;
- disabled Infrastructure Connection;
- malformed deployment target.

Force activation requires a separate permission.

## Concurrency protection

Storage activation protects against configuration changes between provider preflight and ownership commit.

Before entering the transaction SimpleDesk records runtime fingerprints for:

- the Storage profile;
- the referenced Infrastructure Connection when applicable.

The Infrastructure fingerprint includes the raw encrypted credential representation.

The credential does not need to be decrypted or logged to detect a concurrent change.

Inside the activation transaction the lock order is:

1. Storage settings;
2. Storage profile;
3. referenced Infrastructure Connection.

The same settings-before-profile ordering is used by Storage catalog mutations to avoid conflicting lock order with activation.

After locking, SimpleDesk verifies:

- ownership has not changed;
- profile fingerprint has not changed;
- Infrastructure fingerprint has not changed;
- target remains structurally valid.

A runtime-sensitive change between preflight and commit causes activation to fail and be retried rather than committing a target different from the one that was tested.

Network health probes are not executed while database row locks are held.

## Runtime fingerprints

Runtime fingerprints cover state that may change the resulting Storage runtime.

Profile fingerprints include runtime-sensitive profile fields.

Infrastructure fingerprints include:

- type;
- source;
- provider configuration;
- encrypted credential representation;
- enabled state;
- archive state.

Fingerprints are SHA-256 values used for concurrency verification.

They are not provider credentials and are not used to recover secret values.

## Returning to deployment

Managed Storage ownership may be returned explicitly to deployment ownership.

The deployment target is resolved from the pristine filesystem configuration snapshot and the stable configured deployment disk.

Returning to deployment follows the same safety principle as managed activation.

Structural validation is mandatory.

For known filesystem types, SimpleDesk performs additional semantic validation.

For example:

- Local requires a valid root;
- S3 requires a valid bucket configuration.

Laravel must also be capable of constructing the selected deployment disk.

Unknown or custom deployment filesystem drivers may remain valid when Laravel has a registered implementation capable of building them.

Normal return performs an operational Storage health preflight.

Force return may bypass operational health failure only.

Force return cannot bypass a missing, unsupported, or structurally malformed deployment disk.

## Worker lifecycle

Storage ownership changes are persisted first.

After the ownership transaction commits, SimpleDesk signals queue workers to restart.

The activating HTTP request is not mutated to begin using the new filesystem.

Replacement processes apply Storage ownership during normal bootstrap.

This avoids one request operating partly against the old storage runtime and partly against the new one.

A successful restart signal means workers were instructed to restart.

It does not prove that every deployment worker has already restarted.

If restart signaling fails:

- Storage ownership remains committed;
- ownership is not rolled back;
- the administrator receives an operational warning;
- the failure is recorded in System audit.

Deployment process supervision remains responsible for replacing long-running workers.

## Infrastructure dependency protection

An Infrastructure Connection used by the active managed Storage profile cannot be changed in a way that invalidates the current runtime.

While active, SimpleDesk blocks:

- disabling the connection;
- archiving the connection;
- source changes;
- provider configuration changes;
- credential rotation;
- credential removal.

Metadata-only changes such as changing the connection display name remain allowed.

Permanent Infrastructure Connection deletion is blocked while any Storage profile references that connection.

This includes:

- enabled profiles;
- disabled profiles;
- inactive profiles;
- archived profiles.

Storage protection is part of the shared Infrastructure Connection usage guard together with Queue, Cache, Broadcasting, and Search.

## Storage profile lifecycle

Storage profiles support:

- create;
- update;
- enable;
- disable;
- archive;
- restore;
- permanent configuration deletion.

An active managed Storage profile cannot be mutated.

Restoring an archived profile restores it disabled.

The administrator must explicitly enable it before activation.

Permanently deleting a Storage profile deletes only the control-plane configuration.

It never deletes objects from the underlying filesystem or bucket.

## Existing files

Storage activation performs no data migration.

It does not:

- enumerate existing files;
- copy existing files;
- move existing files;
- delete existing files;
- synchronize source and destination backends;
- verify that old data exists on the new backend.

Storage backends are treated as independent data locations.

Applications that require migration between Storage targets need a separate explicit migration workflow.

## Mail storage boundary

The existing Mail subsystem is intentionally not connected to the mutable `simpledesk-managed` alias in Storage v1.

Mail persists concrete disk identities together with stored file paths.

This is important because an alias whose target changes over time is not a safe historical storage identity.

For example, storing:

`simpledesk-managed`

as the persisted disk name and later changing its target from Local to S3 would make an old path resolve against the wrong backend.

Therefore Storage v1 does not modify:

- Mail storage configuration;
- existing EmailAttachment disk values;
- raw message disk values;
- attachment download behavior;
- antivirus storage behavior;
- existing Mail files.

Future integration should introduce a stable storage-target or stored-object identity model before mutable managed Storage is used by components that persist disk identity.

## Permissions

Storage administration uses separate capabilities:

- `admin.settings.storage.view`
- `admin.settings.storage.create`
- `admin.settings.storage.update`
- `admin.settings.storage.archive`
- `admin.settings.storage.delete`
- `admin.settings.storage.test`
- `admin.settings.storage.activate`
- `admin.settings.storage.force_activate`

The normal admin role receives Storage management capabilities except force activation.

The super admin role receives force activation capability as well.

Force activation is intentionally separated from routine Storage administration.

## Local MinIO verification

The development environment may run MinIO as deployment-operated S3-compatible infrastructure.

MinIO is not provisioned or controlled by the SimpleDesk admin interface.

Within the Docker network the provider endpoint is:

`http://minio:9000`

The browser host endpoint may differ, for example:

`http://localhost:9000`

An Infrastructure Connection must use the Docker service hostname because provider requests originate from the application container.

A complete S3-compatible verification flow is:

1. Start MinIO.
2. Ensure the private development bucket exists.
3. Create an enabled managed S3-compatible Infrastructure Connection.
4. Configure the MinIO endpoint, region, bucket, path-style behavior, and credentials.
5. Verify Infrastructure Connection health.
6. Create an enabled S3-compatible Storage profile.
7. Select the MinIO Infrastructure Connection.
8. Optionally configure a profile prefix.
9. Verify Storage profile health.
10. Activate the Storage profile.
11. Bootstrap a new application process.
12. Verify that `filesystems.default` resolves to `simpledesk-managed`.
13. Verify that the synthetic disk resolves to the S3 driver and expected MinIO endpoint, bucket, and prefix.
14. Write a real object through `Storage::disk('simpledesk-managed')`.
15. Read the exact object content.
16. Verify the object exists in the expected bucket and prefix.
17. Delete the object.
18. Verify the object no longer exists.
19. Return Storage ownership to deployment.
20. Bootstrap a new process and verify the deployment disk is effective again.

This flow has been used to verify the S3-compatible control plane against MinIO.

It verifies:

SimpleDesk ownership
→ Storage runtime bootstrap
→ Laravel filesystem
→ Flysystem S3 adapter
→ AWS SDK
→ Docker network
→ MinIO
→ bucket
→ object operations

## Security boundaries

The Storage subsystem follows these boundaries:

- all managed Storage is private;
- arbitrary local filesystem roots are not accepted;
- object-storage credentials remain in Infrastructure Connections;
- credentials are encrypted at rest;
- credentials are not returned to the Storage profile UI;
- health and audit data are redacted;
- Storage profiles do not duplicate provider credentials;
- active Storage infrastructure cannot be runtime-mutated;
- normal activation requires a fresh semantic health check;
- force activation cannot bypass structural validation;
- deployment credentials are never copied into managed persistence;
- runtime configuration is applied during process bootstrap;
- the application does not rewrite deployment environment files;
- activating Storage does not migrate data;
- deleting a Storage profile does not delete storage contents;
- Infrastructure health uses random isolated probe objects;
- administrative external health checks use bounded network behavior.

## Operational troubleshooting

If an external Storage profile cannot be activated:

1. Verify that the Storage profile is enabled and not archived.
2. Run the Storage profile health check.
3. Verify the referenced Infrastructure Connection.
4. Verify that the connection is enabled and not archived.
5. Run the Infrastructure Connection health check.
6. Verify the bucket exists.
7. Verify provider credentials have object read/write/delete permission.
8. Verify endpoint reachability from the application process.
9. For Docker providers, verify the internal Docker hostname rather than using `localhost`.
10. Verify region and endpoint behavior.
11. For compatible providers, verify whether path-style endpoints are required.
12. Verify the profile prefix is valid.
13. Retry activation after correcting the problem.

If managed Storage bootstrap fails:

1. inspect the persisted ownership mode;
2. inspect the active Storage profile;
3. inspect the referenced Infrastructure Connection;
4. verify that no active profile or infrastructure was archived or disabled unexpectedly;
5. verify provider configuration;
6. do not rely on silent deployment fallback.

If ownership commits but worker restart signaling fails, restart queue workers through deployment process supervision.

If return to deployment is blocked, inspect the configured deployment filesystem disk and correct its structural configuration before retrying.

## Future Storage work

The current runtime/control plane is complete independently from application file migration.

Future work may include:

- stable storage-target identities;
- stored-object metadata;
- explicit storage migrations;
- migration progress tracking;
- resumable copy workflows;
- checksums and integrity verification;
- source and destination reconciliation;
- consumer-specific Storage policies;
- optional public object delivery;
- signed URLs;
- retention and lifecycle policies;
- storage quotas;
- domain-specific attachment integration.

Those features should build on the current Storage control plane without turning mutable runtime aliases into historical object identities.
