<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use He4rt\Activity\Message\Enums\MessageSourceKind;
use He4rt\Activity\Message\Models\MembershipEvent;
use He4rt\Activity\Message\Models\Message;
use He4rt\Activity\Reaction\Models\Reaction;
use He4rt\Activity\Retrospective\DiscordSource;
use He4rt\Activity\Voice\Models\Voice;
use He4rt\Community\Retrospective\DTOs\Period;
use He4rt\Community\Retrospective\DTOs\SourceFilters;
use He4rt\Community\Retrospective\DTOs\SourceResult;
use He4rt\Identity\ExternalIdentity\Models\ExternalIdentity;

beforeEach(function (): void {
    $this->since = CarbonImmutable::parse('2026-06-01 00:00:00');
    $this->until = CarbonImmutable::parse('2026-06-07 23:59:59');
    $this->collect = fn (bool $hideBots = true): SourceResult => new DiscordSource()->collect(
        Period::of($this->since, $this->until),
        new SourceFilters(hideBots: $hideBots),
    );
});

/**
 * @return array<string, mixed>
 */
function dcSlide(SourceResult $result, string $kind): array
{
    foreach ($result->slides as $slide) {
        if ($slide->kind() === $kind) {
            return $slide->toArray();
        }
    }

    return [];
}

function dcIdentity(string $name): ExternalIdentity
{
    return ExternalIdentity::factory()->create(['metadata' => ['username' => $name]]);
}

/**
 * @param  array<string, mixed>  $attributes
 */
function dcReaction(string $messageId, string $emojiKey, int $count, ?string $emojiId = null): void
{
    $reaction = new Reaction();
    $reaction->reactable_type = 'message';
    $reaction->reactable_id = $messageId;
    $reaction->emoji_key = $emojiKey;
    $reaction->emoji_name = $emojiKey;
    $reaction->emoji_id = $emojiId;
    $reaction->count = $count;
    $reaction->count_burst = 0;
    $reaction->count_normal = $count;
    $reaction->save();
}

function dcMembership(string $identityId, string $kind, string $occurredAt): void
{
    $event = new MembershipEvent();
    $event->external_identity_id = $identityId;
    $event->kind = $kind;
    $event->occurred_at = CarbonImmutable::parse($occurredAt);
    $event->save();
}

it('identifica-se como a fonte discord', function (): void {
    expect(new DiscordSource()->key())->toBe('discord');
});

it('devolve resultado vazio sem dado no recorte', function (): void {
    expect(($this->collect)()->isEmpty())->toBeTrue();
});

it('conta mensagens do recorte, escondendo bots e mantendo linhas sem source_kind', function (): void {
    $alice = dcIdentity('Alice');
    $bob = dcIdentity('Bob');

    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02']);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-03']);
    Message::factory()->create(['external_identity_id' => $bob->id, 'sent_at' => '2026-06-02']);
    Message::factory()->create(['external_identity_id' => $bob->id, 'sent_at' => '2026-06-02', 'source_kind' => MessageSourceKind::Bot]);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-05-15']);

    $messages = dcSlide(($this->collect)(), 'discord.messages');

    expect($messages['total'])->toBe(3)
        ->and($messages['chatters'][0])->toMatchArray(['name' => 'Alice', 'messages' => 2])
        ->and($messages['chatters'][1])->toMatchArray(['name' => 'Bob', 'messages' => 1]);
});

it('mantém bots quando hideBots é falso', function (): void {
    $bot = dcIdentity('Robô');
    Message::factory()->create(['external_identity_id' => $bot->id, 'sent_at' => '2026-06-02', 'source_kind' => MessageSourceKind::Bot]);

    expect(dcSlide(($this->collect)(false), 'discord.messages')['total'])->toBe(1)
        ->and(($this->collect)()->isEmpty())->toBeTrue();
});

it('escopa mensagens por sent_at, não por created_at', function (): void {
    $alice = dcIdentity('Alice');
    // sent_at fora do recorte, created_at = agora (dentro): deve ficar de fora.
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-05-01']);

    expect(($this->collect)()->isEmpty())->toBeTrue();
});

it('destaca mensagens com reação e fixadas, e a mais reagida', function (): void {
    $alice = dcIdentity('Alice');

    $top = Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'reactions_total' => 12, 'content' => 'mensagem campeã']);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'reactions_total' => 0]);
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-03', 'is_pinned' => true]);

    $result = ($this->collect)();
    $messages = dcSlide($result, 'discord.messages');
    $topMessage = dcSlide($result, 'discord.top_message');

    expect($messages['with_reactions'])->toBe(1)
        ->and($messages['pinned'])->toBe(1)
        ->and($topMessage['messages'][0])->toMatchArray(['author' => 'Alice', 'reactions' => 12])
        ->and($topMessage['messages'][0]['content'])->toBe('mensagem campeã');
});

it('agrega o board de voz por participantes, XP e canais', function (): void {
    $alice = dcIdentity('Alice');
    $bob = dcIdentity('Bob');

    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'obtained_experience' => 10, 'occurred_at' => '2026-06-02']);
    Voice::factory()->create(['external_identity_id' => $alice->id, 'channel_name' => 'geral', 'obtained_experience' => 20, 'occurred_at' => '2026-06-03']);
    Voice::factory()->create(['external_identity_id' => $bob->id, 'channel_name' => 'estudos', 'obtained_experience' => 5, 'occurred_at' => '2026-06-02']);
    Voice::factory()->create(['external_identity_id' => $bob->id, 'channel_name' => 'geral', 'obtained_experience' => 99, 'occurred_at' => '2026-05-01']);

    $voice = dcSlide(($this->collect)(), 'discord.voice_board');

    expect($voice['participants'])->toBe(2)
        ->and($voice['xp'])->toBe(35)
        ->and($voice['channels'][0])->toMatchArray(['name' => 'geral', 'events' => 2, 'xp' => 30]);
});

it('conta novos membros e boosts pelo occurred_at', function (): void {
    $a = dcIdentity('A');
    $b = dcIdentity('B');
    $c = dcIdentity('C');

    dcMembership($a->id, 'user_join', '2026-06-02');
    dcMembership($b->id, 'user_join', '2026-06-03');
    dcMembership($c->id, 'boost_tier_1', '2026-06-03');
    dcMembership($a->id, 'user_join', '2026-05-01');

    $newMembers = dcSlide(($this->collect)(), 'discord.new_members');

    expect($newMembers['joins'])->toBe(2)
        ->and($newMembers['boosts'])->toBe(1);
});

it('agrega reações do recorte por emoji, escopando pelas mensagens do período', function (): void {
    $alice = dcIdentity('Alice');

    $inPeriod = Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02', 'reactions_total' => 8]);
    $outside = Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-05-01', 'reactions_total' => 50]);

    dcReaction($inPeriod->id, 'fire', 3);
    dcReaction($inPeriod->id, 'heart', 5, emojiId: '123');
    dcReaction($outside->id, 'tada', 50);

    $reactions = dcSlide(($this->collect)(), 'discord.reactions');

    expect($reactions['total'])->toBe(8)
        ->and($reactions['emojis'][0])->toMatchArray(['name' => 'heart', 'count' => 5, 'custom' => true])
        ->and($reactions['emojis'][1])->toMatchArray(['name' => 'fire', 'count' => 3, 'custom' => false]);
});

it('expõe os chips do cover só para o que teve dado', function (): void {
    $alice = dcIdentity('Alice');
    Message::factory()->create(['external_identity_id' => $alice->id, 'sent_at' => '2026-06-02']);

    $result = ($this->collect)();
    $labels = collect($result->headline->metrics)->map(fn ($metric): string => $metric->label)->all();

    expect($result->label)->toBe('Discord')
        ->and($labels)->toBe(['Mensagens']);
});
