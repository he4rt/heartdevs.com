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
            ['time' => '15:00', 'speaker' => $event->slug, 'title' => 'Abertura e Início da Live (Twitch)', 'field_type' => 'twitch', 'description' => 'twitch'],
            ['time' => '15:05', 'speaker' => 'Filipe Augusto', 'title' => '3 Pontos com Filipe', 'field_type' => 'CEO 3 Pontos', 'description' => 'A 3 Pontos é uma aceleradora financeira que conecta pessoas e empresas a diagnósticos estratégicos, soluções de investimento e tecnologia de gestão.'],
            ['time' => '15:05', 'speaker' => 'Joy', 'title' => '3 Pontos com Joy', 'field_type' => 'CMO 3 Pontos', 'description' => 'A 3 Pontos é uma aceleradora financeira que conecta pessoas e empresas a diagnósticos estratégicos, soluções de investimento e tecnologia de gestão.'],
            ['time' => '15:10', 'speaker' => 'Fernanda Fagundes', 'title' => 'Talk Fernanda Fagundes -  (Ipê)', 'field_type' => 'Ipê', 'description' => 'Talk da Fefa'],
            ['time' => '15:10', 'speaker' => $event->slug, 'title' => 'Ações social I (Cestas Básicas)', 'field_type' => '3pontos', 'description' => 'Ação Social I'],
            ['time' => '15:30', 'speaker' => 'Juliana Gaioso', 'title' => 'Talk Juliana Gaioso (DevSec PicPay) - Tema', 'field_type' => 'Devsec', 'description' => 'Por mais de quinze anos, solucionando problemas na indústria através de automação, IoT e desenvolvimento de software. Atualmente focada em solucionar problemas de software de segurança.'],
            ['time' => '15:50', 'speaker' => 'Oka', 'title' => 'Fire/ce com Oka', 'field_type' => 'Fire/ce', 'description' => 'Talk da Fire/ce'],
            ['time' => '15:50', 'speaker' => 'Stefano Piucci', 'title' => 'Fire/ce com Stefano', 'field_type' => 'Diretor de Key Acc (Fire/ce)', 'description' => 'Talk da Fire/ce'],
            ['time' => '16:10', 'speaker' => $event->slug, 'title' => 'Aquecimento da Nova Marca', 'field_type' => '3pontos', 'description' => 'Contexto da comunidade'],
            ['time' => '16:50', 'speaker' => 'Dulce', 'title' => 'Hunting de Oportunidades para Comunidade', 'field_type' => 'Bethel', 'description' => 'Hunting de Oportunidades para Comunidade'],
            ['time' => '17:10', 'speaker' => $event->slug, 'title' => 'Sorteio', 'field_type' => 'sorteio', 'description' => 'Sorteio 1.'],
            ['time' => '17:20', 'speaker' => 'Daniel Reis', 'title' => 'Talk Daniel Reis - Tema', 'field_type' => 'Tech Lead & Fundador da He4rt Developers', 'description' => 'Linha de frente na criação de softwares e fortalecendo comunidades dev. DevRel focado em conteúdo técnico, live coding e educação, sempre impulsionando novos talentos. Fundador da He4rt Developers e apaixonado por ensinar, programar e construir espaços onde desenvolvedores crescem juntos.'],
            ['time' => '17:40', 'speaker' => $event->slug, 'title' => 'Roda de Conversa - IA como ferramenta do dia a dia', 'field_type' => 'IA', 'description' => 'IA como ferramenta do dia a dia'],
            ['time' => '17:40', 'speaker' => 'Eduardo Vogel', 'title' => 'Roda de Conversa - Eduardo Vogel', 'field_type' => 'Business Development & Strategic Partnerships/ IT Project Manager', 'description' => 'IA como ferramenta do dia a dia'],
            ['time' => '17:50', 'speaker' => 'Juliano Kimura', 'title' => 'Roda de Conversa - Juliano Kimura ', 'field_type' => 'Palestrante, Creative Thinker, Transformador Digital', 'description' => 'Foi palestrante e especialista no Facebook Brasil. Eleito duas vezes Melhor profissional de redes sociais pela ABcomm Professor há 5 anos na Comschool. '],
            ['time' => '18:00', 'speaker' => 'Tatiana Barros', 'title' => 'Roda de Conversa - Tatiana Barros', 'field_type' => 'Technology Evangelist', 'description' => 'Há mais de uma década unindo tecnologia, criatividade e impacto social. Evangelista de Tecnologia focada em fortalecer comunidades dev e ampliar o acesso à educação tecnológica por meio de workshops, mentorias e iniciativas premiadas.'],
            ['time' => '18:10', 'speaker' => $event->slug, 'title' => 'Intervalo', 'field_type' => '3 pontos', 'description' => 'Intervalo Técnico'],
            ['time' => '18:30', 'speaker' => $event->slug, 'title' => 'Lançamento da comunidade 3 Pontos', 'field_type' => '3 pontos', 'description' => 'Lançamento 3 pontos'],
            ['time' => '19:00', 'speaker' => $event->slug, 'title' => 'Encerramento e Agradecimentos', 'field_type' => '3pontos', 'description' => 'Agradecimentos'],
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
                    'starts_at' => Date::parse($item['time']),
                    'ends_at' => Date::parse($item['time']),
                ]);
        }
    }
}
