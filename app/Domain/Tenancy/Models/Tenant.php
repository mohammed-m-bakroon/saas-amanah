<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Enums\TenantStatus;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name
 * @property string $subdomain
 * @property TenantStatus $status
 * @property string|null $plan
 * @property string|null $stripe_customer_id
 * @property string|null $stripe_account_id
 * @property Carbon|null $trial_ends_at
 * @property array<string, mixed>|null $settings
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'subdomain',
        'status',
        'plan',
        'trial_ends_at',
        'settings',
    ];

    protected $attributes = [
        'status' => 'pending',
    ];

    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'trial_ends_at' => 'datetime',
            'settings' => 'array',
        ];
    }

    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    public function url(string $path = '/'): string
    {
        $scheme = parse_url((string) config('app.url'), PHP_URL_SCHEME) ?: 'http';

        return $scheme.'://'.$this->subdomain.'.'.config('tenancy.central_domain').'/'.ltrim($path, '/');
    }
}
