<?php

declare(strict_types=1);

namespace He4rt\Economy\Actions;

use He4rt\Economy\DTOs\DebitDTO;
use He4rt\Economy\Enums\TransactionType;
use He4rt\Economy\Exceptions\InsufficientBalanceException;
use He4rt\Economy\Models\Transaction;
use He4rt\Economy\Models\Wallet;

final class Debit
{
    /**
     * @throws InsufficientBalanceException
     */
    public function handle(DebitDTO $dto): Transaction
    {
        $wallet = Wallet::query()->findOrFail($dto->walletId);

        if ($wallet->balance < $dto->amount) {
            throw InsufficientBalanceException::forAmount($dto->amount, $wallet->balance);
        }

        $wallet->decrement('balance', $dto->amount);

        return Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Purchase,
            'amount' => -$dto->amount,
            'balance_after' => $wallet->fresh()->balance,
            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
            'description' => $dto->description,
        ]);
    }
}
