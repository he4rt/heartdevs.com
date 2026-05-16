<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Messages\CreateMessage;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;

it('uses POST method', function (): void {
    $request = new CreateMessage('channel-123', ['content' => 'Hello']);

    expect($request->getMethod())->toBe(Method::POST);
});

it('resolves the correct endpoint', function (): void {
    $request = new CreateMessage('channel-123', ['content' => 'Hello']);

    expect($request->resolveEndpoint())->toBe('/channels/channel-123/messages');
});

it('includes the payload as json body', function (): void {
    $payload = ['content' => 'Hello', 'embeds' => [['title' => 'Test']]];
    $request = new CreateMessage('channel-123', $payload);

    $connector = new DiscordConnector('token');
    $pendingRequest = new PendingRequest($connector, $request);

    expect($pendingRequest->body()->all())->toBe($payload);
});
