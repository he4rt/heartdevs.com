<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource\Pages;

use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\PanelAdmin\Moderation\Resources\ModerationCaseResource;

/**
 * @property ModerationCase $record
 */
class ViewModerationCase extends ViewRecord
{
    protected static string $resource = ModerationCaseResource::class;

    public function infolist(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('Content')
                ->schema([
                    TextEntry::make('content_snapshot.text')
                        ->label('Content Text')
                        ->columnSpanFull(),
                    TextEntry::make('source_platform')
                        ->badge(),
                    TextEntry::make('content_type'),
                ]),
            Section::make('Classification')
                ->schema([
                    TextEntry::make('violation_type')
                        ->badge(),
                    TextEntry::make('severity')
                        ->badge(),
                    TextEntry::make('ai_scores')
                        ->label('AI Scores')
                        ->formatStateUsing(static function (mixed $state): string {
                            /** @var array<string, float> $scores */
                            $scores = is_array($state) ? $state : [];

                            return collect($scores)
                                ->map(fn (float $score, string $type) => $type.': '.number_format($score, 2))
                                ->implode(', ');
                        }),
                    TextEntry::make('suggested_action')
                        ->badge()
                        ->color('warning'),
                ]),
            Section::make('Meta')
                ->schema([
                    TextEntry::make('author.name'),
                    TextEntry::make('priority')
                        ->badge(),
                    TextEntry::make('status')
                        ->badge(),
                ]),
        ]);
    }

    /**
     * @return Action[]
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('take_action')
                ->label('Take Action')
                ->icon('heroicon-o-bolt')
                ->color('danger')
                ->visible(fn () => in_array($this->record->status, [CaseStatus::Pending, CaseStatus::Assigned]))
                ->schema([
                    Select::make('action_type')
                        ->options(ActionType::class)
                        ->required(),
                    Select::make('duration')
                        ->options([
                            '24h' => '24 hours',
                            '7d' => '7 days',
                            '30d' => '30 days',
                            'permanent' => 'Permanent',
                        ]),
                    CheckboxList::make('target_platforms')
                        ->options(Platform::class)
                        ->required(),
                    Textarea::make('reason')
                        ->required(),
                ])
                ->action(function (array $data): void {
                    /** @var ModerationCase $case */
                    $case = $this->record;

                    $action = ModerationAction::query()->create([
                        'case_id' => $case->id,
                        'moderator_id' => auth()->id(),
                        'action_type' => $data['action_type'],
                        'target_platforms' => $data['target_platforms'],
                        'duration' => $data['duration'],
                        'reason' => $data['reason'],
                        'automated' => false,
                    ]);

                    if ($case->author) {
                        dispatch_sync(new ExecuteAction($action, $case->author));
                    }

                    $this->redirect(static::getResource()::getUrl('index'));
                }),

            Action::make('dismiss')
                ->label('Dismiss')
                ->icon('heroicon-o-x-circle')
                ->color('gray')
                ->visible(fn () => in_array($this->record->status, [CaseStatus::Pending, CaseStatus::Assigned]))
                ->requiresConfirmation()
                ->action(function (): void {
                    $this->record->update([
                        'status' => CaseStatus::Dismissed,
                        'resolved_at' => now(),
                    ]);

                    $this->redirect(static::getResource()::getUrl('index'));
                }),
        ];
    }
}
