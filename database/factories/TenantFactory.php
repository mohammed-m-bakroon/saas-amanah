<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'name' => fake()->company(),
            'subdomain' => fake()->unique()->lexify('charity-??????'),
            'status' => TenantStatus::Active,
            'settings' => ['locale' => 'ar', 'currency' => 'SAR'],
        ];
    }

    public function pending(): static
    {
        return $this->state(['status' => TenantStatus::Pending]);
    }

    public function suspended(): static
    {
        return $this->state(['status' => TenantStatus::Suspended]);
    }
}
