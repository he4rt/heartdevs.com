<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events\RelationManagers;

use BackedEnum;
use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use He4rt\Events\Filament\Admin\Resources\Talks\TalkResource;
use He4rt\Events\Models\EventModel;
use Illuminate\Database\Eloquent\Model;

class TalksRelationManager extends RelationManager
{
    protected static string $relationship = 'talks';

    protected static ?string $relatedResource = TalkResource::class;

    public static function getBadge(Model $ownerRecord, string $pageClass): ?string
    {
        /** @var EventModel $ownerRecord */
        return (string) $ownerRecord->talks()->count();
    }

    public static function getIcon(Model $ownerRecord, string $pageClass): string|BackedEnum|null
    {
        return Heroicon::Microphone;
    }

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
