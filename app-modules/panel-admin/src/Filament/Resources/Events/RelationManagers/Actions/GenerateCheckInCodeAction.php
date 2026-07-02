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
        $this->label(__('panel-admin::events.check_in_codes.actions.generate_code'))
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
                        ->label(__('panel-admin::events.check_in_codes.fields.code_length'))
                        ->options([
                            '4' => __('panel-admin::events.check_in_codes.digits.four'),
                            '6' => __('panel-admin::events.check_in_codes.digits.six'),
                        ])
                        ->default('6')
                        ->live()
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            $set('code_preview', $this->generateNumericCode((int) ($state ?: 6)));
                        })
                        ->selectablePlaceholder(false)
                        ->required(),

                    TextInput::make('code_preview')
                        ->label(__('panel-admin::events.check_in_codes.fields.generated_code'))
                        ->readOnly()
                        ->default(fn (): string => $this->generateNumericCode(6))
                        ->dehydrated()
                        ->required(),

                    DatePicker::make('event_date')
                        ->label(__('panel-admin::events.columns.event_date'))
                        ->default($event->starts_at->toDateString())
                        ->minDate($event->starts_at->toDateString())
                        ->maxDate($event->ends_at->toDateString())
                        ->required(),

                    DateTimePicker::make('starts_at')
                        ->label(__('panel-admin::events.columns.valid_from'))
                        ->default(now())
                        ->required(),

                    DateTimePicker::make('expires_at')
                        ->label(__('panel-admin::events.columns.expires_at'))
                        ->afterOrEqual('starts_at')
                        ->default(now()->addHours(2))
                        ->required(),

                    TextInput::make('max_uses')
                        ->label(__('panel-admin::events.check_in_codes.fields.max_uses'))
                        ->numeric()
                        ->minValue(1)
                        ->placeholder(__('panel-admin::events.check_in_codes.unlimited')),
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
