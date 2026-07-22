# AGENTS.md

## Stack

- **Backend**: Laravel 13, PHP 8.3, PostgreSQL 16 (production) / SQLite in-memory (tests)
- **Frontend**: React 19, Inertia.js, TypeScript 6, Vite 8, Tailwind CSS 4
- **UI**: shadcn/ui (radix-nova style), Lucide icons
- **Auth**: Laravel Fortify, custom permission/role system (not Spatie)
- **Cache/Queue**: Redis 7, Mailpit for local email

## Setup

```bash
# First-time setup (installs deps, generates key, runs migrations, builds frontend)
composer setup

# Copy Docker env
cp .env.docker.example .env.docker
```

## Dev Server

```bash
# Run all services (artisan serve + queue worker + pail logs + vite)
composer dev
```

App at `http://localhost:8080`. Nginx proxies to PHP-FPM.

## Key Commands (run from `src/`)

```bash
# PHP formatting (Laravel Pint)
./vendor/bin/pint --test          # check
./vendor/bin/pint                 # fix

# PHPStan (level 5, via Larastan)
./vendor/bin/phpstan analyse

# ESLint (JS/TS)
npm run lint                      # check
npm run lint:fix                  # fix

# TypeScript
npm run type-check                # tsc --noEmit

# Tests (PHPUnit, NOT Pest)
composer test                     # clears config cache, then artisan test
php artisan test --filter=TestName   # run single test
```

**CI order**: Pint -> PHPStan -> ESLint -> TypeScript check (see `.github/workflows/code-quality.yml`)

## Project Structure

```
src/
├── app/
│   ├── Actions/Fortify/       # Auth actions (Fortify)
│   ├── Http/Controllers/      # Admin/, Auth/, Tickets/User/
│   ├── Models/                # Ticket, User, Role, Permission, etc.
│   ├── Policies/              # Department, Team, User policies
│   ├── Repositories/Tickets/  # Data access layer
│   ├── Services/              # Admin/, Auth/, Tickets/
│   └── Support/               # Agents/, Departments/, Roles/, Teams/
├── resources/js/
│   ├── Pages/                 # Inertia pages (auto-resolved by name)
│   ├── Components/ui/         # shadcn components
│   ├── Layouts/               # AdminLayout, AgentLayout, AuthLayout, UserLayout
│   ├── hooks/                 # usePermissions.ts
│   └── types/                 # agent.ts, department.ts, role.ts, team.ts
├── routes/web.php             # All routes, role-based middleware
├── tests/                     # Feature/, Unit/ (PHPUnit)
└── config/                    # Standard Laravel config
```

## Architecture Conventions

- **Inertia.js**: Pages in `resources/js/Pages/` are resolved by name. `app.tsx` uses `import.meta.glob` to load them.
- **Roles**: Three distinct roles (Admin, Agent, User) with separate layouts and route prefixes (`/admin`, `/agent`, `/tickets`).
- **Permission middleware**: Routes use `permission:` middleware with pipe-separated permission strings.
- **Repository pattern**: Ticket data access lives in `app/Repositories/Tickets/`.
- **Service layer**: Business logic in `app/Services/{Admin,Auth,Tickets}/`.
- **Actions**: Fortify actions in `app/Actions/Fortify/`.

## Testing

- PHPUnit 12 with in-memory SQLite (configured in `phpunit.xml`)
- Tests run without database migrations (uses `:memory:`)
- Redis/cache/queue/mail all stubbed to array/sync in test env
- Run single test: `php artisan test --filter=TestName`

## TypeScript Path Alias

`@/*` maps to `resources/js/*` (configured in `tsconfig.json`). Use `@/Components/...`, `@/hooks/...`, etc.

## Formatting & Style

- 4-space indent for PHP/JS (`.editorconfig`)
- 2-space indent for YAML
- Laravel Pint for PHP formatting
- ESLint with React + React Hooks plugins
- shadcn components: `components.json` defines aliases as `@/Components`, `@/lib/utils`, `@/hooks`

## Docker

- PHP 8.3 FPM with extensions: pdo_pgsql, redis, gd, intl, bcmath, exif
- Nginx 1.27 Alpine on port 8080
- PostgreSQL 16 Alpine (mapped to host port 5433)
- Redis 7 Alpine
- Mailpit on ports 8025 (UI) and 1025 (SMTP)
