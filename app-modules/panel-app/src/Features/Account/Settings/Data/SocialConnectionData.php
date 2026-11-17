<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Features\Account\Settings\Data;

use App\Data\Support\EnumOptionData;
use Spatie\LaravelData\Data;

/**
 * One provider row on the security page: connected (with its account) or ready to connect.
 */
final class SocialConnectionData extends Data
{
    public function __construct(
        public EnumOptionData $provider,
        public ?string $accountId,
        public ?string $connectedAt,
        public bool $canDisconnect,
    ) {}
}
