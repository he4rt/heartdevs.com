<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Twitch;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class TwitchCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTv;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'twitch';

    protected static bool $shouldRegisterSubNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::twitch.navigation.cluster');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('panel-admin::twitch.navigation.cluster_breadcrumb');
    }
}
