<?php

declare(strict_types=1);

use He4rt\BotDiscord\DTO\WelcomeContextDTO;
use He4rt\BotDiscord\Welcome\WelcomeEmbedBuilder;
use Laracord\Discord\Message;

/**
 * `Message` reads the bot identity on construction, so the constructor is
 * skipped to keep the embed build free of a live Discord connection.
 */
function welcomeMessage(): Message
{
    return new class extends Message
    {
        public function __construct()
        {
            $this->username('he4rt')->avatar('https://cdn.discordapp.com/avatars/1/bot.png');
        }
    };
}

function welcomeContext(?string $serverIconUrl = 'https://cdn.discordapp.com/icons/540/abc.png'): WelcomeContextDTO
{
    return new WelcomeContextDTO(
        userId: '332648215004446720',
        guildId: '540000000000000000',
        username: 'valdirluz',
        avatarUrl: null,
        serverIconUrl: $serverIconUrl,
    );
}

test('decorates the message with the welcome embed', function (): void {
    $message = (new WelcomeEmbedBuilder)->decorate(welcomeMessage(), welcomeContext(), '541443469642563585');

    $embed = $message->getEmbed();

    expect($embed['title'])->toBe(__('bot-discord::welcome.embed.title'))
        ->and($embed['description'])->toContain('valdirluz')
        ->and($embed['thumbnail']['url'])->toBe('https://cdn.discordapp.com/icons/540/abc.png')
        ->and($embed['fields'])->toHaveCount(1)
        ->and($embed['footer']['text'])->toContain(now()->format('Y'));
});

test('links the presentation button to the general channel', function (): void {
    $message = (new WelcomeEmbedBuilder)->decorate(welcomeMessage(), welcomeContext(), '541443469642563585');

    expect($message->getButtons())->toHaveCount(1);
});

test('omits the thumbnail when the guild has no icon', function (): void {
    $message = (new WelcomeEmbedBuilder)->decorate(welcomeMessage(), welcomeContext(serverIconUrl: null), '541443469642563585');

    expect($message->getEmbed()['thumbnail']['url'])->toBeNull();
});

test('reuses the same message for the public fallback', function (): void {
    $builder = new WelcomeEmbedBuilder;
    $context = welcomeContext();

    $message = $builder->decorate(welcomeMessage(), $context, '541443469642563585');
    $fallback = $builder->asFallback($message, $context);

    expect($fallback->getEmbed()['description'])->toBe(__('bot-discord::welcome.fallback.description'))
        ->and($fallback->getEmbed()['fields'])->toHaveCount(1)
        ->and($fallback->getButtons())->toHaveCount(1)
        ->and($fallback->build()->jsonSerialize()['content'])->toContain($context->mention());
});
