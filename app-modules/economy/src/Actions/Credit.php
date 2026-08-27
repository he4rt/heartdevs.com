<?php

declare(strict_types=1);

namespace He4rt\Economy\Actions;

use He4rt\Economy\DTOs\CreditDTO;
use He4rt\Economy\Enums\TransactionType;
use He4rt\Economy\Models\Transaction;
use He4rt\Economy\Models\Wallet;

final class Credit
{
    public function handle(CreditDTO $dto): Transaction
    {
        $wallet = Wallet::query()->findOrFail($dto->walletId);

        $wallet->increment('balance', $dto->amount);

        return Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'type' => TransactionType::Reward,
            'amount' => $dto->amount,
            'balance_after' => $wallet->fresh()->balance,
            'reference_type' => $dto->referenceType,
            'reference_id' => $dto->referenceId,
            'description' => $dto->description,
        ]);
    }
}
