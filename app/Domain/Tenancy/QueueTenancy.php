<?php

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Queue\Events\JobExceptionOccurred;
use Illuminate\Queue\Events\JobProcessed;
use Illuminate\Queue\Events\JobProcessing;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Queue;

/**
 * Makes every queued job (jobs, mail, notifications, listeners) tenant-aware
 * without any per-job code:
 *
 * - on dispatch, the current tenant_id is written into the job payload;
 * - on processing, that tenant is re-bound before handle() runs;
 * - afterwards, the previously bound tenant is restored (matters for the sync driver).
 */
class QueueTenancy
{
    /** @var list<Tenant|null> */
    private array $stack = [];

    public function __construct(private readonly TenantManager $tenants) {}

    public function register(): void
    {
        Queue::createPayloadUsing(fn (): array => [
            'tenant_id' => $this->tenants->id(),
        ]);

        Event::listen(JobProcessing::class, function (JobProcessing $event): void {
            $this->stack[] = $this->tenants->current();

            $tenantId = $event->job->payload()['tenant_id'] ?? null;

            if ($tenantId === null) {
                $this->tenants->forget();

                return;
            }

            // A deleted tenant means the job must not run against someone else's data.
            $this->tenants->set(Tenant::query()->findOrFail($tenantId));
        });

        $restore = function (): void {
            $previous = array_pop($this->stack);
            $previous === null ? $this->tenants->forget() : $this->tenants->set($previous);
        };

        Event::listen(JobProcessed::class, $restore);
        Event::listen(JobExceptionOccurred::class, $restore);
    }
}
