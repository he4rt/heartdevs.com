<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Members\GetMember;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\PendingRequest;

it('has the correct base url', function (): void {
    $connector = new DiscordConnector('test-bot-token');

    expect($connector->resolveBaseUrl())->toBe('https://discord.com/api/v10');
});

it('uses bot token authentication', function (): void {
    $connector = new DiscordConnector('test-bot-token');
    $request = new GetMember('guild', 'user');

    $pendingRequest = new PendingRequest($connector, $request);

    $authenticator = $pendingRequest->getAuthenticator();

    expect($authenticator)->toBeInstanceOf(TokenAuthenticator::class);
});

it('includes accept json header', function (): void {
    $connector = new DiscordConnector('test-bot-token');
    $request = new GetMember('guild', 'user');

    $pendingRequest = new PendingRequest($connector, $request);

    expect($pendingRequest->headers()->get('Accept'))->toBe('application/json');
});
