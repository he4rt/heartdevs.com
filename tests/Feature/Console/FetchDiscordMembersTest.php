<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Sleep;

beforeEach(function (): void {
    config()->set('he4rt.discord.token', 'test-token');
    config()->set('he4rt.discord.guild_id', '123');

    Sleep::fake();
    Storage::fake('local');
});

test('gives up after five retries when Discord keeps rate limiting', function (): void {
    Http::fake([
        'discord.com/*' => Http::response(['retry_after' => 1], 429),
    ]);

    $this->artisan('discord:fetch-members')
        ->expectsOutputToContain('Still rate limited after 5 retries, giving up.')
        ->assertSuccessful();

    Http::assertSentCount(6);
    Sleep::assertSleptTimes(5);
    Storage::disk('local')->assertMissing('discord/members.json');
});

test('retries a rate limited request and continues once Discord answers', function (): void {
    Http::fake([
        'discord.com/api/v10/guilds/123?*' => Http::sequence()
            ->push(['retry_after' => 2], 429)
            ->push(['name' => 'He4rt', 'approximate_member_count' => 1]),
        'discord.com/api/v10/guilds/123/members*' => Http::response([
            ['user' => ['id' => '42', 'username' => 'dev', 'global_name' => 'Dev'], 'joined_at' => '2024-01-01T00:00:00Z'],
        ]),
    ]);

    $this->artisan('discord:fetch-members')->assertSuccessful();

    Sleep::assertSlept(fn ($duration): bool => (int) $duration->totalSeconds === 2, times: 1);
    Storage::disk('local')->assertExists('discord/members.json');
});
