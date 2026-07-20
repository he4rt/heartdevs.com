<?php

declare(strict_types=1);

namespace He4rt\Activity\Retrospective;

use He4rt\Activity\Message\Enums\MembershipEventKind;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Reaction\Models\Reaction;
use He4rt\Activity\Retrospective\Slides\MessagesSlide;
use He4rt\Activity\Retrospective\Slides\NewMembersSlide;
use He4rt\Activity\Retrospective\Slides\ReactionsSlide;
use He4rt\Activity\Retrospective\Slides\TopMessageSlide;
use He4rt\Activity\Retrospective\Slides\VoiceBoardSlide;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Community\Retrospective\Contracts\RetrospectiveSource;
use He4rt\Community\Retrospective\Contracts\Slide;
use He4rt\Community\Retrospective\DTOs\HeadlineMetrics;
use He4rt\Community\Retrospective\DTOs\Metric;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Fonte Discord da retrospectiva. Mora no activity (dono do dado: Voice,
 * Message, Reaction, MembershipEvent), não em integration-discord. As tabelas
 * são grandes em prod (messages ~2GB), então TODA métrica é agregada em SQL
 * escopada pelo Period; nomes de exibição são resolvidos em PHP apenas para as
 * poucas pessoas do topo de cada ranking. Filtra por sent_at/occurred_at
 * (tempo do evento), nunca created_at.
 */
final class DiscordSource implements RetrospectiveSource
{
    public function key(): string
    {
        return 'discord';
    }

    public function collect(Period $period, SourceFilters $filters): SourceResult
    {
        $totalMessages = $this->messages($period, $filters)->count();
        $withReactions = $this->messages($period, $filters)->where('reactions_total', '>', 0)->count();
        $pinned = $this->messages($period, $filters)->where('is_pinned', operator: true)->count();
        $chatters = $this->topChatters($period, $filters);

        $participants = Voice::query()
            ->whereBetween('occurred_at', [$period->since, $period->until])
            ->distinct()
            ->count('external_identity_id');
        $voiceXp = (int) Voice::query()
            ->whereBetween('occurred_at', [$period->since, $period->until])
            ->sum('obtained_experience');
        $channels = $this->topVoiceChannels($period);

        $joins = MembershipEvent::query()
            ->whereBetween('occurred_at', [$period->since, $period->until])
            ->where('kind', MembershipEventKind::UserJoin->value)
            ->count();
        $boosts = MembershipEvent::query()
            ->whereBetween('occurred_at', [$period->since, $period->until])
            ->where('kind', 'like', 'boost%')
            ->count();

        $totalReactions = $this->totalReactions($period, $filters);
        $emojis = $this->topEmojis($period, $filters);
        $topMessages = $this->topMessages($period, $filters);

        return new SourceResult(
            key: $this->key(),
            label: 'Discord',
            headline: $this->headline($totalMessages, $participants, $joins, $totalReactions),
            slides: $this->slides(
                participants: $participants,
                voiceXp: $voiceXp,
                channels: $channels,
                totalMessages: $totalMessages,
                withReactions: $withReactions,
                pinned: $pinned,
                chatters: $chatters,
                joins: $joins,
                boosts: $boosts,
                totalReactions: $totalReactions,
                emojis: $emojis,
                topMessages: $topMessages,
            ),
        );
    }

    private function headline(int $messages, int $participants, int $joins, int $reactions): HeadlineMetrics
    {
        $metrics = [];

        if ($messages > 0) {
            $metrics[] = new Metric('Mensagens', $messages);
        }

        if ($participants > 0) {
            $metrics[] = new Metric('Pessoas em call', $participants);
        }

        if ($joins > 0) {
            $metrics[] = new Metric('Novos membros', $joins);
        }

        if ($reactions > 0) {
            $metrics[] = new Metric('Reações', $reactions);
        }

        return new HeadlineMetrics($metrics);
    }

    /**
     * @param  list<array{name: string, events: int, xp: int}>  $channels
     * @param  list<array{name: string, messages: int}>  $chatters
     * @param  list<array{name: string, count: int, custom: bool}>  $emojis
     * @param  list<array{content: string, author: string, reactions: int}>  $topMessages
     * @return list<Slide>
     */
    private function slides(
        int $participants,
        int $voiceXp,
        array $channels,
        int $totalMessages,
        int $withReactions,
        int $pinned,
        array $chatters,
        int $joins,
        int $boosts,
        int $totalReactions,
        array $emojis,
        array $topMessages,
    ): array {
        $slides = [];

        if ($participants > 0) {
            $slides[] = new VoiceBoardSlide($participants, $voiceXp, $channels);
        }

        if ($totalMessages > 0) {
            $slides[] = new MessagesSlide($totalMessages, $withReactions, $pinned, $chatters);
        }

        if ($joins > 0 || $boosts > 0) {
            $slides[] = new NewMembersSlide($joins, $boosts);
        }

        if ($totalReactions > 0) {
            $slides[] = new ReactionsSlide($totalReactions, $emojis);
        }

        if ($topMessages !== []) {
            $slides[] = new TopMessageSlide($topMessages);
        }

        return $slides;
    }

    /**
     * Base de mensagens do recorte. hideBots derruba source_kind='bot' mas mantém
     * linhas históricas com source_kind nulo.
     *
     * @return Builder<Message>
     */
    private function messages(Period $period, SourceFilters $filters): Builder
    {
        return Message::query()
            ->whereBetween('sent_at', [$period->since, $period->until])
            ->when(
                $filters->hideBots,
                fn (Builder $query): Builder => $query->where(function (Builder $inner): void {
                    $inner->whereNull('source_kind')
                        ->orWhere('source_kind', '!=', MessageSourceKind::Bot->value);
                }),
            );
    }

    /**
     * @return list<array{name: string, messages: int}>
     */
    private function topChatters(Period $period, SourceFilters $filters): array
    {
        $rows = $this->messages($period, $filters)
            ->groupBy('external_identity_id')
            ->orderByRaw('COUNT(*) DESC')
            ->limit(8)
            ->get(['external_identity_id', DB::raw('COUNT(*) AS messages')]);

        $names = $this->displayNames($this->identityIds($rows));

        return array_values(
            $rows->map(fn (Message $row): array => [
                'name' => $names[$row->external_identity_id] ?? 'Anônimo',
                'messages' => (int) $row->getAttribute('messages'),
            ])->all(),
        );
    }

    /**
     * @return list<array{name: string, events: int, xp: int}>
     */
    private function topVoiceChannels(Period $period): array
    {
        return array_values(
            Voice::query()
                ->whereBetween('occurred_at', [$period->since, $period->until])
                ->whereNotNull('channel_name')
                ->groupBy('channel_name')
                ->orderByRaw('SUM(obtained_experience) DESC')
                ->limit(6)
                ->get([
                    'channel_name',
                    DB::raw('COUNT(*) AS events'),
                    DB::raw('COALESCE(SUM(obtained_experience), 0) AS xp'),
                ])
                ->map(fn (Voice $row): array => [
                    'name' => $row->channel_name,
                    'events' => (int) $row->getAttribute('events'),
                    'xp' => (int) $row->getAttribute('xp'),
                ])
                ->all(),
        );
    }

    private function totalReactions(Period $period, SourceFilters $filters): int
    {
        return (int) Reaction::query()
            ->where('reactable_type', 'message')
            ->whereIn('reactable_id', $this->messages($period, $filters)->select('id'))
            ->sum('count');
    }

    /**
     * @return list<array{name: string, count: int, custom: bool}>
     */
    private function topEmojis(Period $period, SourceFilters $filters): array
    {
        return array_values(
            Reaction::query()
                ->where('reactable_type', 'message')
                ->whereIn('reactable_id', $this->messages($period, $filters)->select('id'))
                ->groupBy('emoji_key', 'emoji_name')
                ->orderByRaw('SUM("count") DESC')
                ->limit(10)
                ->get([
                    'emoji_key',
                    'emoji_name',
                    DB::raw('MAX(emoji_id) AS emoji_id'),
                    DB::raw('SUM("count") AS total'),
                ])
                ->map(fn (Reaction $row): array => [
                    'name' => $row->emoji_name ?? $row->emoji_key,
                    'count' => (int) $row->getAttribute('total'),
                    'custom' => $row->getAttribute('emoji_id') !== null,
                ])
                ->all(),
        );
    }

    /**
     * @return list<array{content: string, author: string, reactions: int}>
     */
    private function topMessages(Period $period, SourceFilters $filters): array
    {
        $rows = $this->messages($period, $filters)
            ->where('reactions_total', '>', 0)
            ->orderByDesc('reactions_total')
            ->limit(3)
            ->get(['id', 'external_identity_id', 'content', 'reactions_total']);

        $names = $this->displayNames($this->identityIds($rows));

        return array_values(
            $rows->map(fn (Message $row): array => [
                'content' => (string) str($row->content)->limit(160),
                'author' => $names[$row->external_identity_id] ?? 'Anônimo',
                'reactions' => $row->reactions_total,
            ])->all(),
        );
    }

    /**
     * @param  Collection<int, Message>  $rows
     * @return list<string>
     */
    private function identityIds(Collection $rows): array
    {
        return array_values($rows->map(fn (Message $row): string => $row->external_identity_id)->all());
    }

    /**
     * Resolve o nome de exibição do Discord para os external_identity_id do topo
     * de um ranking: o username do Discord (metadata), senão o id externo. Só as
     * poucas pessoas do topo entram aqui, então a query é barata.
     *
     * @param  list<string>  $ids
     * @return array<string, string>
     */
    private function displayNames(array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return ExternalIdentity::query()
            ->whereIn('id', array_unique($ids))
            ->get()
            ->mapWithKeys(function (ExternalIdentity $identity): array {
                $metadata = $identity->metadata ?? [];
                $username = is_string($metadata['username'] ?? null) ? $metadata['username'] : null;

                return [$identity->id => $username ?? $identity->external_account_id ?? 'Anônimo'];
            })
            ->all();
    }
}
