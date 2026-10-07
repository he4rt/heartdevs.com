<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Discord;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Concerns\SuperAdminOnly;

class DiscordCluster extends Cluster
{
    use SuperAdminOnly;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChatBubbleLeftRight;

    protected static ?int $navigationSort = 40;

    protected static ?string $slug = 'discord';

    protected static bool $shouldRegisterSubNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::discord.navigation.cluster');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('panel-admin::discord.navigation.cluster_breadcrumb');
    }
}
