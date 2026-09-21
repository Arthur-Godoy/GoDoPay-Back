<?php

namespace App\Observers;

use App\Models\Transaction;
use Illuminate\Support\Facades\Log;

class TransactionObserver
{
    /**
     * Só registra depois do commit, para não logar o que sofreu rollback.
     */
    public bool $afterCommit = true;

    /**
     * Handle the Transaction "created" event.
     */
    public function created(Transaction $transaction): void
    {
        Log::info('Transação criada', $this->context($transaction));
    }

    /**
     * Handle the Transaction "updated" event.
     */
    public function updated(Transaction $transaction): void
    {
        if (! $transaction->wasChanged('was_returned')) {
            return;
        }

        Log::info('Transação estornada', [
            ...$this->context($transaction),
            'is_returned_by_transaction_id' => $transaction->is_returned_by_transaction_id,
            'returned_at' => $transaction->returned_at?->toIso8601String(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Transaction $transaction): array
    {
        return [
            'request_user_id' => request()->user()?->id,
            'id' => $transaction->id,
            'type' => $transaction->type->value,
            'amount' => $transaction->amount,
            'account_payer_id' => $transaction->account_payer_id,
            'account_payer' => $this->describeAccount($transaction->accountPayer),
            'account_receiver_id' => $transaction->account_receiver_id,
            'account_receiver' => $this->describeAccount($transaction->accountReceiver),
            'return_of_transaction_id' => $transaction->return_of_transaction_id,
        ];
    }

    private function describeAccount(?object $account): ?string
    {
        if (! $account) {
            return null;
        }

        return sprintf(
            '%s (Ag %s CC %s-%s)',
            $account->nickname,
            $account->agency,
            $account->number,
            $account->digit,
        );
    }
}
