<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Filament\Resources\Seasons\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Icons\Heroicon;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Character\Models\PastSeason;
use He4rt\PanelAdmin\Filament\Resources\Seasons\SeasonResource;

class EditSeason extends EditRecord
{
    protected static string $resource = SeasonResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('endSeason')
                ->label('End Season')
                ->icon(Heroicon::Stop)
                ->color('danger')
                ->visible(fn () => $this->record->ended_at === null)
                ->requiresConfirmation()
                ->modalDescription('This will set ended_at to now.')
                ->action(fn () => $this->record->update(['ended_at' => now()]))
                ->successNotificationTitle('Season ended'),

            Action::make('computeRankings')
                ->label('Compute Rankings')
                ->icon(Heroicon::ChartBar)
                ->color('warning')
                ->requiresConfirmation()
                ->modalDescription('This will snapshot all character rankings for this season.')
                ->action(function (): void {
                    $characters = Character::query()
                        ->where('tenant_id', $this->record->tenant_id)
                        ->orderByDesc('experience')
                        ->get();

                    $position = 1;
                    foreach ($characters as $character) {
                        PastSeason::create([
                            'tenant_id' => $this->record->tenant_id,
                            'season_id' => $this->record->id,
                            'character_id' => $character->id,
                            'ranking_position' => $position++,
                            'level' => $character->level,
                            'experience' => $character->experience,
                            'messages_count' => 0,
                            'badges_count' => $character->badges()->count(),
                            'meetings_count' => 0,
                        ]);
                    }
                })
                ->successNotificationTitle('Rankings computed'),

            DeleteAction::make(),
        ];
    }
}
