<?php

/*
| NFR-01: no tenant ever sees another tenant's data.
|
| Every tenant-scoped model gets an entry in the `tenantModels` dataset below.
| Add the model here in the same PR that creates it.
*/

use App\Domain\Tenancy\Exceptions\MissingTenantException;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Tests\Fixtures\Note;

dataset('tenantModels', [
    'Note (fixture)' => [
        Note::class,
        fn (): Model => Note::create(['body' => fake()->sentence()]),
    ],
    // 'Campaign' => [Campaign::class, fn () => Campaign::factory()->create()],   ← Week 5
]);

beforeEach(function () {
    Note::createTable();

    $this->tenantA = Tenant::factory()->create(['subdomain' => 'alpha']);
    $this->tenantB = Tenant::factory()->create(['subdomain' => 'beta']);
});

it('fills tenant_id automatically on create', function (string $model, Closure $make) {
    $record = asTenant($this->tenantA, $make);

    expect($record->tenant_id)->toBe($this->tenantA->id);
})->with('tenantModels');

it('never returns another tenant\'s rows', function (string $model, Closure $make) {
    $bRecord = asTenant($this->tenantB, $make);
    asTenant($this->tenantA, $make);

    asTenant($this->tenantA, function () use ($model, $bRecord) {
        expect($model::count())->toBe(1)
            ->and($model::find($bRecord->getKey()))->toBeNull()
            ->and($model::whereKey($bRecord->getKey())->exists())->toBeFalse()
            ->and($model::all()->pluck('tenant_id')->unique()->all())->toBe([$this->tenantA->id]);
    });
})->with('tenantModels');

it('cannot update or delete another tenant\'s rows through a query', function (string $model, Closure $make) {
    $bRecord = asTenant($this->tenantB, $make);

    asTenant($this->tenantA, function () use ($model, $bRecord) {
        expect($model::whereKey($bRecord->getKey())->update(['updated_at' => now()]))->toBe(0)
            ->and($model::whereKey($bRecord->getKey())->delete())->toBe(0);
    });

    expect($model::withoutTenancy()->whereKey($bRecord->getKey())->exists())->toBeTrue();
})->with('tenantModels');

it('refuses to create a row for another tenant', function (string $model, Closure $make) {
    asTenant($this->tenantA, fn () => $model::create([
        'tenant_id' => $this->tenantB->id,
        'body' => 'smuggled',
    ]));
})->with('tenantModels')->throws(MissingTenantException::class);

it('refuses to move a row to another tenant', function (string $model, Closure $make) {
    asTenant($this->tenantA, function () use ($make) {
        $record = $make();
        $record->tenant_id = $this->tenantB->id;
        $record->save();
    });
})->with('tenantModels')->throws(MissingTenantException::class);

it('fails closed when no tenant is bound', function (string $model, Closure $make) {
    $model::count();
})->with('tenantModels')->throws(MissingTenantException::class);

it('lets platform code read across tenants only through withoutTenancy()', function (string $model, Closure $make) {
    asTenant($this->tenantA, $make);
    asTenant($this->tenantB, $make);

    expect($model::withoutTenancy()->count())->toBe(2);
})->with('tenantModels');
