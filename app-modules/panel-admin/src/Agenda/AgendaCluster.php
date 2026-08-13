<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Agenda;

use BackedEnum;
use Filament\Clusters\Cluster;
use Filament\Support\Icons\Heroicon;

class AgendaCluster extends Cluster
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    protected static ?int $navigationSort = 30;

    protected static ?string $slug = 'agenda';

    protected static bool $shouldRegisterSubNavigation = false;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::agenda.navigation.cluster');
    }

    public static function getClusterBreadcrumb(): ?string
    {
        return __('panel-admin::agenda.navigation.cluster_breadcrumb');
    }
}
