<?php

namespace App\Observers;

use App\Models\Account;
use Illuminate\Support\Facades\Log;

class AccountLogObserver
{
    /**
     * Só registra depois do commit, para não logar o que sofreu rollback.
     */
    public bool $afterCommit = true;

    /**
     * Handle the Account "created" event.
     */
    public function created(Account $account): void
    {
        Log::info('Conta criada', $this->context($account));
    }

    /**
     * O original é sincronizado antes do callback pós-commit, então o valor
     * anterior precisa ser guardado enquanto a alteração ainda está pendente.
     */
    public function updating(Account $account): void
    {
        $account->saldoAnteriorParaLog = $account->getRawOriginal('balance');
        $account->nomeAnteriorParaLog = $account->getRawOriginal('nickname');
    }

    /**
     * Handle the Account "updated" event.
     */
    public function updated(Account $account): void
    {
        if ($account->wasChanged('balance')) {
            Log::info('Saldo alterado', [
                ...$this->context($account),
                'balance_anterior' => $account->saldoAnteriorParaLog,
            ]);
        }

        if ($account->wasChanged('nickname')) {
            Log::info('Conta renomeada', [
                ...$this->context($account),
                'nickname_anterior' => $account->nomeAnteriorParaLog,
            ]);
        }
    }

    /**
     * Handle the Account "deleted" event.
     */
    public function deleted(Account $account): void
    {
        Log::warning('Conta removida', $this->context($account));
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Account $account): array
    {
        return [
            'request_user_id' => request()->user()?->id,
            'id' => $account->id,
            'user_id' => $account->user_id,
            'nickname' => $account->nickname,
            'agency' => $account->agency,
            'number' => $account->number,
            'digit' => $account->digit,
            'balance' => $account->balance,
        ];
    }
}
