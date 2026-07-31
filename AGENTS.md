# Repository Guidelines

## Project Structure

Application code lives in `src/`; run PHP, Composer, Artisan, npm, and quality commands from that directory.

- `app/Http/Controllers/` — HTTP and Inertia controllers.
- `app/Services/` — business logic grouped by domain.
- `app/Repositories/Tickets/` — ticket data access.
- `app/Models/`, `Policies/`, `Support/`, and `Jobs/` — domain models, authorization, option providers, and queued work.
- `resources/js/Pages/` — Inertia pages; `Components/`, `Layouts/`, `hooks/`, and `types/` contain shared React/TypeScript code.
- `routes/web.php` — role- and permission-protected web routes.
- `tests/Feature/` and `tests/Unit/` — PHPUnit tests.

Before editing, inspect nearby controllers, services, repositories, components, routes, and tests. Follow established boundaries instead of introducing a new pattern unnecessarily.

## Stack and Architecture

SimpleDesk uses Laravel 13 and PHP 8.3 with PostgreSQL 16 in production. Tests use in-memory SQLite, so avoid relying on PostgreSQL-only behavior without dedicated verification. The frontend uses Inertia.js, React 19, TypeScript 6, Vite 8, and Tailwind CSS 4.

Keep business logic in services, ticket persistence in repositories, and request validation in form requests. Inertia pages are resolved from `resources/js/Pages/`. Preserve separate Admin, Agent, and User layouts and route scopes. Authorization uses project policies and custom roles/permissions, not Spatie.

## Development and Verification

```bash
composer setup                  # install dependencies, configure Laravel, migrate, build
composer dev                    # run Laravel, queue worker, logs, and Vite
composer test                   # clear config and run all PHPUnit tests
php artisan test --filter=Name  # run a focused test
./vendor/bin/pint --test        # verify PHP formatting
./vendor/bin/phpstan analyse    # run Larastan/PHPStan
npm run lint                    # lint React and TypeScript
npm run type-check              # check TypeScript types
npm run build                   # create the production frontend bundle
```

Run focused tests first, then checks relevant to changed code. For broad changes, follow CI order: Pint, PHPStan, ESLint, then TypeScript. Run PHPUnit when backend behavior changes.

## Coding and Testing Conventions

Use four-space indentation except two spaces in YAML. Follow Laravel conventions and PSR-4 namespaces. Use PascalCase for PHP classes and React components, camelCase for functions and variables, and the `@/*` alias for `resources/js/*` imports. Use Laravel Pint and ESLint rather than manual style deviations.

Tests use PHPUnit 12, not Pest. Name test classes with the `Test` suffix and place behavioral or HTTP coverage in `Feature`; reserve `Unit` for isolated logic. Do not assume migrations run automatically outside the configured test bootstrap.

## Change Scope and Delivery

Modify only files required by the task. Do not reformat, rename, or clean up unrelated code. Never overwrite user changes. Review `git diff` before finishing and verify that secrets, `.env` files, generated assets, and dependency directories are not included.

Recent commits use short descriptive summaries such as `Diagnostics API was added`. Keep commits focused and clearly describe one logical change. Pull requests should explain behavior, testing, migrations or configuration changes, link related issues, and include screenshots for visible UI changes.

In the final report, list every changed file and summarize each verification command with its result. Explicitly mention checks that were not run and why.
