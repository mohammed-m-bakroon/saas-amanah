<?php

namespace App\Http\Middleware;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Every tenant request passes here first: {subdomain}.{central_domain} → Tenant.
 * Nothing behind this middleware runs without a bound tenant.
 */
class ResolveTenant
{
    public function __construct(private readonly TenantManager $tenants) {}

    public function handle(Request $request, Closure $next): Response
    {
        $subdomain = $this->subdomainFrom($request->getHost());

        $tenant = $subdomain === null
            ? null
            : Tenant::query()->where('subdomain', $subdomain)->first();

        abort_if($tenant === null, 404);
        abort_unless($tenant->status->canBeVisited(), 403);

        $this->tenants->set($tenant);

        // Controllers get the tenant from TenantManager, not from a route parameter.
        $request->route()?->forgetParameter('subdomain');

        try {
            return $next($request);
        } finally {
            $this->tenants->forget();
        }
    }

    private function subdomainFrom(string $host): ?string
    {
        $suffix = '.'.strtolower((string) config('tenancy.central_domain'));
        $host = strtolower($host);

        if (! str_ends_with($host, $suffix)) {
            return null;
        }

        $subdomain = substr($host, 0, -strlen($suffix));

        // One label only: "a.b.amanah.test" is not a tenant.
        return $subdomain !== '' && ! str_contains($subdomain, '.') ? $subdomain : null;
    }
}
