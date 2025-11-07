<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use He4rt\Meeting\Models\MeetingType;
use He4rt\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

final class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // \App\Models\User::factory(10)->create();

        User::factory()->create([
            'username' => 'admin',
            'name' => 'admin',
            'email' => 'admin@admin.com',
            'password' => Hash::make('admin'),
        ]);

        MeetingType::query()->create([
            'name' => 'Reunião Semanal',
            'week_day' => 1,
            'start_at' => '20:30',
        ]);

        MeetingType::query()->create([
            'name' => 'Reunião Semanal',
            'week_day' => 2,
            'start_at' => '20:00',
        ]);
    }
}
