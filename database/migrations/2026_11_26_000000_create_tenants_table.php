<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The only table without a tenant_id column.
     */
    public function up(): void
    {
        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('subdomain', 63)->unique();
            $table->string('status', 20)->default('pending')->index();
            $table->string('plan', 30)->nullable();
            $table->string('stripe_customer_id')->nullable()->index();
            $table->string('stripe_account_id')->nullable()->index();
            $table->timestamp('trial_ends_at')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tenants');
    }
};
