<?php

declare(strict_types=1);

use He4rt\Economy\Actions\CreateWallet;
use He4rt\Economy\Actions\Credit;
use He4rt\Economy\Actions\Debit;
use He4rt\Economy\DTOs\CreditDTO;
use He4rt\Economy\DTOs\DebitDTO;
use He4rt\Economy\Enums\Currency;
use He4rt\Gamification\Character\Models\Character;

test('full economy flow: create wallet, credit, debit', function (): void {
    $character = Character::factory()->create();

    $wallet = resolve(CreateWallet::class)->handle($character, Currency::Coin);

    expect($wallet->balance)->toBe(0)
        ->and($wallet->currency)->toBe(Currency::Coin)
        ->and($wallet->owner_type)->toBe('character')
        ->and($wallet->owner_id)->toBe($character->id);

    resolve(Credit::class)->handle(new CreditDTO(
        walletId: $wallet->id,
        amount: 100,
        description: 'welcome bonus',
    ));

    expect($wallet->fresh()->balance)->toBe(100);

    resolve(Debit::class)->handle(new DebitDTO(
        walletId: $wallet->id,
        amount: 25,
        description: 'badge purchase',
    ));

    expect($wallet->fresh()->balance)->toBe(75)
        ->and($wallet->transactions()->count())->toBe(2);
});

test('character can use HasWallet trait', function (): void {
    $character = Character::factory()->create();

    $wallet = $character->getOrCreateWallet();

    expect($wallet->owner_id)->toBe($character->id)
        ->and($wallet->currency)->toBe(Currency::Coin);

    $sameWallet = $character->getOrCreateWallet();
    expect($sameWallet->id)->toBe($wallet->id);
});
