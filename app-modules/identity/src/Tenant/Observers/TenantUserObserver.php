<?php

declare(strict_types=1);

namespace He4rt\Identity\Tenant\Observers;

use He4rt\Identity\Tenant\Models\TenantUser;
use He4rt\Profile\Models\Profile;

final class TenantUserObserver
{
    public function created(TenantUser $pivot): void
    {
        Profile::query()->firstOrCreate([
            'user_id' => $pivot->user_id,
            'tenant_id' => $pivot->tenant_id,
        ], [
            'available_for_proposals' => false,
        ]);
    }
}
