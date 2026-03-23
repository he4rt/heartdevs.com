<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Feedback\Pages;

use Filament\Resources\Pages\ListRecords;
use He4rt\PanelAdmin\Filament\Resources\Feedback\FeedbackResource;

class ListFeedback extends ListRecords
{
    protected static string $resource = FeedbackResource::class;
}
