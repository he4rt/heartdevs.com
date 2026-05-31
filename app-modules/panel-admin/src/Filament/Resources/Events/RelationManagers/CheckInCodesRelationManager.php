<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Event\Models\Event;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

final class CheckInCodesRelationManager extends RelationManager
{
    protected static string $relationship = 'checkInCodes';

    protected static ?string $title = 'Check-in Codes';

    public static function getBadge(Model $ownerRecord, string $pageClass): string
    {
        /** @var Event $ownerRecord */
        return (string) $ownerRecord->checkInCodes()->count();
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('code')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->latest())
            ->columns([
                TextColumn::make('code')
                    ->label('Code')
                    ->badge()
                    ->color(fn (CheckInCode $record): string => $record->revoked_at !== null ? 'gray' : ($record->expires_at->isPast() ? 'warning' : 'success'))
                    ->searchable(),

                TextColumn::make('event_date')
                    ->label('Event Date')
                    ->date()
                    ->sortable(),

                TextColumn::make('starts_at')
                    ->label('Valid From')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable(),

                TextColumn::make('uses_count')
                    ->label('Uses')
                    ->state(fn (CheckInCode $record): string => $record->uses_count.($record->max_uses !== null ? '/'.$record->max_uses : '')),

                TextColumn::make('revoked_at')
                    ->label('Revoked At')
                    ->dateTime()
                    ->placeholder('-'),
            ])
            ->headerActions([
                $this->generateCodeAction(),
            ])
            ->recordActions([
                $this->revokeCodeAction(),
            ]);
    }

    private function generateCodeAction(): CreateAction
    {
        return CreateAction::make('generateCode')
            ->label('Generate Code')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->color('success')
            ->form(fn (): array => $this->generateFormSchema())
            ->using(function (array $data, self $livewire): CheckInCode {
                $digits = (int) $data['digits'];

                /** @var Event $event */
                $event = $livewire->getOwnerRecord();

                $code = $this->generateNumericCode($digits);

                return CheckInCode::query()->create([
                    'event_id' => $event->id,
                    'event_date' => $data['event_date'],
                    'code' => $code,
                    'starts_at' => $data['starts_at'],
                    'expires_at' => $data['expires_at'],
                    'max_uses' => $data['max_uses'] !== '' ? (int) $data['max_uses'] : null,
                ]);
            });
    }

    private function revokeCodeAction(): Action
    {
        return Action::make('revoke')
            ->label('Revoke')
            ->icon(Heroicon::OutlinedNoSymbol)
            ->color('danger')
            ->visible(fn (CheckInCode $record): bool => $record->revoked_at === null)
            ->requiresConfirmation()
            ->action(function (CheckInCode $record): void {
                $record->update(['revoked_at' => now()]);

                Notification::make()
                    ->success()
                    ->title('Code revoked.')
                    ->send();
            });
    }

    /**
     * @return array<int, mixed>
     */
    private function generateFormSchema(): array
    {
        /** @var Event $event */
        $event = $this->getOwnerRecord();

        return [
            Section::make()
                ->columns(2)
                ->schema([
                    Select::make('digits')
                        ->label('Code Length')
                        ->options([
                            '4' => '4 digits',
                            '6' => '6 digits',
                        ])
                        ->default('6')
                        ->selectablePlaceholder(false)
                        ->required(),

                    TextInput::make('code_preview')
                        ->label('Generated Code')
                        ->disabled()
                        ->default(fn (): string => $this->generateNumericCode(6))
                        ->dehydrated(false),

                    DatePicker::make('event_date')
                        ->label('Event Date')
                        ->default($event->starts_at->toDateString())
                        ->minDate($event->starts_at->toDateString())
                        ->maxDate($event->ends_at->toDateString())
                        ->required(),

                    DateTimePicker::make('starts_at')
                        ->label('Valid From')
                        ->default(now())
                        ->required(),

                    DateTimePicker::make('expires_at')
                        ->label('Expires At')
                        ->default(now()->addHours(2))
                        ->afterOrEqual('starts_at')
                        ->required(),

                    TextInput::make('max_uses')
                        ->label('Max Uses (optional)')
                        ->numeric()
                        ->minValue(1)
                        ->placeholder('Unlimited'),
                ]),
        ];
    }

    private function generateNumericCode(int $digits): string
    {
        $min = 10 ** ($digits - 1);
        $max = 10 ** $digits - 1;

        return (string) random_int($min, $max);
    }
}
