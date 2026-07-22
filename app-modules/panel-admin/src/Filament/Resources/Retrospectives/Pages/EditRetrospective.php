<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Retrospectives\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use He4rt\Community\Retrospective\Models\Retrospective;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Actions\PublishRetrospectiveAction;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\RetrospectiveResource;
use He4rt\PanelAdmin\Filament\Resources\Retrospectives\Support\DeckConfigForm;

class EditRetrospective extends EditRecord
{
    protected static string $resource = RetrospectiveResource::class;

    /**
     * @return array<int, Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            PublishRetrospectiveAction::make(),

            Action::make('preview')
                ->label('Ver preview')
                ->icon(Heroicon::OutlinedEye)
                ->color('gray')
                ->url(fn (Retrospective $record): string => route('community.retrospective.preview', $record))
                ->openUrlInNewTab(),

            DeleteAction::make(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeFill(array $data): array
    {
        /** @var Retrospective $record */
        $record = $this->getRecord();
        $config = $record->deck_config;

        $data['deck_sources'] = DeckConfigForm::sourceRows($config);
        $data['deck_exclusions'] = DeckConfigForm::exclusionRows($config);

        unset($data['deck_config']);

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        /** @var Retrospective $record */
        $record = $this->getRecord();

        return DeckConfigForm::collapse($data, existingHiddenSlides: $record->deck_config->hiddenSlides);
    }
}
