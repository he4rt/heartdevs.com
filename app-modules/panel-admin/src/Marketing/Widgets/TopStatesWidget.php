<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Widgets;

use Filament\Widgets\Widget;
use He4rt\PanelAdmin\Marketing\Pages\Location\Queries\MembersByState;

class TopStatesWidget extends Widget
{
    protected static bool $isLazy = false;

    protected int|string|array $columnSpan = 2;

    protected string $view = 'panel-admin::marketing.widgets.top-states';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $members = new MembersByState()->get();

        return [
            'top' => $members->top,
            'total' => $members->total,
            'statesReached' => $members->statesReached,
        ];
    }
}
