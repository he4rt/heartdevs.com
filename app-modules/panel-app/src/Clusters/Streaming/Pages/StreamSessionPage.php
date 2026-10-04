<?php

declare(strict_types=1);

namespace He4rt\PanelApp\Clusters\Streaming\Pages;

use Filament\Pages\Page;
use Filament\Panel;
use He4rt\Identity\User\Models\User;
use He4rt\PanelApp\Clusters\Streaming\StreamDuration;
use He4rt\PanelApp\Clusters\Streaming\StreamEventSummary;
use He4rt\PanelApp\Clusters\Streaming\StreamingCluster;
use He4rt\Streaming\Enums\StreamEventType;
use He4rt\Streaming\Session\Data\SessionTotals;
use He4rt\Streaming\Session\Models\StreamSession;
use He4rt\Streaming\Session\Queries\StreamSessionChat;
use He4rt\Streaming\Session\Queries\StreamSessionTotals;
use He4rt\Streaming\Streamer\Actions\EnsureStreamer;
use He4rt\Streaming\Streamer\Models\Streamer;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Livewire\Attributes\Locked;

class StreamSessionPage extends Page
{
    private const int TOP_CHATTERS = 5;

    #[Locked]
    public string $sessionId = '';

    protected static ?string $cluster = StreamingCluster::class;

    protected static ?string $slug = 'lives/{session}';

    protected static bool $shouldRegisterNavigation = false;

    protected string $view = 'panel-app::pages.streaming.live';

    public static function canAccess(): bool
    {
        return auth()->user()?->can('use-streamer-tools') ?? false;
    }

    public static function getRelativeRouteName(Panel $panel): string
    {
        return 'lives.show';
    }

    public function mount(EnsureStreamer $ensureStreamer, string $session): void
    {
        $ensureStreamer->handle($this->currentUser());

        abort_unless(Str::isUuid($session), 404);

        $this->sessionId = $this->sessions()->findOrFail($session)->id;
    }

    public function getTitle(): string
    {
        return $this->session()->title ?? 'Live sem título';
    }

    /**
     * @return array<string>
     */
    public function getBreadcrumbs(): array
    {
        return [
            ...StreamingCluster::unshiftClusterBreadcrumbs([StreamSessionsPage::getUrl() => 'Lives']),
            $this->session()->started_at->format('d/m/Y H:i'),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $session = $this->session();
        $earlierSessions = $this->sessions()->where('started_at', '<', $session->started_at)->latest('started_at');
        $previous = (clone $earlierSessions)->first();
        $next = $this->sessions()->where('started_at', '>', $session->started_at)->oldest('started_at')->first();
        $baseline = $earlierSessions->cursor()->first(fn (StreamSession $earlier): bool => !SessionTotals::of($earlier)->hasNoData());
        $totals = SessionTotals::of($session);
        $previousTotals = $baseline instanceof StreamSession ? SessionTotals::of($baseline) : null;

        return [
            'session' => $session,
            'duration' => StreamDuration::between($session->started_at, $session->ended_at ?? now()),
            'hasNoData' => $totals->hasNoData(),
            'comparison' => [
                $this->compare(StreamEventType::Follow->getEmoji(), 'Follows', $totals->follows, $previousTotals?->follows),
                $this->compare(StreamEventType::Sub->getEmoji(), 'Subs', $totals->subs, $previousTotals?->subs),
                $this->compare(StreamEventType::Cheer->getEmoji(), 'Bits', $totals->bits, $previousTotals?->bits),
                $this->compare(StreamEventType::Raid->getEmoji(), 'Raids', $totals->raids, $previousTotals?->raids),
                $this->compare('💬', 'Mensagens', $totals->messages, $previousTotals?->messages),
            ],
            'baseline' => $baseline,
            'previous' => $previous,
            'next' => $next,
            'timeline' => $session->events()->oldest('occurred_at')->get()->map(StreamEventSummary::of(...))->all(),
            'topChatters' => resolve(StreamSessionChat::class)->topChatters($session, self::TOP_CHATTERS),
            'chatters' => $totals->chatters,
        ];
    }

    /**
     * The baseline skips sessions without data, so a live that lost its events does not inflate the deltas.
     *
     * @return array{emoji: string, label: string, value: int, delta: int|null}
     */
    private function compare(string $emoji, string $label, int $value, ?int $previousValue): array
    {
        return [
            'emoji' => $emoji,
            'label' => $label,
            'value' => $value,
            'delta' => $previousValue === null ? null : $value - $previousValue,
        ];
    }

    private function session(): StreamSession
    {
        return once(fn (): StreamSession => $this->sessions()->with('identity')->findOrFail($this->sessionId));
    }

    /**
     * @return Builder<StreamSession>
     */
    private function sessions(): Builder
    {
        return resolve(StreamSessionTotals::class)->apply(StreamSession::query()->whereBelongsTo($this->currentStreamer()));
    }

    private function currentUser(): User
    {
        $user = auth()->user();

        abort_unless($user instanceof User, 403);

        return $user;
    }

    private function currentStreamer(): Streamer
    {
        return Streamer::query()->whereBelongsTo($this->currentUser())->sole();
    }
}
