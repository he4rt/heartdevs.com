<?php

declare(strict_types=1);

namespace He4rt\Activity\Filament\Admin\Resources\Messages;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Activity\Filament\Admin\Resources\Messages\Pages\CreateMessage;
use He4rt\Activity\Filament\Admin\Resources\Messages\Pages\EditMessage;
use He4rt\Activity\Filament\Admin\Resources\Messages\Pages\ListMessages;
use He4rt\Activity\Filament\Admin\Resources\Messages\Schemas\MessageForm;
use He4rt\Activity\Filament\Admin\Resources\Messages\Tables\MessagesTable;
use He4rt\Activity\Models\Message;
use UnitEnum;

class MessageResource extends Resource
{
    protected static ?string $model = Message::class;

    protected static string|UnitEnum|null $navigationGroup = 'Gamefication';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::ChatBubbleBottomCenter;

    protected static ?string $recordTitleAttribute = 'content';

    public static function form(Schema $schema): Schema
    {
        return MessageForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return MessagesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMessages::route('/'),
            'create' => CreateMessage::route('/create'),
            'edit' => EditMessage::route('/{record}/edit'),
        ];
    }
}
