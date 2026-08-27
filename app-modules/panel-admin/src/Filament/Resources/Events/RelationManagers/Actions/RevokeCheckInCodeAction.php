<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\CheckIn\Models\CheckInCode;

final class RevokeCheckInCodeAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-admin::events.check_in_codes.actions.revoke'))
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (CheckInCode $record): bool => $record->revoked_at === null)
            ->requiresConfirmation()
            ->action(static function (CheckInCode $record): void {
                $record->update(['revoked_at' => now()]);

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::events.check_in_codes.notifications.code_revoked'))
                    ->send();
            });
    }

    public static function getDefaultName(): string
    {
        return 'revoke-check-in-code-action';
    }
}
