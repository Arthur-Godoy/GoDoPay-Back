<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\NotAccountOwnerException;
use App\Models\Account;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class Transfer
{

  public function __construct(
    public Account $payer,
    public Account $receiver,
    private User $user,
    private int $amount
  ) {}

  public function makeTransfer(): Transaction
  {
    DB::beginTransaction();
    try {
      $this->lockAccounts();
      $this->canTransfer();

      $transaction = Transaction::create([
        "type" => TransactionType::Transfer->value,
        "account_payer_id" => $this->payer->id,
        "account_receiver_id" => $this->receiver->id,
        "amount" => $this->amount,
      ]);

      $this->payer->debit($this->amount);
      $this->receiver->credit($this->amount);

      DB::commit();

      return $transaction;
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  private function lockAccounts(): void
  {
    $accounts = Account::whereIn("id", [$this->payer->id, $this->receiver->id])
      ->lockForUpdate()
      ->get()
      ->keyBy("id");

    $this->payer = $accounts[$this->payer->id];
    $this->receiver = $accounts[$this->receiver->id];
  }

  private function canTransfer(): void
  {
     $this->validateUserOwnAcount();
     $this->verifyHasAmmount();
  }  

  private function verifyHasAmmount(): void
  {
    if (!$this->payer->hasAmmount($this->amount)) {
      throw new InsufficientBalanceException();
    };
  }

  private function validateUserOwnAcount(): void
  {
    if ($this->payer->user->id !== $this->user->id) {
      throw new NotAccountOwnerException();
    }
  }
}