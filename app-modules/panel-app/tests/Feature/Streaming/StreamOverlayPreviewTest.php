<?php

declare(strict_types=1);

use Filament\Facades\Filament;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamOverlaysPage;
use He4rt\Streaming\Broadcasting\OverlaySettingsUpdated;
use He4rt\Streaming\Enums\OverlayScene;
use He4rt\Streaming\Health\Contracts\OverlayConnections;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Support\Facades\Event;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    Event::fake([OverlaySettingsUpdated::class]);

    $this->user = User::factory()->streamer()->create();
    $this->streamer = resolve(EnsureStreamer::class)->handle($this->user);
    $this->actingAs($this->user);
});

function openOverlays(?int $count): void
{
    app()->instance(OverlayConnections::class, new readonly class($count) implements OverlayConnections
    {
        public function __construct(private ?int $count) {}

        public function count(Streamer $streamer): ?int
        {
            return $this->count;
        }
    });
}

function chatPreviewVersion(string $html, string $token): string
{
    preg_match('#/overlay/'.preg_quote($token, '#').'/chat\?demo=1&amp;v=([0-9a-f]{8})#', $html, $match);

    return $match[1] ?? '';
}

test('cada card mostra a prévia da própria cena com dados de exemplo', function (): void {
    $page = livewire(StreamOverlaysPage::class);

    foreach (OverlayScene::cases() as $scene) {
        $page->assertSeeHtml(sprintf('src="%s/overlay/%s/%s?demo=1&amp;v=', url(''), $this->streamer->overlay_token, $scene->value));
    }
});

test('só as cenas transparentes trocam o fundo da prévia', function (): void {
    $html = livewire(StreamOverlaysPage::class)->html();

    expect(mb_substr_count($html, 'aria-label="Fundo da prévia"'))->toBe(2);
});

test('a prévia recarrega depois de salvar a configuração', function (): void {
    $page = livewire(StreamOverlaysPage::class);
    $before = chatPreviewVersion($page->html(), $this->streamer->overlay_token);

    $page->callAction('chatSettings', data: [
        'style' => 'terminal',
        'fade_after_seconds' => '0',
        'font_size' => '24',
        'width' => '480',
        'direction' => 'newest_bottom',
        'alignment' => 'left',
    ]);

    expect($before)->not->toBeEmpty()
        ->and(chatPreviewVersion($page->html(), $this->streamer->overlay_token))->not->toBe($before);
});

test('o topo diz quantas overlays estão abertas', function (?int $count, string $label): void {
    openOverlays($count);

    livewire(StreamOverlaysPage::class)->assertSee($label);
})->with([
    'duas' => [2, '2 overlays abertas agora'],
    'uma' => [1, '1 overlay aberta agora'],
    'nenhuma' => [0, 'Nenhuma overlay aberta'],
    'Reverb fora do ar' => [null, 'Tempo real fora do ar'],
]);

test('a contagem atualiza sem renderizar a página de novo', function (): void {
    openOverlays(3);

    livewire(StreamOverlaysPage::class)
        ->call('countOpenOverlays')
        ->assertReturned(3);
});
