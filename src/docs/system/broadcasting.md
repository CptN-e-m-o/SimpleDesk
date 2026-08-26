# Real-time / Broadcasting

SimpleDesk manages Laravel broadcaster ownership and outbound event delivery independently from WebSocket server lifecycle management.

## Ownership

Deployment ownership leaves `broadcasting.default` unchanged. The stable deployment target is captured from `BROADCAST_CONNECTION` in `simpledesk-broadcasting.deployment.connection`; it never follows later runtime mutation of `broadcasting.default`.

Managed ownership creates only the synthetic `simpledesk-managed` connection and selects it as Laravel's default broadcaster. Named deployment connections are never overwritten. Missing, archived, disabled, structurally invalid, or colliding managed state fails bootstrap instead of falling back to deployment. Database inspection failures follow `SystemRuntimeBootstrapPolicy`.

## Providers and secrets

Managed Reverb and Pusher profiles reference encrypted Infrastructure Connections. Profiles do not contain credentials. Reverb means an existing, deployment-operated Pusher-protocol publisher endpoint in this phase. Ably is visible as unavailable because its PHP SDK is not installed.

Health checks perform an authenticated, read-only channel-list API request and never publish an application event. Broadcast health is append-only; provider secrets are redacted before persistence, responses, and audit metadata. Safe future browser metadata deliberately includes only the public application key and explicitly configured public endpoint fields. It never includes the app secret or substitutes an internal publisher endpoint for missing public metadata.

Deployment-owned `log` and `null` connections are valid intentional choices. They can be restored without pretending to provide external delivery.

## Activation

Normal activation requires structural validation and a healthy authenticated provider preflight. Force activation bypasses only operational health failure. Ownership changes commit before a queue-worker restart is signaled, and the activation HTTP process keeps its existing broadcaster. A failed restart signal leaves committed ownership intact and creates a visible warning plus an audit event.

## Deferred scope

This phase does not manage `reverb:start`, Reverb listen addresses, Docker services, nginx WebSocket proxying, or global Echo initialization. A later Reverb runtime phase can pair safe public client metadata with separately managed server runtime.
