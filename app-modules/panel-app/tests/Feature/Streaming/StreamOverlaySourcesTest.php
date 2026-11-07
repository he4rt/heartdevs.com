<?php

declare(strict_types=1);

use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamOverlaysPage;
use He4rt\Streaming\Broadcasting\OverlaySettingsUpdated;
use He4rt\Streaming\Broadcasting\OverlaySourcesUpdated;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use Illuminate\Support\Facades\Event;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Event::fake([OverlaySettingsUpdated::class, OverlaySourcesUpdated::class]);

    $this->user = User::factory()->streamer()->create();
    $this->streamer = resolve(EnsureStreamer::class)->handle($this->user);
    $this->actingAs($this->user);
});

function namedSource(StreamerSource $source, string $username): StreamerSource
{
    $source->identity->update(['metadata' => ['username' => $username]]);

    return $source;
}

test('com uma fonte só, a cena não oferece escolher fontes', function (): void {
    StreamerSource::factory()->for($this->streamer)->create();

    livewire(StreamOverlaysPage::class)
        ->assertActionHidden(TestAction::make('sceneSources')->arguments(['scene' => 'pub']));
});

test('o streamer escolhe a fonte da cena e o botão mostra a escolha', function (): void {
    $pubChannel = namedSource(StreamerSource::factory()->for($this->streamer)->create(), 'he4rtdevs');
    namedSource(StreamerSource::factory()->for($this->streamer)->create(), 'danielhe4rt');

    livewire(StreamOverlaysPage::class)
        ->assertActionHasLabel(TestAction::make('sceneSources')->arguments(['scene' => 'pub']), 'Fontes: todas')
        ->callAction(TestAction::make('sceneSources')->arguments(['scene' => 'pub']), data: ['sources' => [$pubChannel->id]])
        ->assertNotified('Fontes da cena atualizadas')
        ->assertActionHasLabel(TestAction::make('sceneSources')->arguments(['scene' => 'pub']), 'Fontes: @he4rtdevs')
        ->assertActionHasLabel(TestAction::make('sceneSources')->arguments(['scene' => 'chat']), 'Fontes: todas');

    expect($this->streamer->refresh()->settings->sceneSources->chosenFor(OverlayScene::Pub))->toBe([$pubChannel->id]);
    Event::assertDispatched(fn (OverlaySourcesUpdated $broadcast): bool => $broadcast->scene === OverlayScene::Pub);
});

test('a fonte de outro streamer não entra na cena', function (): void {
    $ownSource = StreamerSource::factory()->for($this->streamer)->create();
    StreamerSource::factory()->for($this->streamer)->create();
    $foreignSource = StreamerSource::factory()->create();

    livewire(StreamOverlaysPage::class)
        ->callAction(TestAction::make('sceneSources')->arguments(['scene' => 'pub']), data: ['sources' => [$ownSource->id, $foreignSource->id]]);

    expect($this->streamer->refresh()->settings->sceneSources->chosenFor(OverlayScene::Pub))->not->toContain($foreignSource->id);
});
