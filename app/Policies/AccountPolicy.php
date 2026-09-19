<?php

namespace App\Policies;

use App\Models\Account;
use App\Models\User;

class AccountPolicy
{
    /**
     * Determine whether the user can view the model.
     */
    public function ownThisAccount(User $user, Account $account): bool
    {
        return $account->user->id == $user->id;
    }
}
