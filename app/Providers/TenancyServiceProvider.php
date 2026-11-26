<?php

namespace App\Providers;

use App\Domain\Tenancy\QueueTenancy;
use App\Domain\Tenancy\TenantManager;
use Illuminate\Support\ServiceProvider;

class TenancyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(TenantManager::class);
        $this->app->singleton(QueueTenancy::class);
    }

    public function boot(): void
    {
        $this->app->make(QueueTenancy::class)->register();
    }
}
