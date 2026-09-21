<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;

class TransactionPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function revert(User $user, Transaction $transaction): bool
    {
        return $transaction->accountReceiver->user_id === $user->id;
    }

    public function show(User $user, Transaction $transaction): bool
    {
        return $user->accounts()
            ->where('id', $transaction->accountPayer?->id)
            ->orWhere('id', $transaction->accountReceiver->id)
            ->exists();
    }
}
