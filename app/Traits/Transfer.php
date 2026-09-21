<?php

namespace App\Traits;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;

trait Transfer
{
    protected Account $payer;

    protected Account $receiver;

    protected int $amount;

    protected function lockAccounts(): void
    {
        $accounts = Account::whereIn('id', [$this->payer->id, $this->receiver->id])
            ->lockForUpdate()
            ->get()
            ->keyBy('id');

        $this->payer = $accounts[$this->payer->id];
        $this->receiver = $accounts[$this->receiver->id];
    }

    protected function executeTransfer(): Transaction
    {
        $transaction = Transaction::create([
            'type' => TransactionType::Transfer->value,
            'account_payer_id' => $this->payer->id,
            'account_receiver_id' => $this->receiver->id,
            'amount' => $this->amount,
        ]);

        $this->payer->debit($this->amount);
        $this->receiver->credit($this->amount);

        return $transaction;
    }
}
