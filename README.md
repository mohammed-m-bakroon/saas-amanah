# Amanah

Multi-tenant SaaS for nonprofits: a charity signs up and within minutes gets a branded donation site and a dashboard.
Laravel 12 modular monolith, one database with `tenant_id` on every tenant table.

See [`PLAN.md`](PLAN.md) for the 8-week MVP plan.

## Local setup (Laravel Sail)

Requires Docker Desktop.

```bash
composer install
cp .env.example .env
./vendor/bin/sail up -d
./vendor/bin/sail artisan key:generate
./vendor/bin/sail artisan migrate
```

Tenants are served on subdomains of `TENANCY_CENTRAL_DOMAIN` (default `amanah.test`).
Add to `C:\Windows\System32\drivers\etc\hosts` (or `/etc/hosts`), one line per test tenant:

```
127.0.0.1 amanah.test
127.0.0.1 sawt.amanah.test
```

Mail is caught by Mailpit at http://localhost:8025.

## Quality checks (same as CI)

```bash
composer lint      # Pint
composer analyse   # PHPStan level 6
composer test      # Pest
```

## Tenancy, in one paragraph

`ResolveTenant` maps `{subdomain}.amanah.test` to a `Tenant` and binds it in `TenantManager`.
Models with a `tenant_id` column use `BelongsToTenant`: a global scope restricts every query to the current tenant
and **throws if no tenant is bound** (fail closed), `tenant_id` is filled on create, and writing to or moving a row
into another tenant throws. Queued jobs carry `tenant_id` in their payload and get the tenant re-bound before they run
(`QueueTenancy`), with no code in the job itself. Platform-level code reads across tenants only via the explicit
`Model::withoutTenancy()`.

Isolation is enforced by tests: `tests/Feature/Tenancy/IsolationTest.php` runs every check against each tenant model in
its dataset, and `EveryTenantTableIsScopedTest` fails CI if a model with a `tenant_id` column lacks the trait.
