<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Actions;

use He4rt\Identity\ExternalIdentity\Events\ExternalIdentityDisconnected;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

final readonly class DisconnectExternalIdentity
{
    public function handle(ExternalIdentity $identity): void
    {
        $identity->update(['disconnected_at' => now()]);

        event(new ExternalIdentityDisconnected($identity));
    }
}
