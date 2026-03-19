<?php

declare(strict_types=1);

namespace Database\Seeders;

use He4rt\Activity\Message\Models\Message;
use He4rt\Community\Meeting\Models\Meeting;
use He4rt\Events\Models\EventModel;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Gamification\Season\Models\Season;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\Tenant\Models\Tenant;
use He4rt\Identity\User\Models\Address;
use He4rt\Identity\User\Models\Information;
use He4rt\Identity\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Hash;

class BaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
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
            ->withDiscordProvider('1442327864052547656')
            ->create([
                'name' => 'He4rt Developers',
                'slug' => 'he4rt',
            ]);

        Character::factory()
            ->recycle($user)
            ->recycle($tenant)
            ->createOne();

        EventModel::factory()
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
                'ended_at' => now()->addMonth(),
            ]);

        ExternalIdentity::factory()
            ->recycle($user)
            ->recycle($tenant)
            ->count(2)
            ->create(['email' => $user->email]);
        Meeting::factory()
            ->count(2)
            ->recycle($tenant)
            ->create();

        Message::factory()
            ->recycle($tenant)
            ->count(2)
            ->create();
    }
}
