<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

final class ExpTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $expTable = [10, 100, 200, 300, 400, 500, 600, 700, 800, 900, 1_001, 1_200, 1_400, 1_600, 1_800, 2_000, 2_200, 2_400, 2_600, 2_800, 3_001, 3_300, 3_600, 3_900, 4_200, 4_500, 4_800, 5_100, 5_400, 5_700, 6_001, 6_400, 6_800, 7_200, 8_000, 8_400, 8_800, 9_200, 9_600, 10_000, 10_501, 11_000, 11_500, 12_000, 12_500, 13_000, 13_500, 14_000, 14_500, 15_001];

        $table = DB::table('experience_table');
        $table->truncate();

        foreach ($expTable as $value) {
            $table->insert(['required' => $value]);
        }
    }
}
