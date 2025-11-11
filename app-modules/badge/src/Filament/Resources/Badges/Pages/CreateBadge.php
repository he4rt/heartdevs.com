<?php

declare(strict_types=1);

namespace He4rt\Badge\Filament\Resources\Badges\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Badge\Filament\Resources\Badges\BadgeResource;

class CreateBadge extends CreateRecord
{
    protected static string $resource = BadgeResource::class;
}
