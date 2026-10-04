<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Pages\Enums\SubNavigationPosition;
use Filament\Support\Icons\Heroicon;

class StreamingCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedVideoCamera;

    protected static ?string $navigationLabel = 'Minha Live';

    protected static ?string $clusterBreadcrumb = 'Minha Live';

    protected static ?string $slug = 'minha-live';

    protected static ?int $navigationSort = 4;

    protected static ?SubNavigationPosition $subNavigationPosition = SubNavigationPosition::Top;

    public static function getNavigationBadge(): ?string
    {
        return StreamingHealthBadge::label();
    }

    public static function getNavigationBadgeColor(): string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return StreamingHealthBadge::TOOLTIP;
    }
}
