<?php

namespace App\Services;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\NotAccountOwnerException;
use App\Exceptions\SameAccountTransferException;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\Transfer;
use Illuminate\Support\Facades\DB;

class MakeTransfer
{
    use Transfer;

    public function __construct(
        protected Account $payer,
        protected Account $receiver,
        private User $user,
        protected int $amount
    ) {}

    public function makeTransfer(): Transaction
    {
        $this->canTransfer();

        DB::beginTransaction();
        try {
            $this->lockAccounts();

            $transaction = $this->executeTransfer();

            DB::commit();

            return $transaction;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    private function canTransfer(): void
    {
        $this->isSameAccount();
        $this->validateUserOwnAcount();
        $this->verifyHasAmmount();
    }

    private function isSameAccount(): void
    {
        if ($this->payer->id === $this->receiver->id) {
            throw new SameAccountTransferException;
        }
    }

    private function verifyHasAmmount(): void
    {
        if (! $this->payer->hasAmmount($this->amount)) {
            throw new InsufficientBalanceException;
        }
    }

    private function validateUserOwnAcount(): void
    {
        if ($this->payer->user->id !== $this->user->id) {
            throw new NotAccountOwnerException;
        }
    }
}
