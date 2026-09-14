<?php

declare(strict_types=1);

namespace He4rt\Events\CheckIn\Events;

use He4rt\Events\CheckIn\Enums\BotCheckInStatus;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class CheckInProcessed
{
    use Dispatchable;

    /**
     * @param  array<string, mixed>  $channelContext  Echoed back from the request for reply routing.
     */
    public function __construct(
        public IdentityProvider $provider,
        public string $externalUserId,
        public BotCheckInStatus $status,
        public string $message,
        public array $channelContext = [],
        public ?string $enrollmentId = null,
    ) {}
}
