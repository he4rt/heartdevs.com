<?php

declare(strict_types=1);

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use He4rt\PanelApp\Auth\Socialite\DiscordProvider;
use Illuminate\Http\Request;

it('maps the Discord user and keeps the verified flag', function (): void {
    $history = [];
    $handler = HandlerStack::create(new MockHandler([
        new Response(200, [], json_encode(['access_token' => 'token', 'scope' => 'identify email'])),
        new Response(200, [], json_encode(['id' => '80351110224678912', 'username' => 'ana', 'global_name' => 'Ana Lima', 'email' => 'ana@example.test', 'verified' => false])),
    ]));
    $handler->push(Middleware::history($history));

    $provider = new DiscordProvider(Request::create('/auth/discord/callback?code=abc'), 'client-id', 'client-secret', 'https://app.test/auth/discord/callback');
    $user = $provider->stateless()->setHttpClient(new Client(['handler' => $handler]))->user();

    expect($user->getId())->toBe('80351110224678912')
        ->and($user->getName())->toBe('Ana Lima')
        ->and($user->getNickname())->toBe('ana')
        ->and($user->getEmail())->toBe('ana@example.test')
        ->and($user->getRaw()['verified'])->toBeFalse()
        ->and((string) $history[0]['request']->getUri())->toBe('https://discord.com/api/oauth2/token')
        ->and((string) $history[1]['request']->getUri())->toBe('https://discord.com/api/users/@me')
        ->and($history[1]['request']->getHeaderLine('Authorization'))->toBe('Bearer token');
});

it('asks Discord for the identity and the email', function (): void {
    $provider = new DiscordProvider(Request::create('/'), 'client-id', 'client-secret', 'https://app.test/auth/discord/callback');

    $location = $provider->stateless()->redirect()->getTargetUrl();

    expect($location)->toStartWith('https://discord.com/oauth2/authorize?')
        ->toContain('scope=identify+email')
        ->toContain('redirect_uri='.urlencode('https://app.test/auth/discord/callback'));
});
