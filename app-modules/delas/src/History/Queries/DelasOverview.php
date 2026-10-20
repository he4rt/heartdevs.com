<?php

declare(strict_types=1);

namespace He4rt\Delas\History\Queries;

use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use He4rt\Delas\Block\Models\DelasRequesterBlock;
use He4rt\Delas\History\Enums\DelasAction;
use He4rt\Delas\History\Models\DelasTransition;
use He4rt\Delas\TagRequest\Enums\DelasRequestStatus;
use He4rt\Delas\TagRequest\Models\DelasTagRequest;

/**
 * Números da He4rt Delas para líderes e super admins.
 */
final readonly class DelasOverview
{
    public function pendingCount(): int
    {
        return DelasTagRequest::query()->pending()->count();
    }

    public function oldestPendingAt(): ?CarbonInterface
    {
        $oldest = DelasTagRequest::query()->pending()->min('requested_at');

        return $oldest === null ? null : CarbonImmutable::parse($oldest);
    }

    public function membersCount(): int
    {
        return DelasTagRequest::query()->where('status', DelasRequestStatus::Approved)->count();
    }

    /**
     * Horas, em média, entre o pedido e a decisão da moderação (aprovar ou
     * rejeitar) nos últimos dias. Concessões diretas não entram: não houve
     * espera.
     */
    public function averageDecisionHours(int $days = 90): ?float
    {
        $seconds = DelasTransition::query()
            ->join('delas_tag_requests', 'delas_tag_requests.id', '=', 'delas_transitions.request_id')
            ->whereIn('delas_transitions.action', [DelasAction::Approved, DelasAction::Rejected])
            ->where('delas_transitions.created_at', '>=', now()->subDays($days))
            ->selectRaw('avg(extract(epoch from (delas_transitions.created_at - delas_tag_requests.requested_at))) as seconds')
            ->value('seconds');

        return $seconds === null ? null : (float) $seconds / 3_600;
    }

    /**
     * @return array{approved: int, rejected: int}
     */
    public function decisionsSince(CarbonInterface $since): array
    {
        $counts = DelasTransition::query()
            ->where('created_at', '>=', $since)
            ->whereIn('action', [DelasAction::Approved, DelasAction::Granted, DelasAction::Rejected])
            ->selectRaw('action, count(*) as total')
            ->groupBy('action')
            ->pluck('total', 'action');

        return [
            'approved' => (int) ($counts[DelasAction::Approved->value] ?? 0) + (int) ($counts[DelasAction::Granted->value] ?? 0),
            'rejected' => (int) ($counts[DelasAction::Rejected->value] ?? 0),
        ];
    }

    public function activeBlocksCount(): int
    {
        return DelasRequesterBlock::query()->active()->count();
    }
}
