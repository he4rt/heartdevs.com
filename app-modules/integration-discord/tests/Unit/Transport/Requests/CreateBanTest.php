<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Bans\CreateBan;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;

it('uses PUT method', function (): void {
    $request = new CreateBan('123456', '789012', 86_400);

    expect($request->getMethod())->toBe(Method::PUT);
});

it('resolves the correct endpoint', function (): void {
    $request = new CreateBan('123456', '789012', 86_400);

    expect($request->resolveEndpoint())->toBe('/guilds/123456/bans/789012');
});

it('includes delete_message_seconds in the body', function (): void {
    $request = new CreateBan('123456', '789012', 86_400);

    $connector = new DiscordConnector('token');
    $pendingRequest = new PendingRequest($connector, $request);

    expect($pendingRequest->body()->all())->toBe(['delete_message_seconds' => 86_400]);
});
