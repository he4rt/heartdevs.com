<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource;

class ListModerationCases extends ListRecords
{
    protected static string $resource = ModerationCaseResource::class;

    protected ?string $heading = 'Moderation Queue';
}
