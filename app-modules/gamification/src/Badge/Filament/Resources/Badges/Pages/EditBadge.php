<?php

declare(strict_types=1);

namespace He4rt\Gamification\Badge\Filament\Resources\Badges\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Gamification\Badge\Filament\Resources\Badges\BadgeResource;

class EditBadge extends EditRecord
{
    protected static string $resource = BadgeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
