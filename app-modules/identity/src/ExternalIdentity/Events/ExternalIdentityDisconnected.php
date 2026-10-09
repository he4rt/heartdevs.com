<?php

declare(strict_types=1);

namespace He4rt\Identity\ExternalIdentity\Events;

use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Dispatched when a user or an admin ends the connection of an external identity.
 * Consumers release what they hold for this identity, such as webhook subscriptions.
 */
final class ExternalIdentityDisconnected
{
    use Dispatchable;

    public function __construct(
        public ExternalIdentity $identity,
    ) {}
}
