<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Badges\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\SpatieMediaLibraryImageColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

class BadgesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                SpatieMediaLibraryImageColumn::make('badge_image')
                    ->collection('badge')
                    ->circular()
                    ->label('Image'),

                TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('tenant.name')
                    ->badge()
                    ->color('gray'),

                TextColumn::make('redeem_code')
                    ->copyable(),

                TextColumn::make('provider')
                    ->badge(),

                IconColumn::make('active')
                    ->boolean(),

                TextColumn::make('characters_count')
                    ->counts('characters')
                    ->label('Claimed'),
            ])
            ->filters([
                SelectFilter::make('tenant_id')
                    ->relationship('tenant', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('active'),

                SelectFilter::make('provider')
                    ->options(IdentityProvider::class),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
