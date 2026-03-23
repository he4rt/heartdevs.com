<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Feedback;

use BackedEnum;
use Filament\Forms\Components\Placeholder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Community\Feedback\Models\Feedback;
use He4rt\PanelAdmin\Filament\Resources\Feedback\Pages\EditFeedback;
use He4rt\PanelAdmin\Filament\Resources\Feedback\Pages\ListFeedback;
use He4rt\PanelAdmin\Filament\Resources\Feedback\Tables\FeedbackTable;
use UnitEnum;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'Moderation';

    protected static ?int $navigationSort = 1;

    public static function getNavigationBadge(): ?string
    {
        return (string) Feedback::whereDoesntHave('review')->count();
    }

    public static function form(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Placeholder::make('sender')
                    ->content(fn ($record) => $record?->sender?->username ?? '-'),
                Placeholder::make('target')
                    ->content(fn ($record) => $record?->target?->username ?? '-'),
                Placeholder::make('type')
                    ->content(fn ($record) => $record?->type ?? '-'),
                Placeholder::make('message')
                    ->content(fn ($record) => $record?->message ?? '-'),
                Placeholder::make('created_at')
                    ->content(fn ($record) => $record?->created_at?->format('Y-m-d H:i') ?? '-'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return FeedbackTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedback::route('/'),
            'edit' => EditFeedback::route('/{record}/edit'),
        ];
    }
}
