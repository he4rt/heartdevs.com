<?php

declare(strict_types=1);

namespace He4rt\PanelAdmin\Marketing\Pages;

use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use He4rt\PanelAdmin\Marketing\MarketingCluster;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

class MeetingShowcasePage extends Page
{
    public string $channelId = '';

    public string $startDate = '';

    public string $endDate = '';

    /** @var array<int, array{discord_id: string|null, username: string, global_name: string, avatar_url: string|null, total_messages: int}> */
    public array $participants = [];

    public bool $loaded = false;

    protected static ?string $cluster = MarketingCluster::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCamera;

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'meeting-showcase';

    protected string $view = 'panel-admin::pages.meeting-showcase';

    protected Width|string|null $maxContentWidth = Width::Full;

    public static function getNavigationLabel(): string
    {
        return __('panel-admin::marketing.navigation.meeting_showcase');
    }

    public function getTitle(): string
    {
        return __('panel-admin::marketing.navigation.meeting_showcase');
    }

    public function loadParticipants(): void
    {
        if ($this->channelId === '' || $this->startDate === '' || $this->endDate === '') {
            return;
        }

        $tz = config('app.display_timezone');
        $start = Date::parse($this->startDate, $tz)->utc();
        $end = Date::parse($this->endDate, $tz)->endOfMinute()->utc();

        /** @var Collection<int, Message> $messageStats */
        $messageStats = Message::query()
            ->where('channel_id', $this->channelId)
            ->whereBetween('sent_at', [$start, $end])
            ->whereNotNull('sent_at')
            ->select('external_identity_id', DB::raw('COUNT(*) as total_messages'))
            ->groupBy('external_identity_id')
            ->orderByDesc('total_messages')
            ->get();

        $identityIds = $messageStats->pluck('external_identity_id');

        $identities = ExternalIdentity::query()
            ->withTrashed()
            ->with('user')
            ->whereIn('id', $identityIds)
            ->get()
            ->keyBy('id');

        $this->participants = $messageStats->map(function (Message $stat) use ($identities): array {
            $identity = $identities->get($stat->external_identity_id);

            $totalMessages = (int) $stat->getAttribute('total_messages');

            return $this->extractDiscordData($identity, $totalMessages);
        })->all();

        $this->loaded = true;
    }

    /** @return array{discord_id: string|null, username: string, global_name: string, avatar_url: string|null, total_messages: int} */
    private function extractDiscordData(?ExternalIdentity $identity, int $totalMessages): array
    {
        if (!$identity instanceof ExternalIdentity) {
            return [
                'discord_id' => null,
                'username' => 'unknown',
                'global_name' => 'Unknown',
                'avatar_url' => null,
                'total_messages' => $totalMessages,
            ];
        }

        $metadata = $identity->metadata ?? [];
        $discordUser = $metadata['user'] ?? $metadata['author'] ?? [];

        if (!is_array($discordUser)) {
            $discordUser = [];
        }

        $linkedUser = $identity->user;

        $username = $metadata['username'] ?? $discordUser['username'] ?? $linkedUser?->username;
        $avatar = $metadata['avatar'] ?? $discordUser['avatar'] ?? null;
        $globalName = $metadata['global_name'] ?? $discordUser['global_name'] ?? $linkedUser?->name;

        if ($avatar && !str_starts_with((string) $avatar, 'http')) {
            $avatar = sprintf(
                'https://cdn.discordapp.com/avatars/%s/%s.png?size=128',
                $identity->external_account_id,
                $avatar,
            );
        }

        return [
            'discord_id' => $identity->external_account_id,
            'username' => $username ?? 'unknown',
            'global_name' => $globalName ?? $username ?? 'Unknown',
            'avatar_url' => $avatar,
            'total_messages' => $totalMessages,
        ];
    }
}
