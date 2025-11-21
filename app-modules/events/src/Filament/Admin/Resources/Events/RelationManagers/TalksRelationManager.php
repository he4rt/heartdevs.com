<?php

declare(strict_types=1);

namespace He4rt\Events\Filament\Admin\Resources\Events\RelationManagers;

use Filament\Actions\CreateAction;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Table;
use He4rt\Events\Filament\Admin\Resources\Talks\TalkResource;

class TalksRelationManager extends RelationManager
{
    protected static string $relationship = 'talks';

    protected static ?string $relatedResource = TalkResource::class;

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                CreateAction::make(),
            ]);
    }
}
