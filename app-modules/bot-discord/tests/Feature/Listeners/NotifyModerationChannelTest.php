<?php

declare(strict_types=1);

use He4rt\BotDiscord\Listeners\NotifyModerationChannel;
use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\CreateMessage;
use He4rt\Moderation\Cases\Events\CaseQueued;
use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Saloon\Http\Faking\MockClient;
use Saloon\Http\Faking\MockResponse;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    config()->set('discord.token', 'bot-token');
    config()->set('he4rt.discord.moderation.mod_channel_id', '1095115912820043829');
    config()->set('he4rt.discord.moderation.admin_role_ids', ['111111111111111111']);
    config()->set('he4rt.discord.moderation.mod_role_ids', ['222222222222222222']);
});

test('sends embed to mod channel when a new case is created', function (): void {
    $mockClient = new MockClient([
        CreateMessage::class => MockResponse::make([], 200),
    ]);

    $connector = resolve(DiscordConnector::class);
    $connector->withMockClient($mockClient);

    $case = ModerationCase::factory()->create();

    resolve(NotifyModerationChannel::class)->handle(new CaseQueued($case));

    $mockClient->assertSent(fn ($request) => $request instanceof CreateMessage
        && str_contains($request->resolveEndpoint(), '/channels/1095115912820043829/messages')
        && isset($request->body()->all()['embeds']));
});

test('embed contains the case id', function (): void {
    $mockClient = new MockClient([
        CreateMessage::class => MockResponse::make([], 200),
    ]);

    $connector = resolve(DiscordConnector::class);
    $connector->withMockClient($mockClient);

    $case = ModerationCase::factory()->create();

    resolve(NotifyModerationChannel::class)->handle(new CaseQueued($case));

    $mockClient->assertSent(function ($request) use ($case): bool {
        if (!$request instanceof CreateMessage) {
            return false;
        }

        $embeds = $request->body()->all()['embeds'] ?? [];

        return isset($embeds[0]['description']) && str_contains((string) $embeds[0]['description'], $case->id);
    });
});

test('message content includes role mentions for admins and mods', function (): void {
    $mockClient = new MockClient([
        CreateMessage::class => MockResponse::make([], 200),
    ]);

    $connector = resolve(DiscordConnector::class);
    $connector->withMockClient($mockClient);

    $case = ModerationCase::factory()->create();

    resolve(NotifyModerationChannel::class)->handle(new CaseQueued($case));

    $mockClient->assertSent(function ($request): bool {
        if (!$request instanceof CreateMessage) {
            return false;
        }

        $content = $request->body()->all()['content'] ?? '';

        return str_contains($content, '<@&111111111111111111>')
            && str_contains($content, '<@&222222222222222222>');
    });
});

test('does nothing when mod channel is not configured', function (): void {
    $mockClient = new MockClient([]);

    $connector = resolve(DiscordConnector::class);
    $connector->withMockClient($mockClient);

    config()->set('he4rt.discord.moderation.mod_channel_id', '');

    $case = ModerationCase::factory()->create();

    resolve(NotifyModerationChannel::class)->handle(new CaseQueued($case));

    $mockClient->assertNothingSent();
});
