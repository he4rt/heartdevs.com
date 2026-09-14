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
use Illuminate\Support\Arr;

final class OverrideEnrollmentStatusAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->label(__('panel-admin::events.enrollments.actions.override_status'))
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('warning')
            ->visible(fn (Enrollment $record): bool => OverrideEnrollmentStatusDomainAction::allowedTargetsFor($record->status) !== [])
            ->schema([
                Select::make('to_status')
                    ->label(__('panel-admin::events.enrollments.actions.new_status'))
                    ->options(fn (Enrollment $record): array => collect(OverrideEnrollmentStatusDomainAction::allowedTargetsFor($record->status))
                        ->mapWithKeys(fn (EnrollmentStatus $s): array => [$s->value => $s->getLabel()])
                        ->all())
                    ->required(),
                Textarea::make('reason')
                    ->label(__('panel-admin::events.enrollments.actions.reason'))
                    ->required()
                    ->minLength(3)
                    ->rows(3),
            ])
            ->action(static function (Enrollment $record, array $data): void {
                resolve(OverrideEnrollmentStatusDomainAction::class)->handle(
                    new OverrideEnrollmentStatusDTO(
                        enrollment: $record,
                        fromStatus: $record->status,
                        toStatus: EnrollmentStatus::from(Arr::string($data, 'to_status')),
                        actorId: (string) auth()->id(),
                        reason: Arr::string($data, 'reason'),
                    ),
                );

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::events.enrollments.notifications.status_overridden'))
                    ->send();
            });
    }

    public static function getDefaultName(): string
    {
        return 'overrideStatus';
    }
}
