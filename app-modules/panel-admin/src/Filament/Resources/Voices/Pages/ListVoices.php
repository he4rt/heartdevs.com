<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Voices\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Voices\VoiceResource;

class ListVoices extends ListRecords
{
    protected static string $resource = VoiceResource::class;
}
