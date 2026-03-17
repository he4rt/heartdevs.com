<?php

declare(strict_types=1);

namespace He4rt\Community\Feedback\Filament\Admin\Resources\Feedback\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Community\Feedback\Filament\Admin\Resources\Feedback\FeedbackResource;

class CreateFeedback extends CreateRecord
{
    protected static string $resource = FeedbackResource::class;
}
