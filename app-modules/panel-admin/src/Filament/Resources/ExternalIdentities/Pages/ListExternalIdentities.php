<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages;

use Filament\Resources\Pages\ListRecords;
use Filament\Support\Enums\Width;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\ExternalIdentityResource;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Widgets\ExternalIdentityStatsOverview;

class ListExternalIdentities extends ListRecords
{
    protected static string $resource = ExternalIdentityResource::class;

    protected Width|string|null $maxContentWidth = Width::ScreenTwoExtraLarge;

    protected function getHeaderWidgets(): array
    {
        return [
            ExternalIdentityStatsOverview::class,
        ];
    }
}
