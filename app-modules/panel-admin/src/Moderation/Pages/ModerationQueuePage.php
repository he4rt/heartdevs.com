<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\Moderation\Cases\Models\ModerationCase;
use He4rt\Moderation\Enforcement\ExecuteAction;
use He4rt\Moderation\Enforcement\ModerationAction;
use He4rt\Moderation\Enums\ActionType;
use He4rt\Moderation\Enums\CaseStatus;
use He4rt\Moderation\Enums\Platform;
use He4rt\PanelAdmin\Moderation\ModerationCluster;

class ModerationQueuePage extends Page
{
    protected static ?string $cluster = ModerationCluster::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static ?int $navigationSort = 0;

    protected static ?string $slug = 'queue';

    protected string $view = 'panel-admin::moderation.queue';

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::moderation.navigation.queue');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('panel-admin::moderation.navigation.group_moderation');
    }

    public function takeActionAction(): Action
    {
        return Action::make('takeAction')
            ->label(__('panel-admin::moderation.queue.actions.take_action'))
            ->icon(Heroicon::OutlinedBolt)
            ->color('danger')
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
            ->action(function (array $data, array $arguments): void {
                $case = ModerationCase::query()->find($arguments['caseId'] ?? null);

                if (!$case) {
                    return;
                }

                $action = ModerationAction::query()->create([
                    'case_id' => $case->id,
                    'moderator_id' => auth()->id(),
                    'action_type' => $data['action_type'],
                    'target_platforms' => $data['target_platforms'],
                    'duration' => $data['duration'],
                    'reason' => $data['reason'],
                    'automated' => false,
                    'tenant_id' => $case->tenant_id,
                ]);

                if ($case->author) {
                    dispatch_sync(new ExecuteAction($action, $case->author));
                }

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::moderation.queue.actions.success'))
                    ->send();
            });
    }

    public function escalateAction(): Action
    {
        return Action::make('escalate')
            ->label(__('panel-admin::moderation.queue.actions.escalate'))
            ->icon(Heroicon::OutlinedArrowUpCircle)
            ->color('warning')
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                $case = ModerationCase::query()->find($arguments['caseId'] ?? null);

                $case?->update(['status' => CaseStatus::Escalated]);

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::moderation.queue.actions.escalated'))
                    ->send();
            });
    }

    public function dismissAction(): Action
    {
        return Action::make('dismiss')
            ->label(__('panel-admin::moderation.queue.actions.dismiss'))
            ->icon(Heroicon::OutlinedXCircle)
            ->color('gray')
            ->requiresConfirmation()
            ->action(function (array $arguments): void {
                $case = ModerationCase::query()->find($arguments['caseId'] ?? null);

                $case?->update([
                    'status' => CaseStatus::Dismissed,
                    'resolved_at' => now(),
                ]);

                Notification::make()
                    ->success()
                    ->title(__('panel-admin::moderation.queue.actions.dismissed'))
                    ->send();
            });
    }
}
