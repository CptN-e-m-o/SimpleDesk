# Agent Statuses

Agent Statuses records an agent's current human working state. It is independent from Work Schedules (when an agent is expected to work) and Online Presence (whether the person currently has an active client/heartbeat). Online Presence is intentionally not implemented here. Email, portal, and API are work channels, not human statuses, so no Email/Web/All Channels statuses exist.

## Data model and enums

`agent_statuses` is the soft-deletable catalog. It stores a unique slug, Lucide icon key, `#RRGGBB` color, independent availability and routing values, optional default duration, system/default/active/selectable flags, ordering, and actor metadata. `agent_status_periods` is the append-only current/history ledger. An open period has no `ended_at`; temporary periods use `expires_at` and `revert_to_status_id`. Actor/source/end metadata makes transitions auditable.

Availability is `available`, `limited`, or `unavailable`. Routing eligibility is `eligible`, `fallback`, or `blocked`. Scope is `global` or `channel`; supported channels are `portal`, `email`, and `api`. Sources are `self`, `admin`, `system`, and `api`; end reasons are `replaced`, `expired`, `cleared`, and `administrative`.

Only one open global period and one open period per agent/channel are permitted by the transactional service. The resolver treats expired open periods as inactive even before the scheduler runs. Missing periods resolve to the single active default. A channel may only make global availability/routing stricter: unavailable > limited > available and blocked > fallback > eligible.

## Catalog and transitions

`AgentStatusCatalogService` creates, updates, duplicates, activates, archives/restores, and atomically changes the default. System slugs and availability/routing semantics are protected; system/default statuses cannot be archived. Custom slugs are generated uniquely.

`AgentStatusService` validates that the target user has an agent role, locks the relevant open rows, ends replaced periods, and creates the next period in one transaction. Self-service additionally requires `is_selectable`. Durations may use the status default, explicit minutes, or an exact expiration (maximum 30 days). `returnToDefault()` is the stable future-facing API for callers.

`AgentStatusResolver` provides `currentStatus`, `currentPeriod`, `availabilityFor`, `routingEligibilityFor`, `canReceiveNewWork`, and paginated history. Reads never mutate storage and do not consult Work Schedules.

`AgentStatusExpirationService` processes due periods in chunks with per-period transactions and row locks, ending them as expired and creating exactly one system-source revert/default period. Run manually with:

```bash
php artisan simpledesk:agent-statuses:expire
```

The scheduler runs it every minute with overlap protection.

## Seed data and permissions

`AgentStatusSeeder` is idempotent and creates Available (default), Busy, Away, Do Not Disturb, Break (15m), Lunch (60m), Meeting (30m), Training, and Focus (60m). It assigns Available only to agents without an open global period. Factories provide availability/routing/system/default/archive/temporary and current/channel/history/expiration states.

Admin permissions are `admin.staff.agent_statuses.view`, `.create`, `.update`, `.archive`, `.manage_agents`, and `.view_history`. Agent permissions are `agent.status.change_own` and `agent.status.view_own_history`. Routes enforce these permissions; UI actions are also hidden where appropriate.

The admin catalog supports paginated status management and the agent status/history pages support global and channel transitions. The Agent layout contains a self-service global selector and history link. Customer layouts receive no status control.

## Testing and boundaries

Run focused tests with `php artisan test --filter=AgentStatus`, then the full suite, Pint, PHPStan, ESLint, TypeScript, and the production build.

This module does not implement Skills, heartbeat/Online Presence, schedule coupling, or ticket auto-assignment. A later real `AgentAvailabilityService` can combine `AgentScheduleResolver`, `AgentStatusResolver`, Skills, and Online Presence without changing this resolver API.
