<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Shared\Data;

use App\Data\Navigation\NavigationGroupData;
use Spatie\LaravelData\Data;

/**
 * What every page of the panel knows about the signed-in user.
 */
final class AccountData extends Data
{
    /**
     * @param  array<int, NavigationGroupData>  $navigation
     * @param  string|null  $impersonatorName  the super admin who signed in as this user
     * @param  bool  $canAccessAdmin  shows the link to the admin panel; the admin's own middleware still decides
     */
    public function __construct(
        public AppUserData $user,
        public array $navigation,
        public ?string $impersonatorName = null,
        public bool $canAccessAdmin = false,
    ) {}
}
