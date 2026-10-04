<?php

declare(strict_types=1);

use Filament\Actions\Action;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamDashboardPage;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamOverlaysPage;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamSessionPage;
use He4rt\PanelApp\Clusters\Streaming\Pages\StreamSessionsPage;
use He4rt\Streaming\Broadcasting\AlertTriggered;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\StreamerSource;
use He4rt\Streaming\StreamEvent\Data\CheerDetails;
use He4rt\Streaming\StreamEvent\Data\RaidDetails;
use He4rt\Streaming\StreamEvent\Models\StreamEvent;
use Illuminate\Support\Facades\Event;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    Filament::setCurrentPanel(Filament::getPanel('app'));
    config()->set('services.twitch.scopes.app', 'user:read:email');

    $this->user = User::factory()->streamer()->create();
    $this->actingAs($this->user);

    $identity = ExternalIdentity::factory()->create([
        'model_id' => $this->user->getKey(),
        'provider' => IdentityProvider::Twitch,
        'connected_at' => now(),
        'disconnected_at' => null,
        'metadata' => ['username' => 'canal_do_streamer'],
    ]);
    $streamer = resolve(EnsureStreamer::class)->handle($this->user);

    $this->source = StreamerSource::query()->whereBelongsTo($streamer)->where('external_identity_id', $identity->getKey())->sole();
});

function liveOf(StreamerSource $source, string $title, DateTimeInterface $startedAt, ?DateTimeInterface $endedAt = null): StreamSession
{
    return StreamSession::factory()->forSource($source)->create([
        'title' => $title,
        'started_at' => $startedAt,
        'ended_at' => $endedAt,
    ]);
}

/**
 * @param  array<int, StreamEventType>  $types
 */
function eventsIn(StreamSession $session, StreamerSource $source, array $types): void
{
    foreach ($types as $minute => $type) {
        StreamEvent::factory()->forSource($source)->ofType($type)->create([
            'stream_session_id' => $session->id,
            'occurred_at' => $session->started_at->addMinutes($minute + 1),
        ]);
    }
}

function twitchChatter(string $username): ExternalIdentity
{
    return ExternalIdentity::factory()->create(['provider' => IdentityProvider::Twitch, 'metadata' => ['username' => $username]]);
}

function chatterSays(StreamerSource $source, ExternalIdentity $chatter, DateTimeInterface $sentAt): Message
{
    return Message::factory()->create([
        'platform' => IdentityProvider::Twitch,
        'channel_id' => $source->identity->external_account_id,
        'external_identity_id' => $chatter->getKey(),
        'sent_at' => $sentAt,
    ]);
}

describe('Painel', function (): void {
    test('mostra o card ao vivo com as somas da sessão aberta', function (): void {
        $live = liveOf($this->source, 'Refatorando o módulo de lives', now()->subMinutes(95));
        eventsIn($live, $this->source, [StreamEventType::Follow, StreamEventType::Follow, StreamEventType::Raid]);
        chatterSays($this->source, twitchChatter('ana'), now()->subMinute());
        chatterSays($this->source, twitchChatter('bia'), now()->subMinutes(2));

        $html = livewire(StreamDashboardPage::class)->html();

        expect($html)
            ->toContain('Ao vivo')
            ->toContain('há 1h 35min')
            ->toContain('Refatorando o módulo de lives')
            ->toContain('0,4 msgs/min nos últimos 5 min')
            ->toContain('2 chatters');
    });

    test('offline, mostra a última live comparada com a anterior com dados', function (): void {
        $older = liveOf($this->source, 'Live antiga', now()->subDays(4), now()->subDays(4)->addHours(2));
        liveOf($this->source, 'Live que caiu', now()->subDays(3), now()->subDays(3)->addMinutes(20));
        $last = liveOf($this->source, 'Live de ontem', now()->subDays(2)->subHours(2), now()->subDays(2));
        eventsIn($older, $this->source, [StreamEventType::Follow]);
        eventsIn($last, $this->source, [StreamEventType::Follow, StreamEventType::Follow, StreamEventType::Follow]);

        $page = livewire(StreamDashboardPage::class)
            ->assertViewHas('live', fn (?array $live): bool => $live === null)
            ->assertViewHas('lastLive', fn (?array $lastLive): bool => $lastLive !== null
                && $lastLive['session']->is($last)
                && $lastLive['baseline']->is($older)
                && $lastLive['rows'][0]['value'] === 3
                && $lastLive['rows'][0]['delta'] === 2);

        expect($page->html())
            ->toContain('Offline')
            ->toContain('última live há 2 dias')
            ->toContain('Live de ontem')
            ->toContain('▲2')
            ->toContain(StreamSessionPage::getUrl(['session' => $last->id]));
    });

    test('ao vivo, o card da live aberta substitui a última live', function (): void {
        liveOf($this->source, 'Live de ontem', now()->subDay(), now()->subDay()->addHours(2));
        $live = liveOf($this->source, 'Ao vivo agora', now()->subMinutes(10));

        livewire(StreamDashboardPage::class)
            ->assertViewHas('live', fn (?array $summary): bool => $summary !== null && $summary['session']->is($live))
            ->assertViewHas('lastLive', fn (?array $lastLive): bool => $lastLive === null);
    });

    test('a última live sem dados avisa para conferir a integração', function (): void {
        liveOf($this->source, 'Live que caiu', now()->subDay(), now()->subDay()->addMinutes(20));

        expect(livewire(StreamDashboardPage::class)->html())
            ->toContain('Nenhum evento e nenhuma mensagem chegaram nessa live');
    });

    test('sem nenhuma live, diz que os números aparecem na primeira', function (): void {
        $page = livewire(StreamDashboardPage::class)->assertViewHas('lastLive', fn (?array $lastLive): bool => $lastLive === null);

        expect($page->html())->toContain('Nenhuma live ainda');
    });

    test('o alerta de teste pede confirmação só durante a live', function (bool $isLive): void {
        if ($isLive) {
            liveOf($this->source, 'Ao vivo agora', now()->subMinutes(10));
        }

        livewire(StreamOverlaysPage::class)->assertActionExists(
            TestAction::make('testAlert')->arguments(['type' => 'follow']),
            fn (Action $action): bool => $action->isConfirmationRequired() === $isLive,
        );
    })->with(['durante a live' => true, 'fora da live' => false]);

    test('repetir um alerta manda o evento gravado de novo para a overlay', function (): void {
        Event::fake([AlertTriggered::class]);
        $event = StreamEvent::factory()->forSource($this->source)->ofType(StreamEventType::Cheer, new CheerDetails(bits: 250))->create();

        livewire(StreamDashboardPage::class)
            ->callAction(TestAction::make('replayAlert')->arguments(['event' => $event->id]))
            ->assertNotified('Alerta de Bits repetido');

        Event::assertDispatched(fn (AlertTriggered $alert): bool => !$alert->isTest && $alert->type === StreamEventType::Cheer);
        expect(StreamEvent::query()->count())->toBe(1);
    });

    test('não repete o alerta de um evento de outro streamer', function (): void {
        Event::fake([AlertTriggered::class]);
        $otherEvent = StreamEvent::factory()->create();

        livewire(StreamDashboardPage::class)
            ->call('mountAction', 'replayAlert', ['event' => $otherEvent->id])
            ->assertStatus(404);

        Event::assertNotDispatched(AlertTriggered::class);
    });
});

describe('Lives', function (): void {
    test('lista só as lives do streamer, da mais nova para a mais antiga', function (): void {
        $old = liveOf($this->source, 'Live antiga', now()->subDays(3), now()->subDays(3)->addHours(2));
        $recent = liveOf($this->source, 'Live recente', now()->subDay(), now()->subDay()->addHours(3));
        $foreign = StreamSession::factory()->create(['title' => 'Live de outra pessoa']);

        livewire(StreamSessionsPage::class)
            ->loadTable()
            ->assertCanSeeTableRecords([$recent, $old], inOrder: true)
            ->assertCanNotSeeTableRecords([$foreign]);
    });

    test('mostra as somas de cada live e marca a live aberta', function (): void {
        $live = liveOf($this->source, 'Ao vivo agora', now()->subMinutes(30));
        eventsIn($live, $this->source, [StreamEventType::Follow, StreamEventType::Follow, StreamEventType::Follow]);

        livewire(StreamSessionsPage::class)
            ->loadTable()
            ->assertTableColumnStateSet('follows_total', 3, $live)
            ->assertTableColumnFormattedStateSet('duration', 'ao vivo', $live);
    });

    test('uma live sem evento e sem mensagem mostra traço em vez de zero', function (): void {
        $silent = liveOf($this->source, 'Live sem dados', now()->subDay(), now()->subDay()->addHour());

        livewire(StreamSessionsPage::class)
            ->loadTable()
            ->assertTableColumnFormattedStateSet('follows_total', '—', $silent)
            ->assertTableColumnFormattedStateSet('duration', '1h 00min', $silent);
    });

    test('cada linha leva ao detalhe da live', function (): void {
        $live = liveOf($this->source, 'Live recente', now()->subDay(), now()->subDay()->addHour());

        expect(StreamSessionPage::getUrl(['session' => $live->id]))->toEndWith('/minha-live/lives/'.$live->id);
        $this->get(StreamSessionPage::getUrl(['session' => $live->id]))->assertOk()->assertSee('Live recente');
    });
});

describe('Detalhe da live', function (): void {
    test('compara com a live anterior e navega entre as lives', function (): void {
        $previous = liveOf($this->source, 'Live anterior', now()->subDays(2), now()->subDays(2)->addHours(2));
        $current = liveOf($this->source, 'Live do meio', now()->subDay(), now()->subDay()->addHours(2));
        $next = liveOf($this->source, 'Live seguinte', now()->subHours(3), now()->subHour());

        eventsIn($previous, $this->source, [StreamEventType::Follow, StreamEventType::Follow, StreamEventType::Raid]);
        eventsIn($current, $this->source, [StreamEventType::Follow, StreamEventType::Follow, StreamEventType::Follow, StreamEventType::Follow]);

        livewire(StreamSessionPage::class, ['session' => $current->id])
            ->assertViewHas('comparison', fn (array $comparison): bool => $comparison[0]['value'] === 4
                && $comparison[0]['delta'] === 2
                && $comparison[3]['value'] === 0
                && $comparison[3]['delta'] === -1)
            ->assertViewHas('previous', fn (?StreamSession $session): bool => $session?->is($previous) ?? false)
            ->assertViewHas('next', fn (?StreamSession $session): bool => $session?->is($next) ?? false);

        expect(livewire(StreamSessionPage::class, ['session' => $current->id])->html())->toContain('▲2')->toContain('▼1');
    });

    test('a comparação pula a live anterior sem dados', function (): void {
        $withData = liveOf($this->source, 'Live com dados', now()->subDays(3), now()->subDays(3)->addHours(2));
        $dropped = liveOf($this->source, 'Live que caiu', now()->subDays(2), now()->subDays(2)->addMinutes(20));
        $current = liveOf($this->source, 'Live de hoje', now()->subDay(), now()->subDay()->addHours(2));

        eventsIn($withData, $this->source, [StreamEventType::Follow, StreamEventType::Follow, StreamEventType::Follow]);
        eventsIn($current, $this->source, [StreamEventType::Follow]);

        $page = livewire(StreamSessionPage::class, ['session' => $current->id])
            ->assertViewHas('previous', fn (?StreamSession $session): bool => $session?->is($dropped) ?? false)
            ->assertViewHas('baseline', fn (?StreamSession $session): bool => $session?->is($withData) ?? false)
            ->assertViewHas('comparison', fn (array $comparison): bool => $comparison[0]['delta'] === -2);

        expect($page->html())->toContain(sprintf('Setas em relação à live de %s, a última com dados.', $withData->started_at->format('d/m')));
    });

    test('a primeira live não tem comparação nem link para trás', function (): void {
        $first = liveOf($this->source, 'Primeira live', now()->subDay(), now()->subDay()->addHour());
        eventsIn($first, $this->source, [StreamEventType::Follow]);

        $page = livewire(StreamSessionPage::class, ['session' => $first->id])
            ->assertViewHas('previous', fn (?StreamSession $session): bool => !$session instanceof StreamSession)
            ->assertViewHas('comparison', fn (array $comparison): bool => $comparison[0]['delta'] === null);

        expect($page->html())->not->toContain('▲')->not->toContain('▼');
    });

    test('mostra a linha do tempo da live em ordem', function (): void {
        $live = liveOf($this->source, 'Live com raid', now()->subDay(), now()->subDay()->addHours(2));
        StreamEvent::factory()->forSource($this->source)->ofType(StreamEventType::Raid, new RaidDetails(viewers: 42))->create([
            'stream_session_id' => $live->id,
            'actor_display_name' => 'CanalAmigo',
            'occurred_at' => $live->started_at->addMinutes(50),
        ]);
        StreamEvent::factory()->forSource($this->source)->create([
            'stream_session_id' => $live->id,
            'actor_display_name' => 'NovaSeguidora',
            'occurred_at' => $live->started_at->addMinutes(5),
        ]);

        livewire(StreamSessionPage::class, ['session' => $live->id])
            ->assertSeeInOrder(['NovaSeguidora', 'CanalAmigo', '+42 viewers']);
    });

    test('mostra quem mais falou na live', function (): void {
        $live = liveOf($this->source, 'Live com chat', now()->subDay(), now()->subDay()->addHours(2));

        foreach (['ana' => 2, 'bia' => 5] as $username => $messages) {
            $chatter = twitchChatter($username);

            foreach (range(1, $messages) as $minute) {
                chatterSays($this->source, $chatter, $live->started_at->addMinutes($minute));
            }
        }

        livewire(StreamSessionPage::class, ['session' => $live->id])
            ->assertViewHas('topChatters', [
                ['username' => 'bia', 'messages' => 5],
                ['username' => 'ana', 'messages' => 2],
            ])
            ->assertViewHas('chatters', 2);
    });

    test('uma live sem dados avisa que a Twitch pode não ter entregado os eventos', function (): void {
        $silent = liveOf($this->source, 'Live muda', now()->subDay(), now()->subDay()->addHour());

        livewire(StreamSessionPage::class, ['session' => $silent->id])
            ->assertViewHas('hasNoData', value: true);
    });

    test('a live de outro streamer não abre', function (): void {
        $foreign = StreamSession::factory()->create();

        $this->get(StreamSessionPage::getUrl(['session' => $foreign->id]))->assertNotFound();
    });

    test('um id que não é uuid não abre', function (): void {
        $this->get(StreamSessionPage::getUrl(['session' => 'nao-existe']))->assertNotFound();
    });
});
