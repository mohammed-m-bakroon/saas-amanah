<?php

namespace App\Domain\Tenancy\Concerns;

use App\Domain\Tenancy\Exceptions\MissingTenantException;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Tenancy\TenantManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Add to every model whose table has a tenant_id column.
 *
 * - Reads, updates and deletes are scoped to the current tenant (global scope).
 * - tenant_id is filled automatically on create.
 * - Writing a row for another tenant, or moving a row between tenants, throws.
 *
 * @mixin Model
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function (Model $model): void {
            $currentId = app(TenantManager::class)->idOrFail();

            if ($model->getAttribute('tenant_id') === null) {
                $model->setAttribute('tenant_id', $currentId);
            } elseif ((int) $model->getAttribute('tenant_id') !== $currentId) {
                throw MissingTenantException::tenantMismatch();
            }
        });

        static::updating(function (Model $model): void {
            if ($model->isDirty('tenant_id')) {
                throw MissingTenantException::tenantMismatch();
            }
        });
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Explicit, greppable escape hatch for platform-level code (Super Admin, reports).
     *
     * @return Builder<static>
     */
    public static function withoutTenancy(): Builder
    {
        return static::query()->withoutGlobalScope(TenantScope::class);
    }
}
