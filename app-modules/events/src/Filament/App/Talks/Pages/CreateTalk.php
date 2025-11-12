<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\App\Talks\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Events\Filament\App\Talks\TalkResource;

class CreateTalk extends CreateRecord
{
    protected static string $resource = TalkResource::class;
}
