<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Community\UpcomingEvent\Enums\UpcomingEventCategory;
use He4rt\Community\UpcomingEvent\Models\UpcomingEvent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

afterEach(function (): void {
    CarbonImmutable::setTestNow();
});

it('calcula a próxima ocorrência de um evento recorrente semanal', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 10:00:00'));

    $event = UpcomingEvent::factory()->create([
        'week_day' => 3,
        'time' => '19:00',
    ]);

    expect($event->nextOccurrence()->toDateTimeString())->toBe('2026-08-12 19:00:00');
});

it('avança para a próxima semana quando a ocorrência do dia já passou', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 20:00:00'));

    $event = UpcomingEvent::factory()->create([
        'week_day' => 3,
        'time' => '19:00',
    ]);

    expect($event->nextOccurrence()->toDateTimeString())->toBe('2026-08-19 19:00:00');
});

it('retorna o próximo dia da semana para eventos recorrentes', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 10:00:00'));

    $event = UpcomingEvent::factory()->create([
        'week_day' => 1,
        'time' => '21:00',
    ]);

    expect($event->nextOccurrence()->toDateTimeString())->toBe('2026-08-17 21:00:00');
});

it('pula uma semana quando skip_next_occurrence está ativo', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 10:00:00'));

    $event = UpcomingEvent::factory()->create([
        'week_day' => 3,
        'time' => '19:00',
        'skip_next_occurrence' => true,
    ]);

    expect($event->nextOccurrence()->toDateTimeString())->toBe('2026-08-19 19:00:00');
});

it('consome o skip após a ocorrência pulada ter passado', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 10:00:00'));

    $event = UpcomingEvent::factory()->create([
        'week_day' => 3,
        'time' => '19:00',
        'skip_next_occurrence' => true,
    ]);

    expect($event->skip_until->toDateTimeString())->toBe('2026-08-12 19:00:00');

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00'));

    expect($event->nextOccurrence()->toDateTimeString())->toBe('2026-08-19 19:00:00');
});

it('limpa o skip na próxima gravação após a ocorrência ter passado', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 10:00:00'));

    $event = UpcomingEvent::factory()->create([
        'week_day' => 3,
        'time' => '19:00',
        'skip_next_occurrence' => true,
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00'));

    $event->save();

    expect($event->skip_next_occurrence)->toBeFalse();
    expect($event->skip_until)->toBeNull();
});

it('rearma o skip para uma nova ocorrência ao ativar novamente', function (): void {
    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-12 10:00:00'));

    $event = UpcomingEvent::factory()->create([
        'week_day' => 3,
        'time' => '19:00',
        'skip_next_occurrence' => true,
    ]);

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-13 10:00:00'));

    $event->save();

    CarbonImmutable::setTestNow(CarbonImmutable::parse('2026-08-20 10:00:00'));

    $event->update(['skip_next_occurrence' => true]);

    expect($event->skip_until->toDateTimeString())->toBe('2026-08-26 19:00:00');
    expect($event->nextOccurrence()->toDateTimeString())->toBe('2026-09-02 19:00:00');
});

it('usa event_at para eventos pontuais', function (): void {
    $event = UpcomingEvent::factory()->oneOff()->create([
        'category' => UpcomingEventCategory::Networking,
        'event_at' => CarbonImmutable::parse('2026-08-28 20:00:00'),
    ]);

    expect($event->nextOccurrence()->toDateTimeString())->toBe('2026-08-28 20:00:00');
});
