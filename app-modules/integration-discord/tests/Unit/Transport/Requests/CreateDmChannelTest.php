<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Channels\CreateDmChannel;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;

it('uses POST method', function (): void {
    $request = new CreateDmChannel('user-123');

    expect($request->getMethod())->toBe(Method::POST);
});

it('resolves the correct endpoint', function (): void {
    $request = new CreateDmChannel('user-123');

    expect($request->resolveEndpoint())->toBe('/users/@me/channels');
});

it('includes recipient_id in the body', function (): void {
    $request = new CreateDmChannel('user-123');

    $connector = new DiscordConnector('token');
    $pendingRequest = new PendingRequest($connector, $request);

    expect($pendingRequest->body()->all())->toBe(['recipient_id' => 'user-123']);
});
