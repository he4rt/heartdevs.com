<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\GetCurrentUser;
use Saloon\Enums\Method;
use Saloon\Http\Auth\TokenAuthenticator;
use Saloon\Http\PendingRequest;

it('uses GET method', function (): void {
    $request = new GetCurrentUser('access-token-123');

    expect($request->getMethod())->toBe(Method::GET);
});

it('resolves the correct endpoint', function (): void {
    $request = new GetCurrentUser('access-token-123');

    expect($request->resolveEndpoint())->toBe('/users/@me');
});

it('uses bearer token authentication', function (): void {
    $request = new GetCurrentUser('access-token-123');

    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $pendingRequest = new PendingRequest($connector, $request);

    expect($pendingRequest->getAuthenticator())->toBeInstanceOf(TokenAuthenticator::class);
});
