<?php

namespace App\Domain\Tenancy\Exceptions;

use LogicException;

/**
 * Thrown when tenant-scoped data is touched with no tenant bound.
 * Failing loudly is the point: a silent unscoped query is a data leak.
 */
class MissingTenantException extends LogicException
{
    public static function make(): self
    {
        return new self('No tenant is bound. Tenant-scoped models require a resolved tenant (use TenantManager::runAs() or withoutTenancy() for platform-level access).');
    }

    public static function tenantMismatch(): self
    {
        return new self('A tenant-scoped model cannot be written with, or moved to, a tenant_id other than the current tenant.');
    }
}
