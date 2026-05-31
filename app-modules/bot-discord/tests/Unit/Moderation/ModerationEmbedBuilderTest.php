<?php

declare(strict_types=1);

use He4rt\BotDiscord\Moderation\ModerationEmbedBuilder;
use He4rt\Moderation\Cases\Models\ModerationCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('buildCaseEmbed returns correct structure', function (): void {
    $case = ModerationCase::factory()->create([
        'content_snapshot' => [
            'text' => 'some offensive content',
            'metadata' => ['username' => 'baduser'],
        ],
    ]);

    $builder = new ModerationEmbedBuilder();
    $embed = $builder->buildCaseEmbed($case);

    expect($embed)
        ->toHaveKeys(['title', 'description', 'color', 'fields', 'timestamp', 'footer'])
        ->and($embed['title'])->toContain('baduser')
        ->and($embed['description'])->toContain($case->id)
        ->and($embed['color'])->toBe(0xFFA500)
        ->and($embed['footer']['text'])->toBe('He4rt Moderation System')
        ->and($embed['fields'])->toHaveCount(4)
        ->and($embed['fields'][0]['name'])->toBe('Platform')
        ->and($embed['fields'][1]['name'])->toBe('Source')
        ->and($embed['fields'][2]['name'])->toBe('Priority')
        ->and($embed['fields'][3]['name'])->toBe('Content')
        ->and($embed['fields'][3]['value'])->toBe('some offensive content');
});

test('buildCaseEmbed truncates content to 1024 chars', function (): void {
    $longText = str_repeat('a', 2000);

    $case = ModerationCase::factory()->create([
        'content_snapshot' => [
            'text' => $longText,
            'metadata' => ['username' => 'user'],
        ],
    ]);

    $builder = new ModerationEmbedBuilder();
    $embed = $builder->buildCaseEmbed($case);

    $contentField = collect($embed['fields'])->firstWhere('name', 'Content');

    expect((string) $contentField['value'])->toHaveLength(1024);
});

test('buildCaseEmbed handles null text gracefully', function (): void {
    $case = ModerationCase::factory()->create([
        'content_snapshot' => [
            'metadata' => ['username' => 'user'],
        ],
    ]);

    $builder = new ModerationEmbedBuilder();
    $embed = $builder->buildCaseEmbed($case);

    expect($embed['fields'])->toHaveCount(3);

    $fieldNames = array_column($embed['fields'], 'name');
    expect($fieldNames)->not->toContain('Content');
});

test('buildCaseEmbed uses Unknown when username is missing', function (): void {
    $case = ModerationCase::factory()->create([
        'content_snapshot' => ['text' => 'test', 'metadata' => []],
    ]);

    $builder = new ModerationEmbedBuilder();
    $embed = $builder->buildCaseEmbed($case);

    expect($embed['title'])->toContain('Unknown');
});

test('buildRoleMentions formats role IDs correctly', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', ['111111111111111111']);
    config()->set('he4rt.discord.moderation.mod_role_ids', ['222222222222222222']);

    $builder = new ModerationEmbedBuilder();
    $mentions = $builder->buildRoleMentions();

    expect($mentions)->toBe('<@&111111111111111111> <@&222222222222222222>');
});

test('buildRoleMentions returns empty string when no roles configured', function (): void {
    config()->set('he4rt.discord.moderation.admin_role_ids', []);
    config()->set('he4rt.discord.moderation.mod_role_ids', []);

    $builder = new ModerationEmbedBuilder();
    $mentions = $builder->buildRoleMentions();

    expect($mentions)->toBeEmpty();
});
