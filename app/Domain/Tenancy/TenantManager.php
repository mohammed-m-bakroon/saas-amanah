<?php

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Exceptions\MissingTenantException;
use App\Domain\Tenancy\Models\Tenant;

/**
 * Holds the tenant for the current request or job. Registered as a singleton,
 * so everything in one request/job sees the same tenant.
 */
class TenantManager
{
    private ?Tenant $current = null;

    public function set(Tenant $tenant): void
    {
        $this->current = $tenant;
    }

    public function forget(): void
    {
        $this->current = null;
    }

    public function check(): bool
    {
        return $this->current !== null;
    }

    public function current(): ?Tenant
    {
        return $this->current;
    }

    public function id(): ?int
    {
        return $this->current?->id;
    }

    /**
     * @throws MissingTenantException
     */
    public function idOrFail(): int
    {
        return $this->id() ?? throw MissingTenantException::make();
    }

    /**
     * Run a callback as another tenant, then restore whatever was bound before.
     *
     * Careful with dispatching jobs: `fn () => Job::dispatch()` returns a PendingDispatch
     * that is only queued after runAs() has restored the old tenant. Use a block closure
     * (`function () { Job::dispatch(); }`) or dispatch_sync / Bus::dispatch inside.
     *
     * @template T
     *
     * @param  callable(): T  $callback
     * @return T
     */
    public function runAs(?Tenant $tenant, callable $callback): mixed
    {
        $previous = $this->current;
        $this->current = $tenant;

        try {
            return $callback();
        } finally {
            $this->current = $previous;
        }
    }
}
