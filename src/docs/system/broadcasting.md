# Real-time / Broadcasting

SimpleDesk manages Laravel broadcasting ownership, broadcaster configuration, browser client metadata, health verification, and administrative diagnostics.

The lifecycle of the Reverb server itself remains deployment-operated. SimpleDesk does not start, stop, reconfigure, or provision the Reverb process from the admin interface and does not mutate deployment environment files.

## Architecture

The broadcasting subsystem separates infrastructure access from subsystem configuration.

An Infrastructure Connection describes how SimpleDesk reaches and authenticates against a provider.

A Broadcast Driver Configuration describes how the broadcasting subsystem uses that provider.

For a managed Reverb setup:

Browser
→ public WebSocket endpoint
→ nginx or another edge proxy
→ Reverb

Laravel
→ managed broadcaster
→ internal publisher endpoint
→ Reverb

The publisher endpoint and browser endpoint are intentionally separate.

An internal Docker hostname such as `reverb:8080` is valid for Laravel publisher traffic but must never be automatically exposed to browser clients.

## Ownership

Broadcasting supports two ownership modes:

- Deployment
- Managed

### Deployment ownership

Deployment ownership leaves Laravel's deployment-selected broadcaster under deployment control.

The stable deployment connection is captured from `BROADCAST_CONNECTION` in:

`simpledesk-broadcasting.deployment.connection`

It does not follow later runtime mutation of `broadcasting.default`.

Deployment-owned `log` and `null` broadcasters are valid intentional configurations even though they do not provide external real-time delivery.

SimpleDesk does not synthesize browser metadata for deployment-owned broadcasting.

### Managed ownership

Managed ownership creates the synthetic Laravel connection:

`simpledesk-managed`

and selects it as the effective broadcaster.

Named deployment connections are never overwritten.

Missing, archived, disabled, structurally invalid, or colliding managed state fails runtime bootstrap instead of silently falling back to deployment configuration.

Database inspection failures during bootstrap are handled through `SystemRuntimeBootstrapPolicy`.

## Supported providers

Managed profiles currently support:

- Reverb
- Pusher

Ably is represented as unavailable and is not currently configurable as a managed provider.

### Reverb

A managed Reverb profile represents Laravel publisher configuration for an already-running Reverb server.

SimpleDesk does not manage the Reverb server process lifecycle.

The deployment environment is responsible for:

- starting `php artisan reverb:start`;
- process supervision or container restart policy;
- listen address and port;
- Reverb application credentials;
- TLS termination;
- load balancing;
- horizontal scaling.

### Pusher

Pusher uses the same Pusher-compatible infrastructure abstraction while retaining Pusher-specific cluster or custom endpoint configuration.

## Infrastructure connections

Broadcast profiles reference Infrastructure Connections instead of storing provider credentials themselves.

Provider secrets are stored only in encrypted Infrastructure Connection credentials.

Sensitive credentials are never returned to the browser.

Blank secret fields preserve existing credentials.

Credential removal is an explicit operation.

An Infrastructure Connection referenced by the active managed broadcaster cannot be disabled, archived, or runtime-mutated.

Metadata-only changes such as changing the connection name remain allowed.

Permanent deletion is blocked while any Queue, Cache, or Broadcasting configuration still references the infrastructure record, including archived configurations.

## Publisher and browser endpoints

Reverb distinguishes two network paths.

### Publisher endpoint

Used by Laravel to publish events.

Typical Docker configuration:

- host: `reverb`
- port: `8080`
- scheme: `http`

This endpoint may be private to the deployment network.

### Public WebSocket endpoint

Used by browser clients.

Typical local configuration:

- host: `localhost`
- port: `8080`
- scheme: `http`

Typical production configuration:

- host: `helpdesk.example.com`
- port: `443`
- scheme: `https`

The browser client receives only safe public metadata:

- broadcaster type;
- public application key;
- public host;
- public port;
- public scheme;
- Pusher cluster when applicable.

The application secret is never exposed.

If a managed Reverb connection has no explicit public browser endpoint, browser broadcasting is treated as unavailable even if publisher health checks succeed.

## Health checks

Managed Reverb and Pusher health checks perform an authenticated, read-only provider API operation.

Health checks do not publish an application event.

Results are persisted as append-only health history.

Secrets and sensitive provider data are redacted before being written to:

- health history;
- HTTP responses;
- audit metadata.

A healthy publisher endpoint does not automatically imply that browser delivery is available.

Browser delivery requires valid public client metadata as well.

## Activation

Normal managed activation requires:

- structurally valid profile state;
- enabled and non-archived configuration;
- valid Infrastructure Connection;
- successful authenticated provider health preflight.

Force activation bypasses operational provider health failure only.

It does not bypass structural validation.

Ownership changes are committed transactionally.

After commit, SimpleDesk signals queue workers to restart so long-running workers load the new broadcaster configuration.

The activation HTTP request itself is not mutated to use the new runtime connection.

If the worker restart signal fails, the broadcasting ownership change remains committed and the failure is surfaced as an operational warning and audit event.

## Runtime bootstrap

During application bootstrap, the broadcasting runtime configurator inspects persisted ownership state.

Deployment mode leaves deployment configuration intact.

Managed mode reconstructs the synthetic `simpledesk-managed` connection.

Managed configuration corruption is considered an explicit runtime error rather than a reason to fall back silently.

The broadcasting runtime is initialized before queue workers begin consuming jobs that may broadcast events.

## Browser client runtime

Authenticated Inertia responses receive safe browser broadcasting metadata through `BroadcastClientConfigurationService`.

The React application passes this metadata to the shared realtime runtime.

Laravel Echo maintains a single active browser connection.

When Inertia navigation returns changed broadcasting metadata:

- the previous Echo connection is disconnected;
- the runtime is rebuilt from the new metadata;
- unavailable broadcasting leaves no managed Echo connection.

Broadcasting changes are therefore picked up on the next Inertia navigation or full page reload.

Already-open idle browser tabs are not actively migrated to another broadcaster without navigation or reload.

## Private channels

SimpleDesk registers authenticated private channels through `routes/channels.php`.

The initial system channel is:

`users.{userId}`

A user may subscribe only to their own user channel.

This provides the foundation for future application-specific real-time functionality without exposing public application channels.

Business-domain channels such as ticket channels are intentionally deferred until those modules are ready.

## Browser delivery probe

The Real-time administration page contains an end-to-end Browser Delivery Probe.

The probe verifies the complete path:

authenticated browser
→ private channel authorization
→ diagnostic HTTP request
→ Laravel broadcasting event
→ active managed broadcaster
→ Reverb or Pusher-compatible provider
→ WebSocket
→ Laravel Echo
→ originating browser

The probe uses a generated UUID to correlate the request with the received WebSocket event.

Successful delivery reports approximate end-to-end latency.

This latency includes the HTTP request, Laravel event dispatch, provider delivery, and browser reception. It is not a raw WebSocket ping measurement.

The probe:

- requires `admin.settings.broadcasting.test`;
- is available only when managed browser broadcasting is available;
- is rate limited;
- broadcasts only to the authenticated user's private channel.

## Reverb deployment

The local Docker environment runs Reverb as a dedicated persistent service.

The Reverb container:

- uses the application PHP image;
- executes `php artisan reverb:start`;
- binds internally to port `8080`;
- is not directly exposed on a host port;
- uses a restart policy;
- has a TCP health check;
- receives Reverb application credentials through a deployment-managed `.env.reverb` file.

The `.env.reverb` file must not be committed.

SimpleDesk never writes to this file.

The credentials configured for the Reverb server application must match the corresponding managed Infrastructure Connection.

Credential coordination remains a deployment responsibility.

## nginx WebSocket proxy

nginx exposes Reverb through the same public application endpoint.

The following paths are proxied to the Reverb container:

- `/app/`
- `/apps/`

WebSocket upgrade headers are forwarded.

Proxy buffering is disabled for WebSocket traffic.

Read and send timeouts are configured longer than the Reverb heartbeat interval.

In local development the path is:

Browser
→ `ws://localhost:8080`
→ nginx
→ `reverb:8080`

## TLS

Local development uses plain HTTP and WebSocket traffic.

Production should terminate TLS at nginx, a reverse proxy, ingress controller, or load balancer.

Recommended production path:

Browser
→ `wss://helpdesk.example.com`
→ TLS termination
→ internal HTTP connection to Reverb

The internal Reverb container does not require its own TLS certificate when TLS is terminated at the edge.

## Allowed origins

Reverb does not accept arbitrary browser origins by default.

Allowed origins are configured through:

`REVERB_ALLOWED_ORIGINS`

Local development uses:

`localhost,127.0.0.1`

Production deployments should explicitly configure only trusted application domains.

Wildcard `*` should not be used in production.

## Reverb scaling

Reverb scaling is currently disabled.

A single Reverb instance is sufficient for the current deployment model.

When horizontal Reverb scaling becomes necessary, Redis-backed Reverb scaling can be enabled through deployment configuration.

This remains a deployment concern and is independent from the SimpleDesk Cache and Queue ownership settings.

## Security boundaries

The broadcasting subsystem follows these boundaries:

- secrets remain server-side;
- browser metadata is explicitly allowlisted;
- publisher endpoints are not substituted for missing public browser endpoints;
- private channels require authenticated authorization;
- active infrastructure cannot be destructively changed;
- normal activation requires provider health;
- force activation requires a separate capability;
- health operations do not emit application events;
- diagnostic browser broadcasting is rate limited;
- Reverb environment credentials remain deployment-managed.

## Operational verification

A complete managed Reverb verification should confirm:

1. Infrastructure Connection health is Healthy.
2. Broadcast profile health is Healthy.
3. Managed profile activation succeeds.
4. Laravel runtime uses `simpledesk-managed`.
5. Browser WebSocket handshake returns HTTP `101 Switching Protocols`.
6. Private user channel authorization succeeds.
7. Browser Delivery Probe reports Delivered.
8. Returning to Deployment removes managed browser metadata after navigation or reload.
9. Reactivation restores the managed WebSocket connection.
10. Archived and disabled profiles cannot become runtime targets without explicit restoration and activation.

## Deferred application integration

The infrastructure is ready for business-domain broadcasting, but business events are intentionally not introduced until the corresponding modules are complete.

Future integrations may include:

- ticket replies;
- ticket assignment changes;
- ticket status changes;
- notifications;
- agent-facing operational updates.

Those integrations must define their own private channel authorization and visibility rules instead of relying on generic public channels.
