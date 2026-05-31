<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\CheckIn\Actions\QrCheckInAction;
use He4rt\Events\CheckIn\DTOs\QrCheckInDTO;
use He4rt\Events\CheckIn\Exceptions\CheckInException;
use He4rt\Events\Event\Models\Event;
use He4rt\PanelAdmin\Filament\Resources\Events\EventResource;
use Livewire\Attributes\On;
use Throwable;

final class EditEvent extends EditRecord
{
    protected static string $resource = EventResource::class;

    #[On('reopen-scan-qr')]
    public function reopenScanQrModal(): void
    {
        $this->mountAction('scanQr');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('scanQr')
                ->label('Scan QR')
                ->icon(Heroicon::QrCode)
                ->color('success')
                ->schema([
                    TextInput::make('token')
                        ->label('QR Token')
                        ->required()
                        ->autofocus()
                        ->placeholder('Scan or paste the participant token'),
                ])
                ->modalSubmitActionLabel('Check In')
                ->action(function (array $data): void {
                    /** @var Event $event */
                    $event = $this->getRecord();

                    try {
                        $checkIn = resolve(QrCheckInAction::class)->handle(
                            new QrCheckInDTO(
                                token: $data['token'],
                                event: $event,
                                eventDate: now(),
                                actorUserId: (string) auth()->id(),
                            ),
                        );

                        $checkIn->enrollment->loadMissing('user');
                        $participantName = $checkIn->enrollment->user?->name ?? 'Participant';

                        Notification::make()
                            ->success()
                            ->title('Check-in successful')
                            ->body($participantName.' has been checked in.')
                            ->send();
                    } catch (CheckInException $e) {
                        Notification::make()
                            ->danger()
                            ->title('Check-in failed')
                            ->body($e->getMessage())
                            ->send();
                    } catch (Throwable $e) {
                        Notification::make()
                            ->danger()
                            ->title('Check-in failed')
                            ->body('An unexpected error occurred. Please try again.')
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
