<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource;

class CreateModerationRule extends CreateRecord
{
    protected static string $resource = ModerationRuleResource::class;
}
