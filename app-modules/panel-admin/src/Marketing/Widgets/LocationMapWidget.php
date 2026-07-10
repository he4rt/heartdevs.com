<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Widgets;

use Filament\Widgets\Widget;
use He4rt\PanelAdmin\Marketing\Pages\Location\Queries\MembersByState;
use He4rt\PanelAdmin\Marketing\Support\BrazilStatesGeometry;

class LocationMapWidget extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 3;

    protected string $view = 'panel-admin::marketing.widgets.location-map';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $members = new MembersByState()->get();

        return [
            'byName' => $members['by_name'],
            'total' => $members['total'],
            'geometry' => BrazilStatesGeometry::get(),
        ];
    }
}
