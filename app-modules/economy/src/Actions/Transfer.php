<?php

declare(strict_types=1);

namespace He4rt\Economy\Actions;

use He4rt\Economy\DTOs\CreditDTO;
use He4rt\Economy\DTOs\DebitDTO;
use He4rt\Economy\DTOs\TransferDTO;
use He4rt\Economy\Enums\TransactionType;
use He4rt\Economy\Exceptions\InsufficientBalanceException;
use He4rt\Economy\Models\Transaction;
use Illuminate\Support\Facades\DB;

final readonly class Transfer
{
    public function __construct(
        private Debit $debit,
        private Credit $credit,
    ) {}

    /**
     * @return array{debit: Transaction, credit: Transaction}
     *
     * @throws InsufficientBalanceException
     */
    public function handle(TransferDTO $dto): array
    {
        return DB::transaction(function () use ($dto): array {
            $debitTx = $this->debit->handle(new DebitDTO(
                walletId: $dto->fromWalletId,
                amount: $dto->amount,
                description: $dto->description,
            ));

            $creditTx = $this->credit->handle(new CreditDTO(
                walletId: $dto->toWalletId,
                amount: $dto->amount,
                description: $dto->description,
            ));

            $debitTx->update(['type' => TransactionType::Transfer]);
            $creditTx->update(['type' => TransactionType::Transfer]);

            return ['debit' => $debitTx, 'credit' => $creditTx];
        });
    }
}
