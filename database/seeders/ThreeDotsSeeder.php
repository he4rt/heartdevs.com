<?php

declare(strict_types=1);

namespace Database\Seeders;

use He4rt\Events\Enums\EventTypeEnum;
use He4rt\Events\Enums\Talks\TalkStatusEnum;
use He4rt\Events\Models\EventModel;
use He4rt\Events\Models\EventSegment;
use He4rt\Events\Models\EventSubmission;
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
                'attendees_count' => 0,
                'tenant_id' => $tenant->getKey(),
            ]);

        $this->talks($tenant, $event);
    }

    private function talks(Tenant $tenant, EventModel $event): void
    {
        $agenda = [
            [
                'title' => 'Abertura e Início da Live',
                'description' => 'twitch',
                'start' => '2025-11-26 15:00:00',
                'end' => '2025-11-26 15:00:00',
                'speakers' => [],
                'type' => 'segment', // SchedulableTypeEnum::Segment
            ],
            [
                'title' => 'Lançamento da comunidade 3 Pontos',
                'description' => 'Lançamento 3 pontos',
                'start' => '2025-11-26 18:10:00',
                'end' => '2025-11-26 20:00:00',
                'speakers' => [],
                'type' => 'segment',
            ],
            [
                'title' => 'Killing the Vibecoding',
                'description' => 'Por mais de quinze anos, solucionando problemas na indústria através de automação, IoT e desenvolvimento de software. Atualmente focada em solucionar problemas de software de segurança.',
                'start' => '2025-11-26 15:30:00',
                'end' => '2025-11-26 15:30:00',
                'speakers' => ['Juliana Gaioso'],
                'type' => 'submission', // SchedulableTypeEnum::Submission
                'field_type' => 'twitch',
            ],
            [
                'title' => 'Encerramento e Agradecimentos',
                'description' => 'Agradecimentos',
                'start' => '2025-11-26 20:00:00',
                'end' => '2025-11-26 20:10:00',
                'speakers' => [],
                'type' => 'segment',
            ],
            [
                'title' => 'Fire|ce com Stefano',
                'description' => 'Talk da Fire/ce',
                'start' => '2025-11-26 15:50:00',
                'end' => '2025-11-26 15:50:00',
                'speakers' => ['Stefano'],
                'type' => 'submission',
                'field_type' => 'Diretor de Key Acc (Fire|ce)',
            ],
            [
                'title' => 'Coffee Break',
                'description' => 'Contexto da comunidade',
                'start' => '2025-11-26 16:20:00',
                'end' => '2025-11-26 17:00:00',
                'speakers' => [],
                'type' => 'segment',
            ],
            [
                'title' => 'Hunting de Oportunidades para Comunidade',
                'description' => 'Hunting de Oportunidades para Comunidade',
                'start' => '2025-11-26 17:00:00',
                'end' => '2025-11-26 17:20:00',
                'speakers' => ['Dulce'],
                'type' => 'submission',
                'field_type' => 'Bethel',
            ],
            [
                'title' => 'Comunidade é FODA!',
                'description' => 'Linha de frente na criação de softwares e fortalecendo comunidades dev. DevRel focado em conteúdo técnico, live coding e educação, sempre impulsionando novos talentos. Fundador da He4rt Developers e apaixonado por ensinar, programar e construir espaços onde desenvolvedores crescem juntos.',
                'start' => '2025-11-26 17:20:00',
                'end' => '2025-11-26 17:40:00',
                'speakers' => ['Daniel Reis'],
                'type' => 'submission',
                'field_type' => 'Fundador da He4rt Developers',
            ],
            [
                'title' => 'IA como ferramenta do dia a dia',
                'description' => 'IA como ferramenta do dia a dia',
                'start' => '2025-11-26 17:40:00',
                'end' => '2025-11-26 18:10:00',
                'speakers' => ['Tatiana Barros', 'Eduardo Vogel', 'Kimura'],
                'type' => 'submission',
                'field_type' => 'Painel de Discussão',
            ],
        ];

        foreach ($agenda as $item) {

            if ($item['speakers'] !== []) {
                $speakers = [];
                foreach ($item['speakers'] as $speaker) {
                    $speakers[] = User::query()->firstOrCreate([
                        'name' => $speaker,
                    ], [
                        'username' => str($speaker)->slug()->prepend('speaker-'),
                    ]);
                }

                $speakers[0]->addMediaFromUrl('https://github.com/danielhe4rt.png')
                    ->toMediaCollection('avatar');

                $eventSubmission = EventSubmission::factory()
                    ->recycle($tenant)
                    ->recycle($event)
                    ->afterCreating(
                        fn (EventSubmission $submission) => $submission->speakers()->attach($speakers)
                    )
                    ->for($speakers[0], 'user')
                    ->create([
                        'tenant_id' => $tenant->getKey(),
                        'status' => TalkStatusEnum::Accepted,
                        'field_type' => $item['field_type'],
                        'title' => $item['title'],
                        'description' => $item['description'],
                    ]);

                $event->agenda()->create([
                    'tenant_id' => $tenant->getKey(),
                    'schedulable_type' => EventSubmission::class,
                    'schedulable_id' => $eventSubmission->getKey(),
                    'starting_at' => Date::parse($item['start']),
                    'ending_at' => Date::parse($item['end']),
                ]);
            } else {
                $event->agenda()->create([
                    'tenant_id' => $tenant->getKey(),
                    'schedulable_type' => EventSegment::class,
                    'schedulable_id' => EventSegment::query()->inRandomOrder()->first()->getKey(),
                    'starting_at' => Date::parse($item['start']),
                    'ending_at' => Date::parse($item['end']),
                ]);
            }
        }
    }
}
