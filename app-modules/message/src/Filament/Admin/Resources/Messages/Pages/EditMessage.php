<?php

declare(strict_types=1);

namespace He4rt\Message\Filament\Admin\Resources\Messages\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Message\Filament\Admin\Resources\Messages\MessageResource;

class EditMessage extends EditRecord
{
    protected static string $resource = MessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
