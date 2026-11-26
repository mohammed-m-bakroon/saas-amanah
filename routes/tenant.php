<?php

use App\Domain\Tenancy\TenantManager;
use Illuminate\Support\Facades\Route;

/*
| Routes served on {subdomain}.{central_domain}. Every route here runs behind
| ResolveTenant, so a tenant is always bound.
*/

Route::get('/', function (TenantManager $tenants) {
    return view('tenant.home', ['tenant' => $tenants->current()]);
})->name('tenant.home');
