<?php

declare(strict_types=1);

namespace He4rt\Portal\Livewire;

use He4rt\Activity\Message\Models\Message;
use He4rt\Gamification\Character\Models\Character;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\Identity\User\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Cache;
use Livewire\Attributes\Computed;
use Livewire\Component;

final class HeroSection extends Component
{
    #[Computed]
    public function usersCount(): int
    {
        return User::query()->count();
    }

    /**
     * @return array<int, string>
     */
    #[Computed]
    public function avatars(): array
    {
        if (!app()->isProduction()) {
            return $this->fetchAvatars();
        }

        return Cache::remember('portal:hero:avatars', now()->addHour(), fn () => $this->fetchAvatars());
    }

    /**
     * @return array<string, mixed>
     */
    #[Computed]
    public function terminalStats(): array
    {
        if (!app()->isProduction()) {
            return $this->fetchTerminalStats();
        }

        return Cache::remember('portal:hero:terminal', now()->addHour(), fn () => $this->fetchTerminalStats());
    }

    public function render(): View
    {
        return view('portal::sections.hero');
    }

    /**
     * @return array<int, string>
     */
    private function fetchAvatars(): array
    {
        return ExternalIdentity::query()
            ->where('provider', IdentityProvider::GitHub)
            ->whereHas('messages', fn (Builder $query) => $query->where('sent_at', '>=', now()->subDays(30)))
            ->where(fn (Builder $query) => $query->whereHas('messages', operator: '>', count: 20))
            ->inRandomOrder()
            ->limit(10)
            ->pluck('metadata')
            ->map(fn (array $metadata) => sprintf('https://github.com/%s.png', $metadata['username']))
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function fetchTerminalStats(): array
    {
        $totalMessages = Message::query()->count();
        $totalXp = Character::query()->sum('experience');

        return [
            'members' => number_format(User::query()->count(), thousands_separator: '.'),
            'messages' => number_format($totalMessages, thousands_separator: '.'),
            'xp' => number_format((int) $totalXp, thousands_separator: '.'),
        ];
    }
}
