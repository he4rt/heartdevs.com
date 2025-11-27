<?php

declare(strict_types=1);

namespace Database\Seeders;

use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Models\EventAgenda;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\EventSegment;
use He4rt\Events\Models\Talk;
use He4rt\Tenant\Models\Tenant;
use He4rt\User\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Date;

class ThreeDotsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (app()->isProduction()) {
            $user = User::query()->where('username', 'danielhe4rt')->first();
        } else {
            $user = User::query()->where('name', 'admin')->first();
        }

        $tenant = Tenant::factory()
            ->for($user, 'owner')
            ->afterCreating(fn (Tenant $tenant) => $tenant->members()->attach($user))
            ->create([
                'name' => '3 Pontos',
                'slug' => '3pontos',
            ]);

        $event = EventModel::factory()
            ->withStatus()
            ->create([
                'title' => 'Participe do primeiro evento presencial da 3Pontos',
                'event_type' => EventTypeEnum::Workshop,
                'slug' => '3-pontos-evento',
                'description' => 'Um evento híbrido de 5 horas, em parceria com a He4rt, com palestras exclusivas, networking e uma missão
                social que transforma.',
                'event_at' => Date::createFromFormat('m-d-Y h:i:s A', '11-29-2025 02:00:00 PM'),
                'start_at' => Date::createFromFormat('m-d-Y h:i:s A', '11-29-2025 02:00:00 PM'),
                'end_at' => Date::createFromFormat('m-d-Y h:i:s A', '11-29-2025 07:00:00 PM'),
                'location' => 'Alameda Santos, 1163 — Jardim Paulista, São Paulo — SP, 01419-002',
                'max_attendees' => 50,
                'attendees_count' => 30,
                'tenant_id' => $tenant->getKey(),
            ]);

        $this->talks($tenant, $event);
    }

    private function talks(Tenant $tenant, EventModel $event): void
    {
        $talks = [
            ['speaker' => 'Juliana Cardoso', 'title' => "'Killing the Vibecoding  //  Juliana Gaioso \'", 'field_type' => 'twitch', 'description' => 'Por mais de quinze anos, solucionando problemas na indústria através de automação, IoT e desenvolvimento de software. Atualmente focada em solucionar problemas de software de segurança.'],
            ['speaker' => 'Stefano', 'title' => 'FIRE|CE com Stefano', 'field_type' => 'Diretor de Key Acc (Fire|ce)', 'description' => 'Talk da Fire|ce'],
            ['speaker' => 'Dulce', 'title' => 'Hunting de Oportunidades para Comunidade', 'field_type' => 'Bethel', 'description' => 'Hunting de Oportunidades para Comunidade'],
            ['speaker' => 'Daniel Reis', 'title' => "'Comunidade é FODA! // Daniel Reis \'", 'field_type' => 'Tech Lead & Fundador da He4rt Developers', 'description' => 'Linha de frente na criação de softwares e fortalecendo comunidades dev. DevRel focado em conteúdo técnico, live coding e educação, sempre impulsionando novos talentos. Fundador da He4rt Developers e apaixonado por ensinar, programar e construir espaços onde desenvolvedores crescem juntos.'],
        ];

        foreach ($talks as $item) {

            $speaker = User::query()->firstOrCreate([
                'name' => $item['speaker'],
            ]);

            $speaker->addMediaFromUrl('https://github.com/danielhe4rt.png')
                ->toMediaCollection('avatar');

            Talk::factory()
                ->recycle($tenant)
                ->recycle($event)
                ->for($speaker, 'user')
                ->create([
                    'status' => TalkStatusEnum::Accepted,
                    'field_type' => $item['field_type'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                ]);

            $talks = Talk::all()->toArray();

            $segments = EventSegment::all()->toArray();

            $this->eventAgenda($tenant, $event);

        }
    }

    private function eventAgenda(Tenant $tenant, EventModel $event): void
    {
        EventAgenda::factory()
            ->recycle($tenant)
            ->recycle($event)
            ->create();
    }
}
