<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class ModerationCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedShieldCheck;

    protected static ?int $navigationSort = 10;

    protected static ?string $navigationLabel = 'Moderação';

    protected static ?string $slug = 'mod';

    protected static ?string $clusterBreadcrumb = 'Moderação';

    protected static bool $shouldRegisterSubNavigation = false;

}
