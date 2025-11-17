<?php

declare(strict_types=1);

namespace Database\Seeders;

use Illuminate\Support\Facades\Date;
use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\Talk;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Seeder;

class ThreeDotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::query()->where('name', '=', 'admin')->first();

        $tenant = Tenant::factory()
            ->for($user, 'owner')
            ->afterCreating(fn (Tenant $tenant) => $tenant->members()->attach($user))
            ->create([
                'name' => '3 Pontos',
                'slug' => '3-pontos',
            ]);

        $event = EventModel::factory()
            ->withStatus()
            ->create([
                'title' => 'Evento Da Três Pontos',
                'event_type' => EventTypeEnum::Workshop,
                'slug' => '3-pontos-evento',
                'description' => 'Participe do primeiro evento presencial da 3pontos',
                'event_at' => Date::createFromFormat('m-d-Y h:i:s A', '11-29-2025 02:00:00 PM'),
                'start_at' => Date::createFromFormat('m-d-Y h:i:s A', '11-29-2025 02:00:00 PM'),
                'end_at' => Date::createFromFormat('m-d-Y h:i:s A', '11-29-2025 07:00:00 PM'),
                'location' => 'Avenida Paulista, 1666, São Paulo - SP',
                'max_attendees' => 50,
                'attendees_count' => 30,
                'tenant_id' => $tenant->getKey(),
            ]);

        Talk::factory()
            ->recycle($tenant)
            ->recycle($event)
            ->count(4);
    }
}
