<?php

declare(strict_types=1);

use He4rt\Economy\Actions\Transfer;
use He4rt\Economy\DTOs\TransferDTO;
use He4rt\Economy\Enums\TransactionType;
use He4rt\Economy\Exceptions\InsufficientBalanceException;
use He4rt\Economy\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('transfer moves funds between wallets atomically', function (): void {
    $sender = Wallet::factory()->create(['balance' => 200]);
    $receiver = Wallet::factory()->create(['balance' => 50]);

    $result = resolve(Transfer::class)->handle(new TransferDTO(
        fromWalletId: $sender->id,
        toWalletId: $receiver->id,
        amount: 75,
        description: 'gift',
    ));

    expect($sender->fresh()->balance)->toBe(125)
        ->and($receiver->fresh()->balance)->toBe(125)
        ->and($result['debit']->type)->toBe(TransactionType::Transfer)
        ->and($result['credit']->type)->toBe(TransactionType::Transfer)
        ->and($result['debit']->amount)->toBe(-75)
        ->and($result['credit']->amount)->toBe(75);
});

test('transfer fails if sender has insufficient balance', function (): void {
    $sender = Wallet::factory()->create(['balance' => 10]);
    $receiver = Wallet::factory()->create(['balance' => 50]);

    resolve(Transfer::class)->handle(new TransferDTO(
        fromWalletId: $sender->id,
        toWalletId: $receiver->id,
        amount: 100,
    ));
})->throws(InsufficientBalanceException::class);
