<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource;

class ListModerationRules extends ListRecords
{
    protected static string $resource = ModerationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [CreateAction::make()];
    }
}
