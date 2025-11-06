<?php

declare(strict_types=1);

namespace He4rt\Feedback\Filament\Admin\Resources\Feedback\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\FeedbackResource;

class ListFeedback extends ListRecords
{
    protected static string $resource = FeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
