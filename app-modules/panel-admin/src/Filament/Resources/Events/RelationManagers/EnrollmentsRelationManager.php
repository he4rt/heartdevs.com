<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Events\CheckIn\Actions\ManualCheckInAction;
use He4rt\Events\CheckIn\DTOs\ManualCheckInDTO;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Closure\Actions\OverrideEnrollmentStatusAction;
use He4rt\Events\Closure\DTOs\OverrideEnrollmentStatusDTO;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Date;

final class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['checkIns', 'user']))
            ->columns([
                TextColumn::make('user.name')
                    ->label('Participant')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('waitlist_position')
                    ->label('Waitlist')
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('enrolled_at')
                    ->label('Enrolled At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('confirmed_at')
                    ->label('Confirmed At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('check_in_history')
                    ->label('Check-in History')
                    ->state(fn (Enrollment $record): string => $record->checkIns
                        ->sortBy('event_date')
                        ->map(fn (CheckIn $checkIn): string => $checkIn->event_date->toDateString())
                        ->implode(', '))
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('cancelled_at')
                    ->label('Cancelled At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(EnrollmentStatus::class),
            ])
            ->recordActions([
                $this->checkInAction(),
                $this->overrideStatusAction(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    $this->bulkCheckInAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function checkInAction(): Action
    {
        return Action::make('checkIn')
            ->label('Check In')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Enrollment $record): bool => $record->status->is(EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn))
            ->schema($this->checkInSchema())
            ->action(function (Enrollment $record, array $data): void {
                $this->checkIn($record, $data);

                Notification::make()
                    ->success()
                    ->title('Participant checked in.')
                    ->send();
            });
    }

    private function bulkCheckInAction(): BulkAction
    {
        return BulkAction::make('checkInSelected')
            ->label('Check In Selected')
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->schema($this->checkInSchema())
            ->action(function (Collection $records, array $data): void {
                foreach ($records as $record) {
                    if (!$record instanceof Enrollment) {
                        continue;
                    }

                    $this->checkIn($record, $data);
                }

                Notification::make()
                    ->success()
                    ->title('Selected participants checked in.')
                    ->send();
            })
            ->deselectRecordsAfterCompletion();
    }

    /**
     * @return array<int, DatePicker>
     */
    private function checkInSchema(): array
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();

        return [
            DatePicker::make('event_date')
                ->label('Date')
                ->default(now())
                ->minDate($event->starts_at->toDateString())
                ->maxDate($event->ends_at->toDateString())
                ->required(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function checkIn(Enrollment $enrollment, array $data): CheckIn
    {
        return resolve(ManualCheckInAction::class)->handle(
            new ManualCheckInDTO(
                enrollment: $enrollment,
                actorUserId: (string) auth()->id(),
                eventDate: Date::parse($data['event_date']),
            ),
        );
    }

    private function overrideStatusAction(): Action
    {
        return Action::make('overrideStatus')
            ->label('Override Status')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('warning')
            ->visible(fn (Enrollment $record): bool => in_array($record->status, [EnrollmentStatus::NoShow, EnrollmentStatus::Confirmed], strict: true))
            ->schema([
                Select::make('to_status')
                    ->label('New Status')
                    ->options(fn (Enrollment $record): array => match ($record->status) {
                        EnrollmentStatus::NoShow => [EnrollmentStatus::Attended->value => 'Attended'],
                        EnrollmentStatus::Confirmed => [EnrollmentStatus::CheckedIn->value => 'Checked In'],
                        default => [],
                    })
                    ->required(),
                Textarea::make('reason')
                    ->label('Reason')
                    ->required()
                    ->minLength(3)
                    ->rows(3),
            ])
            ->action(function (Enrollment $record, array $data): void {
                resolve(OverrideEnrollmentStatusAction::class)->handle(
                    new OverrideEnrollmentStatusDTO(
                        enrollment: $record,
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
}
