<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages;

use Filament\Resources\Pages\CreateRecord;
use He4rt\Community\Retrospective\Enums\RetrospectiveStatus;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\RetrospectiveResource;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\DeckConfigForm;

class CreateRetrospective extends CreateRecord
{
    protected static string $resource = RetrospectiveResource::class;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['status'] = RetrospectiveStatus::Draft;

        return DeckConfigForm::collapse($data, existingHiddenSlides: []);
    }
}
