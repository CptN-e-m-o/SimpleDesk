# Skills

Skills are named, reusable ticket-classification rules. This first stage stores and validates definitions; it does not evaluate tickets or affect assignment and routing.

## Storage

`skills` contains the name, globally unique slug (including archived rows), description, `any`/`all` match type, active state, ordering, definition version, actor metadata, timestamps, and soft-delete timestamp. `skill_rules` stores ordered conditions with a fixed backend-controlled `subject_type` of `ticket`, a registry field key, operator, JSON value, and order. Archiving a skill preserves its rules; permanent deletion cascades to them.

The version starts at 1 and increases only when the ordered rule definition changes. Editing descriptive metadata alone does not change it. Create, update, duplicate, activation, archive, restore, and permanent deletion are transactional.

## Match types and registry

`ANY` means at least one condition must match. `ALL` means every condition must match. There is one condition level and no nested groups.

`SkillRuleFieldRegistry` is the only source of accepted fields, operators, option labels, and reference validation. The current Ticket schema supports:

- `priority`: Low, Medium, High, Urgent;
- `source`: Portal, Email, API;
- `department_id`: current Department records.

`ticket_form_id`, location, and ticket type fields are not present in the current Ticket schema and are intentionally excluded.

Enum/reference fields support `equals`, `not_equals`, `in`, `not_in`, `is_empty`, and `is_not_empty`. Singular operators require one valid option; set operators require a non-empty array; empty operators accept no value. Errors use indexed keys such as `rules.0.field_key`, `rules.0.operator`, and `rules.0.value`. Arbitrary column names never reach dynamic SQL.

## Access and lifecycle

Routes are under `admin.skills.*`. Permissions are:

- `admin.staff.skills.view`
- `admin.staff.skills.create`
- `admin.staff.skills.update`
- `admin.staff.skills.archive`
- `admin.staff.skills.delete`

Only archived skills can be permanently deleted. This stage has no external usage relations yet; future relations must add checks to `SkillCatalogService::forceDelete()`.

## Deferred work

This stage deliberately does not implement `agent_skill`, `ticket_skill_matches`, ticket evaluation, jobs/listeners, agent selection, or ticket-routing integration.
