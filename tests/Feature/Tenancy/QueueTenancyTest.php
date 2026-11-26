<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantManager;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Tests\Fixtures\Note;
use Tests\Fixtures\RecordNoteJob;

beforeEach(function () {
    Note::createTable();
    config(['queue.default' => 'database']);
});

it('stores the tenant_id in the job payload', function () {
    $tenant = Tenant::factory()->create();

    asTenant($tenant, function () {
        RecordNoteJob::dispatch('hello');
    });

    $payload = json_decode((string) DB::table('jobs')->value('payload'), true);

    expect($payload['tenant_id'])->toBe($tenant->id);
});

it('re-binds the tenant when a worker runs the job', function () {
    $tenantA = Tenant::factory()->create();
    $tenantB = Tenant::factory()->create();

    asTenant($tenantA, function () {
        RecordNoteJob::dispatch('for A');
    });
    asTenant($tenantB, function () {
        RecordNoteJob::dispatch('for B');
    });

    // The worker starts with no tenant, like a real queue:work process.
    expect(app(TenantManager::class)->check())->toBeFalse();

    Artisan::call('queue:work', ['--once' => true, '--stop-when-empty' => true]);
    Artisan::call('queue:work', ['--once' => true, '--stop-when-empty' => true]);

    expect(Note::withoutTenancy()->where('body', 'for A')->value('tenant_id'))->toBe($tenantA->id)
        ->and(Note::withoutTenancy()->where('body', 'for B')->value('tenant_id'))->toBe($tenantB->id)
        ->and(app(TenantManager::class)->check())->toBeFalse();
});

it('restores the outer tenant after a sync job', function () {
    config(['queue.default' => 'sync']);
    $tenant = actingAsTenant(Tenant::factory()->create());

    RecordNoteJob::dispatch('sync');

    expect(app(TenantManager::class)->id())->toBe($tenant->id)
        ->and(Note::count())->toBe(1);
});
