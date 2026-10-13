<?php

declare(strict_types=1);

use He4rt\IntegrationTwitch\Transport\TwitchHelixConnector;

test('login pela Twitch sem credenciais volta para a home em vez de dar 500', function (?string $clientId): void {
    config()->set('services.twitch.client_id', $clientId);
    config()->set('services.twitch.client_secret', 'fake-secret');

    $this->get(route('oauth.redirect', ['panel' => 'app', 'provider' => 'twitch']))
        ->assertRedirect('/');
})->with([
    'variável ausente' => [null],
    'variável vazia' => [''],
]);

test('conector Helix sem credenciais avisa o que falta configurar', function (): void {
    config()->set('services.twitch.client_id');

    expect(fn () => resolve(TwitchHelixConnector::class))
        ->toThrow(RuntimeException::class, 'Twitch OAuth credentials are not configured');
});
