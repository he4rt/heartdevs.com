<?php

declare(strict_types=1);

namespace He4rt\Feedback\Filament\Admin\Resources\Feedback;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\Pages\CreateFeedback;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\Pages\EditFeedback;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\Pages\ListFeedback;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\Schemas\FeedbackForm;
use He4rt\Feedback\Filament\Admin\Resources\Feedback\Tables\FeedbackTable;
use He4rt\Feedback\Models\Feedback;
use UnitEnum;

class FeedbackResource extends Resource
{
    protected static ?string $model = Feedback::class;

    protected static string|UnitEnum|null $navigationGroup = 'General';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    protected static ?string $recordTitleAttribute = 'message';

    public static function form(Schema $schema): Schema
    {
        return FeedbackForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return FeedbackTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFeedback::route('/'),
            'create' => CreateFeedback::route('/create'),
            'edit' => EditFeedback::route('/{record}/edit'),
        ];
    }
}
