<?php

declare(strict_types=1);

use He4rt\Economy\Actions\Debit;
use He4rt\Economy\DTOs\DebitDTO;
use He4rt\Economy\Enums\TransactionType;
use He4rt\Economy\Exceptions\InsufficientBalanceException;
use He4rt\Economy\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('debit decreases wallet balance and creates transaction', function (): void {
    $wallet = Wallet::factory()->create(['balance' => 100]);

    $transaction = resolve(Debit::class)->handle(new DebitDTO(
        walletId: $wallet->id,
        amount: 30,
        description: 'test purchase',
    ));

    expect($wallet->fresh()->balance)->toBe(70)
        ->and($transaction->amount)->toBe(-30)
        ->and($transaction->balance_after)->toBe(70)
        ->and($transaction->type)->toBe(TransactionType::Purchase);
});

test('debit throws exception when insufficient balance', function (): void {
    $wallet = Wallet::factory()->create(['balance' => 10]);

    resolve(Debit::class)->handle(new DebitDTO(
        walletId: $wallet->id,
        amount: 50,
    ));
})->throws(InsufficientBalanceException::class);
