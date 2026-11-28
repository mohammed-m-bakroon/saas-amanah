# Amanah — project context for Claude

Multi-tenant SaaS for nonprofits (charities). A charity registers, gets `{subdomain}.amanah.app` with a donation
site + dashboard, and pays a monthly subscription. Portfolio/CV project: 1 developer, 8-week MVP.
Full plan with week-by-week checklists: `PLAN.md` (derived from the SRS doc). Working name "Amanah" is temporary.

## Status (updated 2026-11-28)

- **Week 1 is DONE**: scaffold, CI, tenancy core, isolation tests. CI green, `main` protected.
- **Next: Week 2 — FR-01..FR-05** (see PLAN.md): charity registration (name, email, subdomain; reserved words in
  `config/tenancy.php`), email verification gating activation (`pending` → `active`), onboarding wizard (logo,
  colors, currency, default locale), team invitations by email with roles (Owner / Admin / Accountant via
  spatie/laravel-permission in teams mode keyed by `tenant_id`), optional tenant switcher (FR-05, Should).
  AR/EN + RTL from the start. Acceptance: new charity reaches its dashboard on its subdomain in < 5 minutes.
- PLAN.md checkboxes for Week 1 are partly stale (Docker, local subdomains, branch protection are done now).

## Stack & decisions (already made — don't re-litigate)

- **Laravel 12** (not 11 as the SRS says: 11 is EOL with unpatched CVEs). PHP 8.3 in Sail and CI.
- Modular monolith, **single DB with `tenant_id`** on every tenant table. **Hand-written tenancy** (no stancl/tenancy).
- Livewire 3 + Alpine for UI (planned), MySQL 8.4, Redis, Mailpit, Pest, Larastan level 6, Pint.
- Money always as integer minor units. Stripe (Cashier + Connect) comes in Weeks 3–6.
- Local domain: **`lvh.me`** (every subdomain → 127.0.0.1). `TENANCY_CENTRAL_DOMAIN=lvh.me`,
  `SESSION_DOMAIN=.lvh.me`. Production will be a real domain + wildcard DNS `*.domain`. Tests use `amanah.test`.

## Tenancy architecture (app/Domain/Tenancy)

- `ResolveTenant` middleware (`app/Http/Middleware`): host `{sub}.{central}` → `Tenant`; 404 unknown, 403 suspended,
  nested subdomains rejected; forgets the `subdomain` route param; unbinds after the request.
- `TenantManager` singleton: `set/forget/check/current/id/idOrFail/runAs`.
- `BelongsToTenant` trait: global `TenantScope` that **throws `MissingTenantException` when no tenant is bound**
  (fail closed); fills `tenant_id` on create; throws on writing/moving a row to another tenant;
  `Model::withoutTenancy()` is the only cross-tenant escape hatch (Super Admin/platform code).
- `QueueTenancy`: puts `tenant_id` into every queued job payload and re-binds it on processing; restores the
  previous tenant afterwards (sync driver). No per-job code needed.
- Routing (`bootstrap/app.php`): `routes/web.php` on the central domain, `routes/tenant.php` on
  `{subdomain}.{central}` behind `ResolveTenant`.
- `TenantStatus` enum: pending, active, read_only, suspended.

## Tests & quality

- `tests/Feature/Tenancy/IsolationTest.php`: dataset `tenantModels` — **add every new tenant-scoped model there**
  in the same PR. `EveryTenantTableIsScopedTest` fails CI if a model with a `tenant_id` column lacks the trait.
- `tests/Fixtures/Note` is a test-only tenant model (table created in the test).
- Tests run on in-memory SQLite, forced in `phpunit.xml` (`force="true"`). `sail:install` once rewrote phpunit.xml
  to MySQL — don't let that happen again.
- `phpstan.neon` analyses `tests/Fixtures` too (so the trait is analysed through a real user).
- Commands: `sail test`, `sail composer lint` (Pint), `sail composer analyse` (PHPStan).
- CI (`.github/workflows/ci.yml`): Pint --test, PHPStan, Pest `--coverage --min=80`. Required check on `main`:
  "Pint · PHPStan · Pest".

## Gotchas learned

- `TenantManager::runAs($t, fn () => Job::dispatch())` is wrong: the PendingDispatch queues *after* runAs restores.
  Use a block closure `function () { Job::dispatch(); }`.
- Pest dataset closures are resolved against the test's parameter list — tests using the dataset must accept both
  `(string $model, Closure $make)`.
- `artisan db:show` fails on MariaDB (performance_schema) — irrelevant now that Docker MySQL is used.

## Workflow

- **`main` is protected**: branch → push → PR → CI green → merge. Branch names like `week-2/registration`.
- Commit/push only when the user asks. GitHub: `mohammed-m-bakroon/saas-amanah` (public).
- The user communicates in English and sometimes Arabic; keep explanations simple and step-by-step,
  with exact commands to paste.

## Environment

- Windows 10 22H2 + WSL2 Ubuntu 26.04 (user `momostafa`), **Docker Engine inside Ubuntu** (no Docker Desktop).
  Laptop has 5.9 GB RAM; `C:\Users\HP-R5\.wslconfig` caps WSL at 3 GB.
- Work only in `~/code/saas-amanah` (this copy). An old Windows copy exists at `D:\xampp\htdocs\SaaS-Amanah`
  (XAMPP `.env`) — do not edit it; it's a backup.
- Run everything through Sail: `sail up -d`, `sail artisan ...`. XAMPP must be stopped (ports 80/3306).
- URLs: http://lvh.me (platform), http://sawt.lvh.me (tenant "Sawt", id 1, exists in the DB),
  http://localhost:8025 (Mailpit). DB from Windows: 127.0.0.1:3306, user `sail`, password `password`, db `amanah`.

## Open decisions (ask the user, don't assume)

- Final name & domain · prices of the 3 plans · migrate Sawt & Fajr data as first tenants? ·
  hosting: Laravel Cloud vs Forge + Hetzner (user has a Laravel Cloud account; deploy planned for Week 8 /
  once a domain is bought; choose an EU region, not US East).
- Stripe test account with Connect + Stripe CLI needed before Week 3; Sentry before Week 8.
