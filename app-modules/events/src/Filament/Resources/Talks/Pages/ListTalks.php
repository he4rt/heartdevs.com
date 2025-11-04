<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Talks\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Events\Filament\Resources\Talks\TalkResource;

class ListTalks extends ListRecords
{
    protected static string $resource = TalkResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
