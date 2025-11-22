<?php

declare(strict_types=1);

namespace He4rt\Season\Filament\Admin\Resources\Seasons\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Season\Filament\Admin\Resources\Seasons\SeasonResource;

class EditSeason extends EditRecord
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
