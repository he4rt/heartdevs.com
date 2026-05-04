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

    protected static ?string $slug = 'mod';

    protected static bool $shouldRegisterSubNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.cluster');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('panel-admin::moderation.navigation.cluster_breadcrumb');
    }
}
