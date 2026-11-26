<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

/**
 * Bind a tenant for the rest of the test (like ResolveTenant does for a request).
 */
function actingAsTenant(Tenant $tenant): Tenant
{
    app(TenantManager::class)->set($tenant);

    return $tenant;
}

/**
 * Run a callback as a tenant and restore the previous binding afterwards.
 *
 * @template T
 *
 * @param  callable(): T  $callback
 * @return T
 */
function asTenant(Tenant $tenant, callable $callback): mixed
{
    return app(TenantManager::class)->runAs($tenant, $callback);
}
