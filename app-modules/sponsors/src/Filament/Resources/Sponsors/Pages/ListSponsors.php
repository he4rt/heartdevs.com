<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Filament\Resources\Sponsors\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Sponsors\Filament\Resources\Sponsors\SponsorResource;

class ListSponsors extends ListRecords
{
    protected static string $resource = SponsorResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
