<?php

namespace App\Policies;

use App\Models\Transaction;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class TransactionPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function revert(User $user, Transaction $transaction): Response
    {
        return $transaction->accountReceiver->user_id === $user->id
            ? Response::allow()
            : Response::deny('Apenas quem recebeu a transação pode devolvê-la');
    }

    public function show(User $user, Transaction $transaction): Response
    {
        $belongsToUser = $user->accounts()
            ->whereIn('id', array_filter([
                $transaction->account_payer_id,
                $transaction->account_receiver_id,
            ]))
            ->exists();

        return $belongsToUser
            ? Response::allow()
            : Response::deny('Você não tem acesso a esta transação');
    }

    public function solicitateRevert(User $user, Transaction $transaction): Response
    {
        return $transaction->accountPayer?->user_id === $user->id
           ? Response::allow()
           : Response::deny('Apenas quem pagou a transação pode solicitar uma devolução');
    }
}
