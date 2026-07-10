<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Location\Queries;

use Carbon\CarbonInterface;
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

    /**
     * @return array{
     *     web_active: int,
     *     web_active_prev: int,
     *     discord_active: int,
     *     discord_active_prev: int,
     *     located_members: int,
     *     total_members: int,
     *     states_reached: int,
     *     states_total: int,
     * }
     */
    public function get(): array
    {
        return once(function (): array {
            [$currentStart, $currentEnd, $prevStart, $prevEnd] = $this->windows();

            $byState = new MembersByState()->get();

            return [
                'web_active' => $this->distinctTimelineUsers($currentStart, $currentEnd),
                'web_active_prev' => $this->distinctTimelineUsers($prevStart, $prevEnd),
                'discord_active' => $this->distinctMessageIdentities($currentStart, $currentEnd),
                'discord_active_prev' => $this->distinctMessageIdentities($prevStart, $prevEnd),
                'located_members' => $this->locatedMembers(),
                'total_members' => $this->totalMembers(),
                'states_reached' => $byState['states_reached'],
                'states_total' => $byState['states_total'],
            ];
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
