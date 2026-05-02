<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources;

use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\Platform;
use He4rt\Moderation\Enums\Severity;
use He4rt\Moderation\Enums\ViolationType;
use He4rt\Moderation\Rules\ModerationRule;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages\CreateModerationRule;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages\EditModerationRule;
use He4rt\PanelAdmin\Moderation\Resources\ModerationRuleResource\Pages\ListModerationRules;
use UnitEnum;

class ModerationRuleResource extends Resource
{
    protected static ?string $model = ModerationRule::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-funnel';

    protected static ?string $navigationLabel = 'Rules';

    protected static string|UnitEnum|null $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 3;

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
                IconColumn::make('is_active')
                    ->boolean(),
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
                EditAction::make(),
            ]);
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
