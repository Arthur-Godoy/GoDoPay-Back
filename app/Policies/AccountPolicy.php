<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class AccountPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function update(User $user, Account $account): Response
    {
        return $account->user->id === $user->id
            ? Response::allow()
            : Response::deny('Você não tem acesso a esta conta');
    }
}
