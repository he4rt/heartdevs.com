<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\UpcomingEvent\Enums\UpcomingEventCategory;
use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;
use He4rt\Portal\Livewire\UpcomingEventsSection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->withoutVite();
    app()->setLocale('pt_BR');
});

it('exibe apenas eventos ativos com próxima ocorrência futura, ordenados por data', function (): void {
    UpcomingEvent::factory()->create([
        'title' => 'Reunião Semanal',
        'category' => UpcomingEventCategory::ReuniaoSemanal,
        'week_day' => 1,
        'time' => '21:00',
    ]);

    UpcomingEvent::factory()->oneOff()->create([
        'title' => 'Encontro de Pub',
        'category' => UpcomingEventCategory::Networking,
        'event_at' => CarbonImmutable::now()->addDays(10),
        'location' => 'Pub',
    ]);

    UpcomingEvent::factory()->create([
        'title' => 'Evento Inativo',
        'is_active' => false,
    ]);

    UpcomingEvent::factory()->oneOff()->create([
        'title' => 'Evento Passado',
        'event_at' => CarbonImmutable::now()->subDay(),
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Reunião Semanal')
        ->assertSee('Encontro de Pub')
        ->assertDontSee('Evento Inativo')
        ->assertDontSee('Evento Passado');
});

it('ordena os eventos pela próxima ocorrência', function (): void {
    UpcomingEvent::factory()->oneOff()->create([
        'title' => 'Encontro de Pub',
        'category' => UpcomingEventCategory::Networking,
        'event_at' => CarbonImmutable::now()->addDays(20),
    ]);

    UpcomingEvent::factory()->create([
        'title' => 'Aula de Inglês',
        'category' => UpcomingEventCategory::AulaIngles,
        'week_day' => 6,
        'time' => '15:00',
    ]);

    $component = livewire(UpcomingEventsSection::class);

    /** @var Collection<int, array{event: UpcomingEvent, occurrence: CarbonImmutable}> $events */
    $events = $component->get('upcomingEvents');

    expect($events)->toHaveCount(2)
        ->and($events->pluck('event.title')->values()->all())->toBe(['Aula de Inglês', 'Encontro de Pub']);
});

it('renderiza a mensagem de fallback quando não há eventos futuros', function (): void {
    UpcomingEvent::factory()->oneOff()->create([
        'event_at' => CarbonImmutable::now()->subDay(),
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Nenhum evento agendado no momento')
        ->assertDontSee('events-carousel');
});

it('renderiza badge de data e placeholder He4rt quando o evento não tem capa', function (): void {
    UpcomingEvent::factory()->create([
        'title' => 'Aula de Inglês',
        'week_day' => 6,
        'time' => '15:00',
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Aula de Inglês')
        ->assertSee('landingLogo.svg', escape: false)
        ->assertSee('Online')
        ->assertSee('id="agenda"', escape: false);
});

it('marca eventos recorrentes com badge e padrão de recorrência', function (): void {
    UpcomingEvent::factory()->create([
        'title' => 'Reunião Semanal',
        'week_day' => 1,
        'time' => '21:00',
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Recorrente')
        ->assertSee('Toda Seg')
        ->assertSee('21:00');
});

it('exibe badge Presencial quando o evento tem local', function (): void {
    UpcomingEvent::factory()->oneOff()->create([
        'title' => 'Encontro de Pub',
        'category' => UpcomingEventCategory::Networking,
        'event_at' => CarbonImmutable::now()->addDays(10),
        'location' => 'Pub',
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Presencial')
        ->assertDontSee('Online');
});

it('renderiza a linha do anfitrião com nome e cargo no card', function (): void {
    UpcomingEvent::factory()->create([
        'title' => 'Reunião Semanal',
        'week_day' => 1,
        'time' => '21:00',
        'host_name' => 'Fernando',
        'host_role' => 'Mentor',
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Fernando')
        ->assertSee('Mentor');
});

it('renderiza o fallback quando não há eventos futuros', function (): void {
    UpcomingEvent::factory()->oneOff()->create([
        'event_at' => CarbonImmutable::now()->subDay(),
    ]);

    livewire(UpcomingEventsSection::class)
        ->assertSee('Nenhum evento agendado no momento')
        ->assertDontSee('events-carousel');
});

it('inclui dados estruturados JSON-LD na home quando existem eventos', function (): void {
    UpcomingEvent::factory()->create([
        'title' => 'Reunião Semanal',
        'week_day' => 1,
        'time' => '21:00',
    ]);

    get('/')
        ->assertOk()
        ->assertSee('application/ld+json', escape: false)
        ->assertSee('Reunião Semanal');
});

it('renderiza a capa do evento no card com atributos de SEO', function (): void {
    Storage::fake('public');

    $event = UpcomingEvent::factory()->create([
        'title' => 'Hacktoberfest 2026',
        'week_day' => 3,
        'time' => '20:00',
    ]);

    $event->addMediaFromString('fake cover bytes')
        ->usingFileName('cover.png')
        ->usingName('Hacktoberfest 2026')
        ->toMediaCollection('cover');

    livewire(UpcomingEventsSection::class)
        ->assertSee('Capa do evento Hacktoberfest 2026')
        ->assertSee('loading="lazy"', escape: false)
        ->assertSee('fetchpriority="low"', escape: false)
        ->assertSee('<h2', escape: false)
        ->assertSee('"image"', escape: false)
        ->assertSee('"url"', escape: false);
});
