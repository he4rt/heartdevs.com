<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Location\Queries;

use Carbon\CarbonInterface;
use He4rt\PanelAdmin\Marketing\Pages\Location\Data\CommunityActivitySnapshot;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

/**
 * The headline numbers for the location dashboard's stat widget: web and Discord
 * actives (current window vs the preceding one), location coverage, and how many
 * states the community reaches.
 */
final readonly class CommunityActivityStats
{
    public function __construct(
        private int $rangeDays,
    ) {}

    public function get(): CommunityActivitySnapshot
    {
        return once(function (): CommunityActivitySnapshot {
            [$currentStart, $currentEnd, $prevStart, $prevEnd] = $this->windows();

            $byState = new MembersByState()->get();

            return new CommunityActivitySnapshot(
                webActive: $this->distinctTimelineUsers($currentStart, $currentEnd),
                webActivePrev: $this->distinctTimelineUsers($prevStart, $prevEnd),
                discordActive: $this->distinctMessageIdentities($currentStart, $currentEnd),
                discordActivePrev: $this->distinctMessageIdentities($prevStart, $prevEnd),
                locatedMembers: $this->locatedMembers(),
                totalMembers: $this->totalMembers(),
                statesReached: $byState->statesReached,
                statesTotal: $byState->statesTotal,
            );
        });
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: CarbonInterface, 3: CarbonInterface}
     */
    private function windows(): array
    {
        $now = Date::now(config('app.display_timezone'));

        return [
            $now->copy()->subDays($this->rangeDays)->utc(),
            $now->copy()->utc(),
            $now->copy()->subDays($this->rangeDays * 2)->utc(),
            $now->copy()->subDays($this->rangeDays)->utc(),
        ];
    }

    private function distinctTimelineUsers(CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) DB::table('activity_timeline')
            ->whereBetween('created_at', [$start, $end])
            ->distinct()
            ->count('user_id');
    }

    private function distinctMessageIdentities(CarbonInterface $start, CarbonInterface $end): int
    {
        return (int) DB::table('messages')
            ->whereNotNull('sent_at')
            ->whereBetween('sent_at', [$start, $end])
            ->distinct()
            ->count('external_identity_id');
    }

    private function locatedMembers(): int
    {
        return (int) DB::table('addresses')
            ->where('addressable_type', 'user')
            ->whereNotNull('state')
            ->where('state', '!=', '')
            ->distinct()
            ->count('addressable_id');
    }

    private function totalMembers(): int
    {
        return (int) DB::table('users')->count();
    }
}
