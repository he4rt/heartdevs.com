<?php

declare(strict_types=1);

namespace He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions\Pages;

use Filament\Infolists\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Schema;
use He4rt\Activity\Tracking\Filament\Admin\Resources\Interactions\InteractionResource;

class ViewInteraction extends ViewRecord
{
    protected static string $resource = InteractionResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Interaction Details')
                    ->schema([
                        TextEntry::make('type')->badge(),
                        TextEntry::make('provider')->badge(),
                        TextEntry::make('status')->badge(),
                        TextEntry::make('value_tier')->badge()->label('Tier'),
                        TextEntry::make('character.user.name')->label('User'),
                        TextEntry::make('coins_min')->label('Min Coins'),
                        TextEntry::make('coins_max')->label('Max Coins'),
                        TextEntry::make('coins_awarded')->label('Awarded'),
                        TextEntry::make('xp_awarded')->label('XP Awarded'),
                        TextEntry::make('external_ref')->label('External Ref'),
                        TextEntry::make('occurred_at')->dateTime('d/m/Y H:i'),
                        TextEntry::make('reviewed_at')->dateTime('d/m/Y H:i'),
                    ])->columns(3),

                Section::make('Metadata')
                    ->schema([
                        TextEntry::make('metadata')
                            ->formatStateUsing(fn (?array $state): string => $state ? json_encode($state, JSON_PRETTY_PRINT) : 'N/A')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
