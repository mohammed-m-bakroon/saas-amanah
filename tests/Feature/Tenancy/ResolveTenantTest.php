<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantManager;

it('resolves the tenant from its subdomain', function () {
    $tenant = Tenant::factory()->create(['name' => 'Sawt Charity', 'subdomain' => 'sawt']);

    $this->get('http://sawt.amanah.test/')
        ->assertOk()
        ->assertSee('Sawt Charity');

    // Unbound again after the request.
    expect(app(TenantManager::class)->check())->toBeFalse();
});

it('returns 404 for an unknown subdomain', function () {
    $this->get('http://nobody.amanah.test/')->assertNotFound();
});

it('returns 403 for a suspended tenant', function () {
    Tenant::factory()->suspended()->create(['subdomain' => 'closed']);

    $this->get('http://closed.amanah.test/')->assertForbidden();
});

it('does not treat nested subdomains as tenants', function () {
    Tenant::factory()->create(['subdomain' => 'sawt']);

    $this->get('http://x.sawt.amanah.test/')->assertNotFound();
});

it('serves the platform on the central domain without a tenant', function () {
    $this->get('http://amanah.test/')->assertOk();
});
