<?php

namespace App\Services;

use App\Exceptions\NotAccountOwnerException;
use App\Models\Transaction;
use App\Models\User;
use App\Traits\Transfer;
use Illuminate\Support\Facades\DB;

class RevertTransfer
{
  use Transfer;

  public function __construct(
    private Transaction $transaction,
    private User $user
  ) {
    $this->payer = $transaction->accountReceiver;
    $this->receiver = $transaction->accountPayer;
    $this->amount = $transaction->amount;
  }

  public function makeRevert(): Transaction
  {
    DB::beginTransaction();
    try {
      $this->lockAccounts();
      $this->validateUserOwnAcount();

      $returnTransaction = $this->executeTransfer();
      $returnTransaction->return_of_transaction_id = $this->transaction->id;
      $returnTransaction->save();

      $this->transaction->update([
        "was_returned" => true,
        "is_returned_by_transaction_id" => $returnTransaction->id,
        "returned_at" => now()
      ]);

      DB::commit();

      return $returnTransaction;
    } catch (\Exception $e) {
      DB::rollBack();
      throw $e;
    }
  }

  private function validateUserOwnAcount(): void
  {
    if (
      $this->payer->user->id !== $this->user->id
      || $this->receiver->user->id !== $this->user->id
    ) {
      throw new NotAccountOwnerException();
    }
  }
}
