<?php

declare(strict_types=1);

use He4rt\Economy\Actions\Credit;
use He4rt\Economy\DTOs\CreditDTO;
use He4rt\Economy\Enums\TransactionType;
use He4rt\Economy\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('credit increases wallet balance and creates transaction', function (): void {
    $wallet = Wallet::factory()->create(['balance' => 100]);

    $transaction = resolve(Credit::class)->handle(new CreditDTO(
        walletId: $wallet->id,
        amount: 50,
        description: 'test reward',
    ));

    expect($wallet->fresh()->balance)->toBe(150)
        ->and($transaction->amount)->toBe(50)
        ->and($transaction->balance_after)->toBe(150)
        ->and($transaction->type)->toBe(TransactionType::Reward)
        ->and($transaction->description)->toBe('test reward');
});
