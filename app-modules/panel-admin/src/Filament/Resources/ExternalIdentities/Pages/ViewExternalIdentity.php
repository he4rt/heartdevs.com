<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use He4rt\PanelAdmin\Filament\Resources\ExternalIdentities\ExternalIdentityResource;

class ViewExternalIdentity extends EditRecord
{
    protected static string $resource = ExternalIdentityResource::class;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        unset($data['credentials']);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('disconnect')
                ->label('Disconnect')
                ->icon(Heroicon::NoSymbol)
                ->color('danger')
                ->visible(fn () => $this->record->isConnected())
                ->requiresConfirmation()
                ->action(fn () => $this->record->update(['disconnected_at' => now()]))
                ->successNotificationTitle('Identity disconnected'),
            DeleteAction::make(),
            RestoreAction::make(),
            ForceDeleteAction::make(),
        ];
    }
}
