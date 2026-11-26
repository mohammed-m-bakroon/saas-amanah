<?php

/*
| Guard for NFR-01: any model in app/ whose table has a tenant_id column
| must use BelongsToTenant. Forgetting the trait fails CI.
*/

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\Finder\Finder;

it('applies BelongsToTenant to every model with a tenant_id column', function () {
    $unscoped = [];

    foreach (Finder::create()->files()->in(app_path())->name('*.php') as $file) {
        $class = 'App\\'.str_replace(['/', '.php'], ['\\', ''], Str::after($file->getRealPath(), realpath(app_path()).DIRECTORY_SEPARATOR));
        $class = str_replace('/', '\\', $class);

        if (! class_exists($class) || ! is_subclass_of($class, Model::class) || (new ReflectionClass($class))->isAbstract()) {
            continue;
        }

        $table = (new $class)->getTable();

        if (Schema::hasColumn($table, 'tenant_id') && ! in_array(BelongsToTenant::class, class_uses_recursive($class), true)) {
            $unscoped[] = $class;
        }
    }

    expect($unscoped)->toBe([]);
});
