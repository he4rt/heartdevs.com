<?php

declare(strict_types=1);

namespace Database\Seeders;

use He4rt\Activity\Message\Models\Message;
use He4rt\Community\Meeting\Models\Meeting;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Season\Models\Season;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class BaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = User::factory()
            ->create([
                'username' => 'danielhe4rt',
                'name' => 'Daniel Reis',
                'email' => 'admin@admin.com',
                'password' => Hash::make('admin'),
            ]);

        $he4rt = Tenant::factory()
            ->for($admin, 'owner')
            ->afterCreating(fn (Tenant $tenant) => $tenant->members()->attach($admin))
            ->withDiscordProvider('1442327864052547656')
            ->create([
                'name' => 'He4rt Developers',
                'slug' => 'he4rt',
            ]);

        Tenant::factory()
            ->for($admin, 'owner')
            ->afterCreating(fn (Tenant $tenant) => $tenant->members()->attach($admin))
            ->create([
                'name' => '3 Pontos',
                'slug' => '3pontos',
            ]);

        Character::factory()
            ->recycle($admin)
            ->recycle($he4rt)
            ->createOne();

        Season::factory()
            ->recycle($he4rt)
            ->create([
                'name' => 'Season 1',
                'started_at' => now()->subMonth(),
                'ended_at' => now()->addMonth(),
            ]);

        ExternalIdentity::factory()
            ->recycle($admin)
            ->recycle($he4rt)
            ->count(2)
            ->create(['email' => $admin->email]);

        Meeting::factory()
            ->count(2)
            ->recycle($he4rt)
            ->create();

        Message::factory()
            ->recycle($he4rt)
            ->count(2)
            ->create();
    }
}
