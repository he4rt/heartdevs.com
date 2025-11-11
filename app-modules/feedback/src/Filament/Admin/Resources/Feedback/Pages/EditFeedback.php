<?php

declare(strict_types=1);

namespace He4rt\Feedback\Filament\Admin\Resources\Feedback\Pages;

use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\FeedbackResource;

class EditFeedback extends EditRecord
{
    protected static string $resource = FeedbackResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
