<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Data;

use He4rt\Identity\Users\DTOs\BrowserSession;
use Spatie\LaravelData\Data;

final class BrowserSessionData extends Data
{
    public function __construct(
        public string $browser,
        public string $platform,
        public ?string $ipAddress,
        public string $lastActiveAt,
        public bool $isCurrent,
    ) {}

    public static function fromDto(BrowserSession $session): self
    {
        return new self(
            browser: $session->browser,
            platform: $session->platform,
            ipAddress: $session->ipAddress,
            lastActiveAt: $session->lastActiveAt,
            isCurrent: $session->isCurrent,
        );
    }
}
