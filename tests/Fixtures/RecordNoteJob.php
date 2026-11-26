<?php

namespace Tests\Fixtures;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * A job with no tenancy code of its own: QueueTenancy must re-bind the tenant.
 */
class RecordNoteJob implements ShouldQueue
{
    use Queueable;

    public function __construct(public string $body) {}

    public function handle(): void
    {
        Note::create(['body' => $this->body]);
    }
}
