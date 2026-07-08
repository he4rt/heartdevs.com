<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers;

use Carbon\CarbonInterface;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Forms\Components\DatePicker;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use He4rt\Events\CheckIn\Actions\ManualCheckInAction;
use He4rt\Events\CheckIn\DTOs\ManualCheckInDTO;
use He4rt\Events\CheckIn\Models\CheckIn;
use He4rt\Events\Enrollment\Enums\EnrollmentStatus;
use He4rt\Events\Enrollment\Models\Enrollment;
use He4rt\Events\Event\Models\Event;
use He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions\ApproveApplicationAction;
use He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions\OverrideEnrollmentStatusAction;
use He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions\RejectApplicationAction;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Date;

final class EnrollmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'enrollments';

    public static function getTitle(Model $ownerRecord, string $pageClass): string
    {
        return __('panel-admin::events.relations.enrollments');
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitle(fn (Enrollment $record): string => $record->user->name ?? $record->id)
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['checkIns', 'user']))
            ->columns([
                TextColumn::make('user.name')
                    ->label(__('panel-admin::events.enrollments.columns.participant'))
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label(__('panel-admin::events.columns.status'))
                    ->badge()
                    ->sortable(),

                TextColumn::make('waitlist_position')
                    ->label(__('panel-admin::events.enrollments.columns.waitlist'))
                    ->sortable()
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('enrolled_at')
                    ->label(__('panel-admin::events.enrollments.columns.enrolled_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('confirmed_at')
                    ->label(__('panel-admin::events.enrollments.columns.confirmed_at'))
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('check_in_history')
                    ->label(__('panel-admin::events.enrollments.columns.check_in_history'))
                    ->state(fn (Enrollment $record): string => $record->checkIns
                        ->sortBy('event_date')
                        ->map(fn (CheckIn $checkIn): string => $checkIn->event_date->toDateString())
                        ->implode(', '))
                    ->placeholder('-')
                    ->toggleable(),

                TextColumn::make('cancelled_at')
                    ->label(__('panel-admin::events.enrollments.columns.cancelled_at'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label(__('panel-admin::events.columns.status'))
                    ->options(EnrollmentStatus::class),
            ])
            ->recordActions([
                $this->viewApplicationAction(),
                ApproveApplicationAction::make(),
                RejectApplicationAction::make(),
                $this->checkInAction(),
                OverrideEnrollmentStatusAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    $this->bulkCheckInAction(),
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    private function viewApplicationAction(): Action
    {
        return Action::make('viewApplication')
            ->label('View Application')
            ->icon(Heroicon::OutlinedDocumentText)
            ->color('gray')
            ->visible(fn (Enrollment $record): bool => $record->application_data !== null)
            ->modalContent(static function (Enrollment $record): View {
                $schema = $record->event?->enrollmentPolicy?->application_schema ?? [];
                $data = $record->application_data ?? [];

                $answers = collect($schema)
                    ->map(fn (array $field, int $index): array => [
                        'label' => $field['label'] ?? 'Question '.$index,
                        'value' => $data[$field['key'] ?? null] ?? '—',
                    ])
                    ->values()
                    ->all();

                return view('panel-admin::enrollments.application-data', [
                    'answers' => $answers,
                    'record' => $record,
                ]);
            })
            ->modalSubmitAction(action: false);
    }

    private function checkInAction(): Action
    {
        return Action::make('checkIn')
            ->label(__('panel-admin::events.enrollments.actions.check_in'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->visible(fn (Enrollment $record): bool => $record->status->is(EnrollmentStatus::Confirmed, EnrollmentStatus::CheckedIn))
            ->schema($this->checkInSchema())
            ->action(function (Enrollment $record, array $data): void {
                $this->checkIn($record, Date::parse(Arr::string($data, 'event_date')));

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::events.enrollments.notifications.participant_checked_in'))
                    ->send();
            });
    }

    private function bulkCheckInAction(): BulkAction
    {
        return BulkAction::make('checkInSelected')
            ->label(__('panel-admin::events.enrollments.actions.check_in_selected'))
            ->icon(Heroicon::OutlinedCheckCircle)
            ->color('success')
            ->schema($this->checkInSchema())
            ->action(function (Collection $records, array $data): void {
                foreach ($records as $record) {
                    if (!$record instanceof Enrollment) {
                        continue;
                    }

                    $this->checkIn($record, Date::parse(Arr::string($data, 'event_date')));
                }

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::events.enrollments.notifications.selected_participants_checked_in'))
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
                ->label(__('panel-admin::events.columns.date'))
                ->default(now())
                ->minDate($event->starts_at->toDateString())
                ->maxDate($event->ends_at->toDateString())
                ->required(),
        ];
    }

    private function checkIn(Enrollment $enrollment, CarbonInterface $eventDate): CheckIn
    {
        return resolve(ManualCheckInAction::class)->handle(
            new ManualCheckInDTO(
                enrollment: $enrollment,
                actorUserId: (string) auth()->id(),
                eventDate: $eventDate,
            ),
        );
    }
}
