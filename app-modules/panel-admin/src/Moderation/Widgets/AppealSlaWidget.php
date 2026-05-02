<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Moderation\Widgets;

use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget;
use He4rt\Moderation\Appeals\ModerationAppeal;

class AppealSlaWidget extends TableWidget
{
    protected int|string|array $columnSpan = 1;

    public function getHeading(): string
    {
        return __('panel-admin::moderation.dashboard.appeal_sla.heading');
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                ModerationAppeal::query()
                    ->with(['appellant', 'reviewer'])
                    ->whereIn('status', ['pending', 'reviewing'])
                    ->orderBy('sla_deadline')
            )
            ->columns([
                IconColumn::make('sla_status')
                    ->label('')
                    ->state(fn (ModerationAppeal $record): string => $record->sla_deadline->isFuture() ? 'on_track' : 'overdue')
                    ->icon(fn (string $state): Heroicon => $state === 'on_track' ? Heroicon::MiniCheckCircle : Heroicon::MiniExclamationCircle)
                    ->color(fn (string $state): string => $state === 'on_track' ? 'success' : 'danger'),
                TextColumn::make('id')
                    ->label('Appeal')
                    ->formatStateUsing(fn (string $state): string => '#'.mb_substr($state, 0, 8)),
                TextColumn::make('appellant.username')
                    ->label(__('panel-admin::moderation.appeal_queue.detail.appellant'))
                    ->formatStateUsing(fn (?string $state): string => $state ? '@'.$state : '—'),
                TextColumn::make('reviewer.username')
                    ->label(__('panel-admin::moderation.appeal_queue.detail.action_moderator'))
                    ->formatStateUsing(fn (?string $state): string => $state ? '@'.$state : '—'),
                TextColumn::make('sla_deadline')
                    ->label('SLA')
                    ->formatStateUsing(function (ModerationAppeal $record): string {
                        $hours = (int) abs(now()->diffInHours($record->sla_deadline));

                        return $record->sla_deadline->isFuture()
                            ? __('panel-admin::moderation.dashboard.appeal_sla.remaining', ['hours' => $hours])
                            : __('panel-admin::moderation.dashboard.appeal_sla.overdue', ['hours' => $hours]);
                    }),
            ])
            ->paginated(false);
    }

    protected function getTableDescription(): ?string
    {
        $startOfMonth = now()->startOfMonth();

        $resolvedInSla = ModerationAppeal::query()
            ->where('resolved_at', '>=', $startOfMonth)
            ->whereNotNull('resolved_at')
            ->whereColumn('resolved_at', '<=', 'sla_deadline')
            ->count();

        $totalResolved = ModerationAppeal::query()
            ->where('resolved_at', '>=', $startOfMonth)
            ->whereNotNull('resolved_at')
            ->count();

        $rate = $totalResolved > 0 ? round(($resolvedInSla / $totalResolved) * 100) : 100;

        return __('panel-admin::moderation.dashboard.appeal_sla.compliance', [
            'count' => $resolvedInSla,
            'rate' => $rate,
        ]);
    }
}
