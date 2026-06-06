<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\Closure\Actions\OverrideEnrollmentStatusAction as OverrideEnrollmentStatusDomainAction;
use He4rt\Events\Closure\DTOs\OverrideEnrollmentStatusDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;

final class OverrideEnrollmentStatusAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label('Override Status')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('warning')
            ->visible(fn (Enrollment $record): bool => OverrideEnrollmentStatusDomainAction::allowedTargetsFor($record->status) !== [])
            ->schema([
                Select::make('to_status')
                    ->label('New Status')
                    ->options(fn (Enrollment $record): array => collect(OverrideEnrollmentStatusDomainAction::allowedTargetsFor($record->status))
                        ->mapWithKeys(fn (EnrollmentStatus $s): array => [$s->value => $s->getLabel()])
                        ->all())
                    ->required(),
                Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->minLength(3)
                    ->rows(3),
            ])
            ->action(function (Enrollment $record, array $data): void {
                resolve(OverrideEnrollmentStatusDomainAction::class)->handle(
                    new OverrideEnrollmentStatusDTO(
                        enrollment: $record,
                        fromStatus: $record->status,
                        toStatus: EnrollmentStatus::from($data['to_status']),
                        actorId: (string) auth()->id(),
                        reason: $data['reason'],
                    ),
                );

                Notification::make()
                    ->success()
                    ->title('Enrollment status overridden.')
                    ->send();
            });
    }

    public static function getDefaultName(): string
    {
        return 'overrideStatus';
    }
}
