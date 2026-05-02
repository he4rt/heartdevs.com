<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources\ModerationAppealResource\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Moderation\Resources\ModerationAppealResource;

class ListModerationAppeals extends ListRecords
{
    protected static string $resource = ModerationAppealResource::class;

    protected ?string $heading = 'Appeals Queue';
}
