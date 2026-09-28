<?php

declare(strict_types=1);

use He4rt\BotDiscord\BotDiscordServiceProvider;
use Laracord\Laracord;

it('defaults the bot locale to pt_BR', function (): void {
    expect(config('bot-discord.locale'))->toBe('pt_BR');
});

it('applies the bot locale when the bot boots', function (): void {
    config()->set('bot-discord.locale', 'en');
    app()->setLocale('pt_BR');

    $bot = Mockery::mock(Laracord::class);
    $bot->shouldReceive('disableHttpServer', 'discoverEvents', 'discoverCommands', 'discoverSlashCommands', 'discoverTasks', 'registerHook')
        ->andReturnSelf();

    new BotDiscordServiceProvider(app())->bot($bot);

    expect(app()->getLocale())->toBe('en');
});

it('translates the welcome copy in every supported locale', function (string $locale): void {
    app()->setLocale($locale);

    expect(__('bot-discord::welcome.embed.title'))->not->toStartWith('bot-discord::')
        ->and(__('bot-discord::welcome.dm.description', ['username' => 'valdirluz']))->toContain('valdirluz')
        ->and(__('bot-discord::welcome.fallback.description'))->not->toStartWith('bot-discord::');
})->with(['en', 'pt_BR']);
