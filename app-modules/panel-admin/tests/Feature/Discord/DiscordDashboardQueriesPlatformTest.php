<?php

declare(strict_types=1);

use He4rt\Activity\Message\Models\Message;
use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\ActivityPerDay;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\MessageHeatmap;
use He4rt\PanelAdmin\Marketing\Pages\Discord\Dashboard\Queries\TopChannels;

beforeEach(function (): void {
    Message::factory()->count(3)->create(['sent_at' => now()->subDay(), 'channel_id' => 'geral']);
    Message::factory()->count(5)->create([
        'sent_at' => now()->subDay(),
        'channel_id' => '227168488',
        'platform' => IdentityProvider::Twitch,
    ]);
});

test('a atividade por dia do Discord ignora o chat da Twitch', function (): void {
    expect(new ActivityPerDay(30)->get()->sum('msgs'))->toBe(3);
});

test('os canais mais ativos do Discord ignoram o chat da Twitch', function (): void {
    expect(new TopChannels(30)->get()->pluck('channel_id')->all())->toBe(['geral']);
});

test('o mapa de calor do Discord ignora o chat da Twitch', function (): void {
    expect(array_sum(array_column(new MessageHeatmap(30)->get(), 'value')))->toBe(3);
});
