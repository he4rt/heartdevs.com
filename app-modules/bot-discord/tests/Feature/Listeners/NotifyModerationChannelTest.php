<?php

declare(strict_types=1);

use He4rt\BotDiscord\Listeners\NotifyModerationChannel;
use He4rt\Moderation\Cases\Events\CaseCreated;
use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('discord.token', 'bot-token');
    config()->set('he4rt.discord.moderation.mod_channel_id', '1095115912820043829');
});

test('sends embed to mod channel when a new case is created', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $case = ModerationCase::factory()->create();

    new NotifyModerationChannel()->handle(new CaseCreated($case));

    Http::assertSent(fn ($req) => str_contains((string) $req->url(), '/channels/1095115912820043829/messages')
        && isset($req->data()['embeds']));
});

test('embed contains the case id', function (): void {
    Http::fake(['discord.com/*' => Http::response([], 200)]);

    $case = ModerationCase::factory()->create();

    new NotifyModerationChannel()->handle(new CaseCreated($case));

    Http::assertSent(function ($req) use ($case): bool {
        $embeds = $req->data()['embeds'] ?? [];

        return isset($embeds[0]['description']) && str_contains((string) $embeds[0]['description'], $case->id);
    });
});

test('does nothing when mod channel is not configured', function (): void {
    Http::fake();

    config()->set('he4rt.discord.moderation.mod_channel_id', '');

    $case = ModerationCase::factory()->create();

    new NotifyModerationChannel()->handle(new CaseCreated($case));

    Http::assertNothingSent();
});

test('does nothing when bot token is not configured', function (): void {
    Http::fake();

    config()->set('discord.token');
    config()->set('he4rt.discord.token');

    $case = ModerationCase::factory()->create();

    new NotifyModerationChannel()->handle(new CaseCreated($case));

    Http::assertNothingSent();
});
