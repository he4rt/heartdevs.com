<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Characters\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Filament\Resources\Characters\CharacterResource;

class EditCharacter extends EditRecord
{
    protected static string $resource = CharacterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
