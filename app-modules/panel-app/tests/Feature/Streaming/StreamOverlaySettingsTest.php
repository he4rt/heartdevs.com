<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamOverlaysPage;
use He4rt\Streaming\Broadcasting\AlertTriggered;
use He4rt\Streaming\Broadcasting\OverlaySettingsUpdated;
use He4rt\Streaming\DTOs\IncomingStreamEvent;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Enums\VoiceLayout;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\Streamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Actions\RecordStreamEvent;
use He4rt\Streaming\StreamEvent\Data\StreamActor;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Event::fake([OverlaySettingsUpdated::class, AlertTriggered::class]);

    $this->user = User::factory()->streamer()->create();
    $this->streamer = resolve(EnsureStreamer::class)->handle($this->user);
    $this->actingAs($this->user);
});

test('o título e o horário da abertura vão para a overlay aberta', function (): void {
    livewire(StreamOverlaysPage::class)
        ->callAction('startingSoonSettings', data: ['title' => 'Live de PHP', 'starts_at' => '19:05'])
        ->assertHasNoActionErrors()
        ->assertNotified('Cena de abertura atualizada');

    $startingSoon = Streamer::query()->whereKey($this->streamer->id)->sole()->settings->startingSoon;

    expect($startingSoon->title)->toBe('Live de PHP')
        ->and($startingSoon->startsAt)->toBe('19:05');
    Event::assertDispatched(fn (OverlaySettingsUpdated $broadcast): bool => $broadcast->broadcastAs() === 'settings.updated'
        && $broadcast->broadcastWith() === ['scene' => 'starting', 'settings' => ['title' => 'Live de PHP', 'starts_at' => '19:05']]);
});

test('um horário fora do formato não é salvo', function (): void {
    livewire(StreamOverlaysPage::class)
        ->callAction('startingSoonSettings', data: ['title' => 'Live de PHP', 'starts_at' => '25:00'])
        ->assertHasActionErrors(['starts_at']);

    Event::assertNotDispatched(OverlaySettingsUpdated::class);
});

test('a sala de voz troca de disposição', function (): void {
    livewire(StreamOverlaysPage::class)
        ->callAction('voiceSettings', data: ['layout' => VoiceLayout::Row->value])
        ->assertNotified('Sala de voz atualizada');

    expect(Streamer::query()->whereKey($this->streamer->id)->sole()->settings->voice->layout)->toBe(VoiceLayout::Row);
    Event::assertDispatched(fn (OverlaySettingsUpdated $broadcast): bool => $broadcast->scene === OverlayScene::Voice);
});

test('com o alerta de follow desligado, o follow é gravado sem alerta', function (): void {
    $source = StreamerSource::factory()->create(['streamer_id' => $this->streamer->id]);
    $alerts = array_fill_keys(array_map(fn (StreamEventType $type): string => $type->value, StreamEventType::cases()), value: true);

    livewire(StreamOverlaysPage::class)
        ->callAction('alertSettings', data: [...$alerts, StreamEventType::Follow->value => false])
        ->assertNotified('Alertas atualizados');

    resolve(RecordStreamEvent::class)->handle(new IncomingStreamEvent(
        platform: $source->identity->provider,
        broadcasterId: $source->identity->external_account_id,
        sourceEventId: (string) Str::uuid(),
        type: StreamEventType::Follow,
        occurredAt: CarbonImmutable::now(),
        actor: new StreamActor('9911', 'mariacoda', 'MariaCoda'),
    ));

    expect(StreamEvent::query()->whereBelongsTo($this->streamer)->count())->toBe(1);
    Event::assertNotDispatched(AlertTriggered::class);
    Event::assertNotDispatched(OverlaySettingsUpdated::class);
});

test('cada cena com configuração tem o seu botão', function (): void {
    livewire(StreamOverlaysPage::class)
        ->assertActionVisible('startingSoonSettings')
        ->assertActionVisible('voiceSettings')
        ->assertActionVisible('alertSettings');
});
