<?php

declare(strict_types=1);

namespace He4rt\Profile\Listeners;

use He4rt\Profile\Models\Profile;

final class CreateProfileForTenantMember
{
    public function handle(string $userId, int $tenantId): void
    {
        Profile::query()->firstOrCreate([
            'user_id' => $userId,
            'tenant_id' => $tenantId,
        ], [
            'available_for_proposals' => false,
        ]);
    }
}
