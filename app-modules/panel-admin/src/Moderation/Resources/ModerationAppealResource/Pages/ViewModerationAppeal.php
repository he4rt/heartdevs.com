<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources\ModerationAppealResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Moderation\Enums\AppealStatus;
use He4rt\Moderation\Models\ModerationAppeal;
use He4rt\PanelAdmin\Moderation\Resources\ModerationAppealResource;

/**
 * @property ModerationAppeal $record
 */
class ViewModerationAppeal extends ViewRecord
{
    protected static string $resource = ModerationAppealResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Appeal Details')
                ->schema([
                    TextEntry::make('reason_category')->badge(),
                    TextEntry::make('reason_text')->columnSpanFull(),
                    TextEntry::make('appellant.name'),
                    TextEntry::make('sla_deadline')->dateTime(),
                ]),
            Section::make('Original Action')
                ->schema([
                    TextEntry::make('action.action_type')->badge(),
                    TextEntry::make('action.reason'),
                    TextEntry::make('action.moderator.name')
                        ->label('Original Moderator'),
                ]),
        ]);
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('uphold')
                ->label('Uphold Decision')
                ->icon('heroicon-o-hand-thumb-down')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [AppealStatus::Pending, AppealStatus::Reviewing]))
                ->schema([
                    Textarea::make('reviewer_notes')->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => AppealStatus::Upheld,
                        'reviewer_id' => auth()->id(),
                        'reviewer_notes' => $data['reviewer_notes'],
                        'resolved_at' => now(),
                    ]);
                }),

            Action::make('overturn')
                ->label('Overturn Decision')
                ->icon('heroicon-o-hand-thumb-up')
                ->color('success')
                ->visible(fn () => in_array($this->record->status, [AppealStatus::Pending, AppealStatus::Reviewing]))
                ->schema([
                    Textarea::make('reviewer_notes')->required(),
                ])
                ->action(function (array $data): void {
                    $this->record->update([
                        'status' => AppealStatus::Overturned,
                        'reviewer_id' => auth()->id(),
                        'reviewer_notes' => $data['reviewer_notes'],
                        'resolved_at' => now(),
                    ]);
                }),
        ];
    }
}
