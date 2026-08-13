<?php

declare(strict_types=1);

namespace Database\Seeders;

use Carbon\CarbonImmutable;
use He4rt\Community\UpcomingEvent\Enums\UpcomingEventCategory;
use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;
use Illuminate\Database\Seeder;

final class UpcomingEventSeeder extends Seeder
{
    public function run(): void
    {
        $events = [
            [
                'title' => 'Reunião Semanal',
                'description' => 'Encontro semanal da comunidade para tirar dúvidas, apresentar projetos e trocar ideias.',
                'category' => UpcomingEventCategory::ReuniaoSemanal,
                'week_day' => 1,
                'time' => '21:00',
                'sort_order' => 1,
            ],
            [
                'title' => 'Aula Livre',
                'description' => 'Aula aberta toda quarta-feira sobre temas escolhidos pela comunidade.',
                'category' => UpcomingEventCategory::Aula,
                'week_day' => 3,
                'time' => '19:00',
                'sort_order' => 2,
            ],
            [
                'title' => 'Aula de Inglês',
                'description' => 'Aula de inglês para devs, todo sábado às 15h.',
                'category' => UpcomingEventCategory::AulaIngles,
                'week_day' => 6,
                'time' => '15:00',
                'sort_order' => 3,
            ],
            [
                'title' => 'Aula de Onboarding',
                'description' => 'Boas-vindas e apresentação da comunidade para novos membros, todo início de mês.',
                'category' => UpcomingEventCategory::Onboarding,
                'event_at' => now()->addMonth()->startOfMonth()->setTime(19, 0),
                'sort_order' => 4,
            ],
            [
                'title' => 'Encontro Presencial de Pub',
                'description' => 'Encontro presencial da galera para networking e resenha num pub.',
                'category' => UpcomingEventCategory::Networking,
                'event_at' => CarbonImmutable::parse('2026-08-28 20:00:00'),
                'location' => 'Pub',
                'external_url' => 'https://discord.gg/he4rt',
                'sort_order' => 5,
            ],
        ];

        foreach ($events as $event) {
            UpcomingEvent::query()->updateOrCreate(
                ['title' => $event['title']],
                $event,
            );
        }
    }
}
