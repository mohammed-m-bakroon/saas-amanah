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

Mail is caught by Mailpit at http://localhost:8025.

### Without Docker (XAMPP)

Start MySQL in XAMPP, create an `amanah` database, then in `.env` set `DB_HOST=127.0.0.1`, `DB_USERNAME=root`,
`DB_PASSWORD=`, `QUEUE_CONNECTION=database`, `CACHE_STORE=database`, `MAIL_MAILER=log` and
`APP_URL=http://lvh.me:8000`. Then:

```bash
php artisan migrate
php artisan serve
```

## Local domains

Tenants are served on subdomains of `TENANCY_CENTRAL_DOMAIN`. Locally that is `lvh.me`, a public domain whose
every subdomain resolves to `127.0.0.1`, so a new tenant works immediately with no hosts-file edits:

| URL | Serves |
|---|---|
| http://lvh.me | Platform (marketing, registration, Super Admin) |
| http://sawt.lvh.me | The tenant with subdomain `sawt` |

Add `:8000` when using `php artisan serve`. `SESSION_DOMAIN=.lvh.me` keeps the login cookie valid across subdomains.
`lvh.me` needs an internet connection for DNS; `localtest.me` works the same way if it is ever down.

Create a tenant to try it:

```bash
php artisan tinker --execute="App\Domain\Tenancy\Models\Tenant::factory()->create(['subdomain'=>'sawt','name'=>'Sawt'])"
```

In production the same setting is the real domain (`TENANCY_CENTRAL_DOMAIN=amanah.app`) with one wildcard DNS
record `*.amanah.app` pointing at the server. Tests use `amanah.test` from `phpunit.xml` and need no DNS.

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
