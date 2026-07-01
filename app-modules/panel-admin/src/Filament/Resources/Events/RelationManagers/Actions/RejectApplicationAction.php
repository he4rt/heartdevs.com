<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\Enrollment\Actions\RejectApplicationAction as RejectApplicationDomainAction;
use He4rt\Events\Enrollment\DTOs\RejectApplicationDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Exceptions\EnrollmentException;
use He4rt\Events\Enrollment\Models\Enrollment;

final class RejectApplicationAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Reject')
            ->icon(Heroicon::OutlinedXCircle)
            ->color('danger')
            ->visible(fn (Enrollment $record): bool => $record->status === EnrollmentStatus::Pending)
            ->schema([
                Textarea::make('reason')
                    ->label('Rejection Reason')
                    ->required()
                    ->minLength(3)
                    ->maxLength(500)
                    ->rows(3),
            ])
            ->action(function (Enrollment $record, array $data): void {
                try {
                    resolve(RejectApplicationDomainAction::class)->handle(
                        new RejectApplicationDTO(
                            enrollmentId: $record->id,
                            actorId: (string) auth()->id(),
                            reason: $data['reason'],
                        ),
                    );

                    Notification::make()
                        ->success()
                        ->title('Application rejected.')
                        ->send();
                } catch (EnrollmentException $enrollmentException) {
                    Notification::make()
                        ->danger()
                        ->title($enrollmentException->getMessage())
                        ->send();
                }
            });
    }

    public static function getDefaultName(): string
    {
        return 'rejectApplication';
    }
}
