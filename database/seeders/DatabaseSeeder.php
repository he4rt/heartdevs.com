<?php

declare(strict_types=1);

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Date;
use He4rt\Character\Models\Character;
use He4rt\Events\Models\EventModel;
use He4rt\Season\Models\Season;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\Address;
use He4rt\User\Models\Information;
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
        $user = User::factory()
            ->create([
                'username' => 'admin',
                'name' => 'admin',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin'),
            ]);

        Information::factory()->recycle($user)->create();
        Address::factory()->recycle($user)->create();

        $tenant = Tenant::factory()
            ->for($user, 'owner')
            ->afterCreating(fn (Tenant $tenant) => $tenant->members()->attach($user))
            ->create([
                'name' => 'He4rt Developers',
                'slug' => 'he4rt',
            ]);

        Character::factory()
            ->recycle($user)
            ->recycle($tenant)
            ->createOne();

        EventModel::factory()->count(10)
            ->withStatus()
            ->recycle($tenant)
            ->create([
                'end_at' => Date::tomorrow(),
            ]);

        Season::factory()
            ->recycle($tenant)
            ->create([
                'name' => 'Season 1',
                'started_at' => now()->subMonth(),
                'ended_at' => today(),
            ]);
    }
}
