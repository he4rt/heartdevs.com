<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Events\RelationManagers\Actions;

use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use He4rt\Events\CheckIn\Models\CheckInCode;
use He4rt\Events\Event\Models\Event;

final class GenerateCheckInCodeAction extends Action
{
    protected function setUp(): void
    {
        parent::setUp();
        $this->label('Generate Code')
            ->icon(Heroicon::OutlinedPlusCircle)
            ->color('success')
            ->schema($this->generateFormSchema(...))
            ->action($this->persistCheckInCode(...));
    }

    public static function getDefaultName(): string
    {
        return 'generateCode';
    }

    /**
     * @param  array{digits?: string, code_preview: string, event_date: string, starts_at: string, expires_at: string, max_uses?: string|int|null}  $data
     */
    public function persistCheckInCode(array $data, RelationManager $livewire): CheckInCode
    {
        /** @var Event $event */
        $event = $livewire->getOwnerRecord();

        $code = (string) $data['code_preview'];

        if (!preg_match('/^\d{4}$|^\d{6}$/', $code)) {
            $code = $this->generateNumericCode((int) ($data['digits'] ?? 6));
        }

        return CheckInCode::query()->create([
            'event_id' => $event->id,
            'event_date' => $data['event_date'],
            'code' => $code,
            'starts_at' => $data['starts_at'],
            'expires_at' => $data['expires_at'],
            'max_uses' => filled($data['max_uses'] ?? null) ? (int) $data['max_uses'] : null,
        ]);
    }

    /**
     * @return array<int, mixed>
     */
    private function generateFormSchema(RelationManager $livewire): array
    {
        /** @var Event $event */
        $event = $livewire->getOwnerRecord();

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
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $set('code_preview', $this->generateNumericCode((int) ($state ?: 6)));
                        })
                        ->selectablePlaceholder(false)
                        ->required(),

                    TextInput::make('code_preview')
                        ->label('Generated Code')
                        ->readOnly()
                        ->default(fn (): string => $this->generateNumericCode(6))
                        ->dehydrated()
                        ->required(),

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
