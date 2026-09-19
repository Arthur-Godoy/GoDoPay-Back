<?php

namespace App\Models;

use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['user_id', 'balance', 'nickname'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    public function credit(int $val): bool
    {
        $account = $this->lockForUpdate()->find($this->id);

        return $account->update(['balance' => $account->balance + $val]);
    }

    public function debit(int $val): bool
    {
        $account = $this->lockForUpdate()->find($this->id);

        return $account->update(['balance' => $account->balance - $val]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
