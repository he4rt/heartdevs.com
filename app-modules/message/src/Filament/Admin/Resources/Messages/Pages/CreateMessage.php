<?php

declare(strict_types=1);

namespace He4rt\Message\Filament\Admin\Resources\Messages\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Message\Filament\Admin\Resources\Messages\MessageResource;

class CreateMessage extends CreateRecord
{
    protected static string $resource = MessageResource::class;
}
