<?php

declare(strict_types=1);

use He4rt\IntegrationDiscord\Transport\DiscordOAuthConnector;
use He4rt\IntegrationDiscord\Transport\Requests\OAuth\ExchangeCodeForToken;
use Saloon\Enums\Method;
use Saloon\Http\PendingRequest;

it('uses POST method', function (): void {
    $request = new ExchangeCodeForToken('code-123', 'client-id', 'client-secret', 'https://example.com/callback');

    expect($request->getMethod())->toBe(Method::POST);
});

it('resolves the correct endpoint', function (): void {
    $request = new ExchangeCodeForToken('code-123', 'client-id', 'client-secret', 'https://example.com/callback');

    expect($request->resolveEndpoint())->toBe('/oauth2/token');
});

it('includes oauth parameters as form body', function (): void {
    $request = new ExchangeCodeForToken('code-123', 'client-id', 'client-secret', 'https://example.com/callback');

    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $pendingRequest = new PendingRequest($connector, $request);

    expect($pendingRequest->body()->all())->toBe([
        'grant_type' => 'authorization_code',
        'code' => 'code-123',
        'redirect_uri' => 'https://example.com/callback',
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
    ]);
});

it('omits redirect_uri from the body when null (Discord Activity flow)', function (): void {
    $request = new ExchangeCodeForToken('code-123', 'client-id', 'client-secret');

    $connector = new DiscordOAuthConnector('client-id', 'client-secret', 'https://example.com/callback');
    $pendingRequest = new PendingRequest($connector, $request);

    expect($pendingRequest->body()->all())->toBe([
        'grant_type' => 'authorization_code',
        'code' => 'code-123',
        'client_id' => 'client-id',
        'client_secret' => 'client-secret',
    ]);
});
