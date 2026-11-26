<?php

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class TenantScope implements Scope
{
    /**
     * @param  Builder<Model>  $builder
     */
    public function apply(Builder $builder, Model $model): void
    {
        // Fail closed: no tenant bound means an exception, never an unscoped query.
        $builder->where(
            $model->qualifyColumn('tenant_id'),
            app(TenantManager::class)->idOrFail(),
        );
    }
}
