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
            ['time' => '15:00', 'speaker' => $event->slug, 'title' => 'Abertura e Início da Live (Twitch)', 'field_type' => 'twitch', 'description' => 'twitch'],
            ['time' => '15:30', 'speaker' => 'Juliana Gaioso', 'title' => 'Talk Juliana Gaioso (DevSec PicPay) - Tema', 'field_type' => 'Devsec', 'description' => 'Por mais de quinze anos, solucionando problemas na indústria através de automação, IoT e desenvolvimento de software. Atualmente focada em solucionar problemas de software de segurança.'],
            ['time' => '15:50', 'speaker' => 'Fernanda Fagundes', 'title' => 'Talk Fernanda Fagundes (Ipê) - Tema', 'field_type' => 'Lady', 'description' => 'Talk da Fefa'],
            ['time' => '16:10', 'speaker' => $event->slug, 'title' => 'Ações social I (Cestas Básicas)', 'field_type' => '3pontos', 'description' => 'Ação Social I'],
            ['time' => '16:30', 'speaker' => $event->slug, 'title' => 'Coffee Break', 'field_type' => '3pontos', 'description' => 'coffee break'],
            ['time' => '17:00', 'speaker' => 'Tatiana Barros', 'title' => 'Talk Tatiana Barros - Tema', 'field_type' => 'Technology Evangelist', 'description' => 'Há mais de uma década unindo tecnologia, criatividade e impacto social. Evangelista de Tecnologia focada em fortalecer comunidades dev e ampliar o acesso à educação tecnológica por meio de workshops, mentorias e iniciativas premiadas.'],
            ['time' => '17:20', 'speaker' => 'Daniel Reis', 'title' => 'Talk Daniel Reis - Tema', 'field_type' => 'Tech Lead & Fundador da He4rt Developers', 'description' => 'Linha de frente na criação de softwares e fortalecendo comunidades dev. DevRel focado em conteúdo técnico, live coding e educação, sempre impulsionando novos talentos. Fundador da He4rt Developers e apaixonado por ensinar, programar e construir espaços onde desenvolvedores crescem juntos.'],
            ['time' => '17:40', 'speaker' => $event->slug, 'title' => 'Ações social II (Materiais Escolares)', 'field_type' => '3pontos', 'description' => 'Ação Social II'],
            ['time' => '17:45', 'speaker' => $event->slug, 'title' => 'Roda de Conversa com ?????????? - IA como ferramenta do dia a dia', 'field_type' => 'IA', 'description' => ' ????'],
            ['time' => '18:50', 'speaker' => $event->slug, 'title' => 'Lançamento da comunidade 3 Pontos', 'field_type' => '3 pontos', 'description' => 'Lançamento 3 pontos'],
            ['time' => '19:20', 'speaker' => $event->slug, 'title' => 'Hunting de Oportunidades para Comunidade', 'field_type' => '3pontos', 'description' => 'oportunidades para comunidade'],
            ['time' => '20:00', 'speaker' => $event->slug, 'title' => 'Encerramento e Agradecimentos', 'field_type' => '3pontos', 'description' => 'Agradecimentos'],
        ];

        foreach ($talks as $item) {

            $speaker = User::query()->firstOrCreate([
                'name' => $item['speaker'],
            ]);
            Talk::factory()
                ->recycle($tenant)
                ->recycle($event)
                ->for($speaker, 'user')
                ->create([
                    'status' => TalkStatusEnum::Accepted,
                    'field_type' => $item['field_type'],
                    'title' => $item['title'],
                    'description' => $item['description'],
                    'starts_at' => Date::parse($item['time']),
                    'ends_at' => Date::parse($item['time']),
                ]);
        }
    }
}
