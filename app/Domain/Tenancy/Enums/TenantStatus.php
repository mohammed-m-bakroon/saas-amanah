<?php

namespace App\Domain\Tenancy\Enums;

enum TenantStatus: string
{
    /** Registered, email not verified yet (FR-02). */
    case Pending = 'pending';

    case Active = 'active';

    /** Payment failed past the grace period: data kept, writes blocked (FR-10). */
    case ReadOnly = 'read_only';

    /** Suspended by the Super Admin. */
    case Suspended = 'suspended';

    public function canBeVisited(): bool
    {
        return $this !== self::Suspended;
    }
}
