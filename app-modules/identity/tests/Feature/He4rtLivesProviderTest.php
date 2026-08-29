<?php

declare(strict_types=1);

use He4rt\Identity\ExternalIdentity\Enums\IdentityProvider;

it('expõe o provider da plataforma de lives da he4rt', function (): void {
    $provider = IdentityProvider::from('he4rt-lives');

    expect($provider)->toBe(IdentityProvider::He4rtLives)
        ->and($provider->getLabel())->not->toBeEmpty()
        ->and($provider->getColor())->not->toBeNull();
});
