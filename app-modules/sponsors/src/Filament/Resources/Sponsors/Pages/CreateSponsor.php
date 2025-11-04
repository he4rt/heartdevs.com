<?php

declare(strict_types=1);

namespace He4rt\Sponsors\Filament\Resources\Sponsors\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Sponsors\Filament\Resources\Sponsors\SponsorResource;

class CreateSponsor extends CreateRecord
{
    protected static string $resource = SponsorResource::class;
}
