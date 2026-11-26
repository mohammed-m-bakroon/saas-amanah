# Amanah — MVP Implementation Plan

Source: *منصة SaaS للجمعيات: SRS والمعمارية وخطة التنفيذ* (Nov 26, 2026).
Scope: 24 FRs + 10 NFRs, 1 developer, 8 weeks, hosting < $20/month.
Rule: **a phase starts only when the previous phase's tests are green in CI.**
Scope is **frozen at the end of Week 1** — new ideas go to the "Later" list at the bottom, never into the build.

---

## Week 0 — Pre-flight (fix before writing code)

Found while checking this machine:

- [ ] **Git root is wrong.** `D:\xampp\htdocs` itself is a git repo (branch `mostafaBranch`, no commits), so every project in htdocs shows as untracked. Run `git init` *inside* `SaaS-Amanah` so it gets its own repo (and consider removing the stray `htdocs\.git` if it was created by accident).
- [ ] **Docker is not installed** → Sail won't run. Install Docker Desktop (WSL2 backend). Fallback: run on XAMPP + local Redis/Mailpit, but the SRS assumes Sail.
- [ ] **PHP is 8.2.12**; SRS targets 8.3. Sail containers give you 8.3 regardless; local CLI is only used for `composer create-project`.
- [ ] **`gh` CLI not installed** — install it or create the GitHub repo in the browser.
- [ ] Create Stripe account (test mode), enable **Connect** (Express accounts), install **Stripe CLI**.
- [ ] Create Sentry project (free tier).

---

## Target structure (modular monolith)

```
app/
  Domain/
    Tenancy/        Tenant model, TenantResolver middleware, BelongsToTenant trait, TenantAware jobs
    Accounts/       Registration, onboarding wizard, invitations, roles (FR-01..05)
    Billing/        Cashier, plans, limits, grace period (FR-06..10)
    Campaigns/      Campaign model, states, progress (FR-11..13)
    Donations/      PaymentGateway interface, StripeGateway, Checkout, recurring, receipts (FR-14..19)
    Donors/         Donor list, CSV export (FR-20..21)
    Reporting/      Dashboard summaries, activity log (FR-22..23)
    Platform/       Super Admin panel (FR-24)
  Http/Controllers/Webhooks/StripeWebhookController.php
tests/
  Feature/Tenancy/IsolationTest.php   ← one dataset entry per tenant-scoped model
```

Stack: Laravel 11, Livewire 3 + Alpine, MySQL 8, Redis, Horizon, Cashier (Stripe), spatie/laravel-permission (teams = tenant_id), spatie/laravel-translatable, Pest, PHPStan (Larastan) level 6, Pint, Sentry.
Tenancy: **hand-written** (no stancl/tenancy) — stronger interview story.
Money: always integer minor units (halalas/cents).

---

## Week 1 — Foundation & tenant isolation

**Deliverables**
- [ ] `composer create-project laravel/laravel:^11 .` → `php artisan sail:install --with=mysql,redis,mailpit`
- [ ] Push to GitHub; add `.github/workflows/ci.yml`: Pint (`--test`), PHPStan L6, Pest `--coverage --min=80` on every PR
- [ ] Branch protection on `main` (CI must pass)
- [ ] Migration `tenants` (name, subdomain unique, stripe_customer_id, stripe_account_id, plan, status, trial_ends_at, settings json)
- [ ] **Write `IsolationTest` first** (red), then:
- [ ] `TenantResolver` middleware: subdomain → `Tenant` → bind `app('tenant')`; 404 on unknown; central domain routes separate
- [ ] `BelongsToTenant` trait: global scope `where tenant_id = current`, `creating` hook fills `tenant_id`, throws if no tenant bound
- [ ] `TenantAware` job trait/middleware: serializes `tenant_id`, re-binds tenant before `handle()`
- [ ] Local subdomains: `*.amanah.test` (hosts entries or `lvh.me`)

**Acceptance:** Tenant A cannot read/update/count any record of Tenant B (model, query, job); CI green.

## Week 2 — Accounts & onboarding (FR-01 → FR-05)

- [ ] `users`, `tenant_user (tenant_id, user_id, role)` migrations
- [ ] FR-01 Register charity: name, email, subdomain (validate reserved words: www, app, admin, api)
- [ ] FR-02 Email verification gates tenant activation (`status = pending → active`)
- [ ] FR-03 Onboarding wizard (Livewire, multi-step): logo upload, brand colors, currency, default locale
- [ ] FR-04 Team invitations by email (signed URL, expiry) with role: Owner / Admin / Accountant
- [ ] spatie/permission in teams mode keyed by `tenant_id`; Policies per resource
- [ ] FR-05 *(Should)* tenant switcher for users in multiple tenants
- [ ] AR/EN + RTL layout base (`dir` from locale), translation files from day one (NFR-07)

**Acceptance:** new charity reaches its dashboard on its subdomain in < 5 min (Pest browser/feature test of the full flow).

## Weeks 3–4 — Subscriptions (FR-06 → FR-10) — hardest part

- [ ] Install Cashier; `Tenant` is the Billable model
- [ ] FR-06 Plans in config: Starter / Pro / Enterprise, monthly + yearly Stripe prices (prices = open decision)
- [ ] FR-07 14-day trial without card (`trial_ends_at` on tenant, generic trial)
- [ ] FR-08 Upgrade / downgrade / cancel via Stripe Customer Portal
- [ ] FR-09 Plan limits (active campaigns, team members) — single `PlanLimits` service + middleware/policy checks
- [ ] FR-10 `invoice.payment_failed` → `past_due` + notification emails; after 7 days → **read-only mode** (middleware blocks writes, data kept); `invoice.paid` restores
- [ ] Scheduled command: daily check of grace periods / expired trials
- [ ] Tests driven by Stripe CLI fixtures (`stripe trigger ...`) + faked webhook payloads

**Acceptance:** tests cover upgrade, cancel, trial expiry and payment failure → read-only → recovery.

## Week 5 — Campaigns (FR-11 → FR-13)

- [ ] `campaigns` (tenant_id, title/description json, image, goal_amount, raised_amount, status, ends_at), index `(tenant_id, status)`
- [ ] FR-11 CRUD bilingual (spatie/translatable), image upload
- [ ] FR-12 State machine: draft → published → completed → archived (enum + guarded transitions)
- [ ] FR-13 Progress bar from stored `raised_amount` (no `SUM()`); live update via Livewire polling (or Reverb later)
- [ ] Public charity page listing published campaigns with tenant branding
- [ ] Plan limit enforced on publishing (FR-09)

**Acceptance:** published campaign shows on public page; drafts never do.

## Week 6 — Donations & payments (FR-14 → FR-19)

- [ ] `PaymentGateway` interface + `StripeGateway` (leaves room for Tap/HyperPay)
- [ ] FR-19 Stripe Connect Express onboarding for each charity → `stripe_account_id`
- [ ] FR-14 Public donation page per campaign, tenant-branded, Lighthouse a11y > 90 (NFR-10)
- [ ] FR-15 One-time donation: Checkout session on the connected account, metadata `tenant_id`, `campaign_id`
- [ ] FR-16 Monthly donation: Checkout `mode=subscription` → `recurring_donations`; cancel link (signed URL) in email
- [ ] `webhook_events (stripe_event_id unique, type, payload, processed_at)`
- [ ] `/webhooks/stripe` (+ Connect endpoint): verify signature → insert event (ignore duplicate) → return 200 → dispatch job
- [ ] `ProcessDonation` job: create donor/donation + increment `raised_amount` in one DB transaction
- [ ] FR-17 Chained jobs: `GenerateReceiptPdf` (per-tenant sequential number, row-locked counter) → `SendReceiptEmail` (AR/EN)
- [ ] FR-18 *(Should)* anonymous donation
- [ ] Rate limiting on donation endpoints (NFR-04)

**Acceptance:** sending the same webhook twice creates exactly one donation and one receipt (NFR-03).

## Week 7 — Donors, reports, admin (FR-20 → FR-24)

- [ ] FR-20 Donors list: total given + last donation, unique `(tenant_id, email)`, cursor pagination, index `(tenant_id, created_at)`
- [ ] FR-21 CSV export by date range as queued job using `lazy()`; email/download link when done
- [ ] FR-22 Dashboard (month total, donor count, active recurring) read from a `tenant_daily_summaries` table updated by job
- [ ] FR-23 *(Should)* Activity log on key actions
- [ ] FR-24 Super Admin panel (central domain): tenants, suspend tenant, MRR, past-due subscriptions

**Acceptance:** dashboard numbers equal the raw sums in the DB (test asserts both).

## Week 8 — Hardening & launch (NFR-01 → NFR-10)

- [ ] Seeder: 500k fake donations across tenants
- [ ] k6 on donation page, donations list, dashboard → target p95 < 300 ms, 200 req/s (NFR-02); record real numbers
- [ ] Security pass: OWASP Top 10 checklist, rate limits on login, CSRF, no card data stored (NFR-04)
- [ ] Sentry + Horizon + UptimeRobot (NFR-05, NFR-09)
- [ ] Daily DB backup, 7-day retention (NFR-06)
- [ ] CD: merge to `main` → deploy (Forge + Hetzner or Laravel Cloud)
- [ ] Demo tenant with seeded data + demo login
- [ ] README with architecture diagram; 2-minute demo video
- [ ] Fill in the CV line with measured numbers

**Acceptance:** all NFRs verified; public link works for any visitor.

---

## NFR → where it's enforced

| NFR | Enforced by |
|---|---|
| 01 Isolation | `BelongsToTenant` + `IsolationTest` per model in CI |
| 02 Performance | composite indexes, cursor pagination, stored aggregates, k6 in Week 8 |
| 03 Payment reliability | signed webhooks, `webhook_events` unique key, queued processing |
| 04 Security | Policies, rate limiting, CSRF, Stripe-hosted Checkout |
| 05 Availability | UptimeRobot |
| 06 Backups | daily dump, 7-day retention |
| 07 Languages | AR/EN from Week 2 in screens, emails, receipts |
| 08 Code quality | CI: Pint, PHPStan L6, coverage ≥ 80% |
| 09 Monitoring | Sentry, Horizon |
| 10 Accessibility | Lighthouse ≥ 90 on donation page |

## Risks

| Risk | Mitigation |
|---|---|
| Cross-tenant data leak | Mandatory global scope + isolation test per model |
| Stripe Connect unavailable in your country | Test mode for demo; `PaymentGateway` interface for Tap/HyperPay |
| Scope creep | Freeze after Week 1; "Later" list only |
| Duplicate / out-of-order webhooks | Unique `stripe_event_id`, queued idempotent handlers |

## Open decisions

- [ ] Final name & domain (Amanah is temporary)
- [ ] Prices for the 3 plans
- [ ] Migrate Sawt & Fajr data as the first two tenants?
- [ ] Hosting: Forge + Hetzner vs Laravel Cloud

## Later (not in MVP)

Custom domains · in-kind campaigns / auctions · Tap, HyperPay, bank transfer · donor portal · usage-based billing · RAG donor assistant · extra languages · DB-per-tenant for Enterprise.
