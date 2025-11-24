<?php

declare(strict_types=1);

namespace Database\Seeders;

use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Models\EventModel;
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
        $user = User::query()->where('name', '=', 'admin')->first();

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
                'location' => 'Avenida Paulista, 1666, São Paulo - SP',
                'max_attendees' => 50,
                'attendees_count' => 30,
                'tenant_id' => $tenant->getKey(),
            ]);

        $this->talks($tenant, $event);
    }

    private function talks(Tenant $tenant, EventModel $event): void
    {
        $talks = [
            ['time' => '15:00', 'title' => 'Abertura e Início da Live (Twitch)'],
            ['time' => '15:30', 'title' => 'Talk Juliana Gaioso (DevSec PicPay) - Tema'],
            ['time' => '15:50', 'title' => 'Talk Fernanda Fagundes (Ipê) - Tema'],
            ['time' => '16:10', 'title' => 'Ações social I (Cestas Básicas)'],
            ['time' => '16:30', 'title' => 'Coffee Break'],
            ['time' => '17:00', 'title' => 'Talk Tatiana Barros - Tema'],
            ['time' => '17:20', 'title' => 'Talk Daniel Reis - Tema'],
            ['time' => '17:40', 'title' => 'Ações social II (Materiais Escolares)'],
            ['time' => '17:45', 'title' => 'Roda de Conversa com ?????????? - IA como ferramenta do dia a dia'],
            ['time' => '18:50', 'title' => 'Lançamento da comunidade 3 Pontos'],
            ['time' => '19:20', 'title' => 'Hunting de Oportunidades para Comunidade'],
            ['time' => '20:00', 'title' => 'Encerramento e Agradecimentos'],
        ];

        foreach ($talks as $item) {
            Talk::factory()
                ->recycle($tenant)
                ->recycle($event)
                ->create([
                    'status' => TalkStatusEnum::Accepted,
                    'field_type' => 'schedule',
                    'title' => $item['title'],
                    'description' => $item['title'],
                    'starts_at' => Date::parse($item['time']),
                    'ends_at' => Date::parse($item['time']),
                ]);
        }
    }
}
