<?php

declare(strict_types=1);

namespace He4rt\Gamification\Badge\Filament\Resources\Badges\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Gamification\Badge\Filament\Resources\Badges\BadgeResource;

class ListBadges extends ListRecords
{
    protected static string $resource = BadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
