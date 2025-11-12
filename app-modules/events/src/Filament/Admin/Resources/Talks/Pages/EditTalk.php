<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Talks\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Events\Filament\Admin\Resources\Talks\TalkResource;

class EditTalk extends EditRecord
{
    protected static string $resource = TalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
