<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Shared\Data;

use App\Data\Navigation\NavigationGroupData;
use Spatie\LaravelData\Data;

final class AdminSharedPropsData extends Data
{
    /**
     * @param  array<int, NavigationGroupData>  $navigation
     */
    public function __construct(
        public AdminUserData $user,
        public array $navigation,
    ) {}
}
