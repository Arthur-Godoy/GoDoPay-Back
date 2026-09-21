<?php

namespace App\Services;

use App\Exceptions\AlreadyReturnedException;
use App\Exceptions\NotAccountOwnerException;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\Transfer;
use Illuminate\Support\Facades\DB;

class RevertTransfer
{
    use Transfer;

    public function __construct(
        private Transaction $transaction,
        private User $user
    ) {
        $this->payer = $transaction->accountReceiver;
        $this->receiver = $transaction->accountPayer;
        $this->amount = $transaction->amount;
    }

    public function makeRevert(): Transaction
    {
        $this->validateAlreadyReturned();

        DB::beginTransaction();
        try {
            $this->lockAccounts();

            $returnTransaction = $this->executeTransfer();
            $returnTransaction->return_of_transaction_id = $this->transaction->id;
            $returnTransaction->save();

            $this->transaction->update([
                'was_returned' => true,
                'is_returned_by_transaction_id' => $returnTransaction->id,
                'returned_at' => now(),
            ]);

            DB::commit();

            return $returnTransaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function validateAlreadyReturned(): void
    {
        if (
            $this->transaction->alreadyReturned()
            || $this->transaction->isReturnTransaction()
        ) {
            throw new AlreadyReturnedException;
        }
    }
}
