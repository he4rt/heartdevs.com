<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages\Location\Queries;

use App\Geo\Support\GeoLocation;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Aggregates community members by Brazilian state, reading the cumulative
 * `addresses` snapshot owned by the identity domain. The canonical state set
 * comes from the same geo source the profile uses (`GeoLocation`); stored names
 * are reconciled against it by an accent-insensitive normalized name, which is
 * also how the Blade view joins the counts to the map geometry.
 */
final readonly class MembersByState
{
    private const string BRAZIL_ISO3 = 'BRA';

    /**
     * @return array{
     *     total: int,
     *     states_reached: int,
     *     states_total: int,
     *     by_name: array<string, int>,
     *     top: array<int, array{name: string, members: int, share: float}>,
     * }
     */
    public function get(): array
    {
        return once(function (): array {
            $canonical = [];
            foreach (GeoLocation::statesFor(self::BRAZIL_ISO3) as $name) {
                $canonical[$this->normalize($name)] = $name;
            }

            /** @var Collection<int, object{state: string, members: int}> $rows */
            $rows = DB::table('addresses')
                ->where('addressable_type', 'user')
                ->whereNotNull('state')
                ->where('state', '!=', '')
                ->select('state', DB::raw('COUNT(*) AS members'))
                ->groupBy('state')
                ->get();

            $counts = [];
            foreach ($rows as $row) {
                $key = $this->normalize((string) $row->state);

                if (!isset($canonical[$key])) {
                    continue;
                }

                $counts[$key] = ($counts[$key] ?? 0) + (int) $row->members;
            }

            arsort($counts);

            $total = array_sum($counts);

            $top = [];
            foreach (array_slice($counts, 0, 5, preserve_keys: true) as $key => $members) {
                $top[] = [
                    'name' => $canonical[$key],
                    'members' => $members,
                    'share' => $total > 0 ? round($members / $total * 100, 1) : 0.0,
                ];
            }

            return [
                'total' => $total,
                'states_reached' => count($counts),
                'states_total' => count($canonical),
                'by_name' => $counts,
                'top' => $top,
            ];
        });
    }

    private function normalize(string $name): string
    {
        return Str::of($name)->ascii()->lower()->squish()->value();
    }
}
