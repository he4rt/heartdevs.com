<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Events;

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CheckInRequested
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $channelContext  Opaque routing data the bot uses to reply to the member.
     */
    public function __construct(
        public IdentityProvider $provider,
        public string $externalUserId,
        public string $code,
        public ?string $eventId = null,
        public array $channelContext = [],
    ) {}
}
