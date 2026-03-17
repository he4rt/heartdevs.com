<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Resources\Sponsors\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Events\Filament\Resources\Sponsors\SponsorResource;

class EditSponsor extends EditRecord
{
    protected static string $resource = SponsorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
