<?php

declare(strict_types=1);

namespace He4rt\Identity\Tenant\Observers;

use He4rt\Identity\Tenant\Models\TenantUser;

final class TenantUserObserver
{
    public function created(TenantUser $pivot): void
    {
        // Profile bootstrapping moved to UserObserver::created as part of removing
        // multi-tenancy. This observer is retained until the Tenant concept is
        // fully deleted in a later phase.
    }
}
