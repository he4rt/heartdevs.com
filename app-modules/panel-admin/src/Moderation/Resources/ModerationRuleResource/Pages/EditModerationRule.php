<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages;

use Filament\Resources\Pages\EditRecord;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource;

class EditModerationRule extends EditRecord
{
    protected static string $resource = ModerationRuleResource::class;

    protected function getHeaderActions(): array
    {
        return [
            ModerationRuleResource::testRuleAction(),
        ];
    }
}
