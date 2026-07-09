<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Components\ViewComponent;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\CheckIn\Actions\QrCheckInAction;
use He4rt\Events\CheckIn\DTOs\QrCheckInDTO;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\Event\Models\Event;
use He4rt\PanelAdmin\Filament\Resources\Events\EventResource;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Throwable;

final class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    #[On(event: 'reopen-scan-qr')]
    public function reopenScanQrModal(): void
    {
        $this->mountAction('scanQr');
    }

    /**
     * @return ViewComponent[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('scanQr')
                ->label(__('panel-admin::events.edit.scan_qr'))
                ->icon(Heroicon::QrCode)
                ->color('success')
                ->schema([
                    TextInput::make('token')
                        ->label(__('panel-admin::events.edit.qr_token'))
                        ->required()
                        ->autofocus()
                        ->placeholder(__('panel-admin::events.edit.qr_token_placeholder')),
                ])
                ->modalSubmitActionLabel(__('panel-admin::events.edit.check_in_submit'))
                ->action(function (array $data): void {
                    /** @var Event $event */
                    $event = $this->getRecord();

                    try {
                        $checkIn = resolve(QrCheckInAction::class)->handle(
                            new QrCheckInDTO(
                                token: Arr::string($data, 'token'),
                                event: $event,
                                eventDate: now(),
                                actorUserId: (string) auth()->id(),
                            ),
                        );

                        $checkIn->enrollment->loadMissing('user');
                        $participantName = $checkIn->enrollment->user->name ?? __('panel-admin::events.edit.participant_fallback');

                        Notification::make()
                            ->success()
                            ->title(__('panel-admin::events.edit.notifications.check_in_success_title'))
                            ->body(__('panel-admin::events.edit.notifications.check_in_success_body', [
                                'name' => $participantName,
                            ]))
                            ->send();
                    } catch (CheckInException $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('panel-admin::events.edit.notifications.check_in_failed_title'))
                            ->body($e->getMessage())
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title(__('panel-admin::events.edit.notifications.check_in_failed_title'))
                            ->body(__('panel-admin::events.edit.notifications.check_in_unexpected_error'))
                            ->send();

                        report($e);
                    } finally {
                        $this->dispatch('reopen-scan-qr');
                    }
                }),
            DeleteAction::make(),
        ];
    }
}
