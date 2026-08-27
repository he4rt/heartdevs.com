<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\DiscordConnector;
use He4rt\IntegrationDiscord\Transport\Requests\Members\ModifyMember;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;

it('uses PATCH method', function (): void {
    $request = new ModifyMember('123456', '789012', ['nick' => 'new-nick']);

    expect($request->getMethod())->toBe(Method::PATCH);
});

it('resolves the correct endpoint', function (): void {
    $request = new ModifyMember('123456', '789012', ['nick' => 'new-nick']);

    expect($request->resolveEndpoint())->toBe('/guilds/123456/members/789012');
});

it('includes the payload as json body', function (): void {
    $payload = ['nick' => 'new-nick', 'roles' => ['role-1']];
    $request = new ModifyMember('123456', '789012', $payload);

    $connector = new DiscordConnector('token');
    $pendingRequest = new PendingRequest($connector, $request);

    expect($pendingRequest->body()->all())->toBe($payload);
});
