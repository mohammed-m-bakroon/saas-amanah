<?php

namespace Tests\Fixtures;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test-only tenant-scoped model. Lets the isolation suite run in Week 1,
 * before the first real tenant model (campaigns) exists.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $body
 */
class Note extends Model
{
    use BelongsToTenant;

    protected $table = 'test_notes';

    protected $fillable = ['body', 'tenant_id'];

    public static function createTable(): void
    {
        Schema::create('test_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('body');
            $table->timestamps();
        });
    }
}
