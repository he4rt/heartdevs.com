<?php

declare(strict_types=1);

namespace He4rt\Gamification\Database\Seeders;

use He4rt\Gamification\Season\Models\Season;
use Illuminate\Database\Seeder;

final class SeasonSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        foreach (config('he4rt.seasons') as $season) {
            Season::query()->create($season);
        }
    }
}
