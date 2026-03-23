<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\EventModels\RelationManagers;

use Filament\Actions\Action;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use He4rt\Events\Enums\Talks\TalkStatusEnum;

class TalksRelationManager extends RelationManager
{
    protected static string $relationship = 'talks';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title')
                    ->searchable(),
                TextColumn::make('user.username')
                    ->label('Speaker'),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('field_type'),
            ])
            ->actions([
                Action::make('accept')
                    ->icon(Heroicon::Check)
                    ->color('success')
                    ->visible(fn ($record) => $record->status === TalkStatusEnum::Pending)
                    ->action(fn ($record) => $record->update(['status' => TalkStatusEnum::Accepted]))
                    ->successNotificationTitle('Talk accepted'),
                Action::make('reject')
                    ->icon(Heroicon::XMark)
                    ->color('danger')
                    ->visible(fn ($record) => $record->status === TalkStatusEnum::Pending)
                    ->action(fn ($record) => $record->update(['status' => TalkStatusEnum::Rejected]))
                    ->successNotificationTitle('Talk rejected'),
            ]);
    }
}
