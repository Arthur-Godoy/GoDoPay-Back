<?php

namespace App\Policies;

use App\Models\RevertSolicitations;
use App\Models\User;
use Illuminate\Auth\Access\Response;

class RevertSolicitationsPolicy
{
    /**
     * Determine whether the user can approve the model.
     */
    public function approve(User $user, RevertSolicitations $solicitation): Response
    {
        return $this->ownsAccount($user, $solicitation->approver_account_id)
            ? Response::allow()
            : Response::deny('Apenas quem recebeu a transação pode aprovar a devolução');
    }

    /**
     * Determine whether the user can reject the model.
     */
    public function reject(User $user, RevertSolicitations $solicitation): Response
    {
        return $this->ownsAccount($user, $solicitation->approver_account_id)
            ? Response::allow()
            : Response::deny('Apenas quem recebeu a transação pode rejeitar a devolução');
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, RevertSolicitations $solicitation): Response
    {
        return $this->ownsAccount($user, $solicitation->requester_account_id)
            ? Response::allow()
            : Response::deny('Apenas quem solicitou a devolução pode excluí-la');
    }

    private function ownsAccount(User $user, ?string $accountId): bool
    {
        return $accountId !== null
            && $user->accounts()->whereKey($accountId)->exists();
    }
}
