<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;
use Filament\Tables\Table;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Rules\ModerationRule;
use He4rt\PanelAdmin\Moderation\ModerationCluster;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages\CreateModerationRule;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages\EditModerationRule;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages\ListModerationRules;

class ModerationRuleResource extends Resource
{
    protected static ?string $cluster = ModerationCluster::class;

    protected static ?string $model = ModerationRule::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFunnel;

    protected static ?int $navigationSort = 3;

    protected static ?string $slug = 'rules';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.rules');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_config');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')
                ->required()
                ->maxLength(100),
            Select::make('type')
                ->options(['keyword' => 'Keyword', 'regex' => 'Regex'])
                ->required(),
            Textarea::make('pattern')
                ->required()
                ->helperText('Keywords: comma-separated. Regex: pattern without delimiters.'),
            Select::make('platform')
                ->options(Platform::class)
                ->placeholder('All platforms'),
            Select::make('violation_type')
                ->options(ViolationType::class)
                ->required(),
            Select::make('severity')
                ->options(Severity::class)
                ->required(),
            Select::make('action_on_match')
                ->options(ActionType::class)
                ->required(),
            Toggle::make('is_active')
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ToggleColumn::make('is_active'),
                TextColumn::make('name')
                    ->searchable(),
                TextColumn::make('type')
                    ->badge(),
                TextColumn::make('platform')
                    ->badge()
                    ->default('All'),
                TextColumn::make('violation_type')
                    ->badge(),
                TextColumn::make('severity')
                    ->badge(),
            ])
            ->recordActions([
                self::testRuleAction(),
                EditAction::make(),
            ]);
    }

    public static function testRuleAction(): Action
    {
        return Action::make('testRule')
            ->label(__('panel-admin::moderation.rules.actions.test'))
            ->icon(Heroicon::OutlinedBeaker)
            ->color('gray')
            ->schema([
                Textarea::make('test_input')
                    ->label(__('panel-admin::moderation.rules.actions.test_input'))
                    ->required()
                    ->rows(4),
            ])
            ->action(static function (array $data, ModerationRule $record): void {
                if ($record->matches($data['test_input'])) {
                    Notification::make()
                        ->success()
                        ->title(__('panel-admin::moderation.rules.actions.test_match', [
                            'violation' => $record->violation_type->getLabel(),
                            'severity' => $record->severity->getLabel(),
                            'action' => $record->action_on_match->getLabel(),
                        ]))
                        ->persistent()
                        ->send();

                    return;
                }

                Notification::make()
                    ->warning()
                    ->title(__('panel-admin::moderation.rules.actions.test_no_match'))
                    ->send();
            });
    }

    public static function getPages(): array
    {
        return [
            'index' => ListModerationRules::route('/'),
            'create' => CreateModerationRule::route('/create'),
            'edit' => EditModerationRule::route('/{record}/edit'),
        ];
    }
}
