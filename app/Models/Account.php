<?php

namespace App\Models;

use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['user_id', 'balance', 'nickname'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory;

    public function credit(int $amount): bool
    {
        $account = $this->lockForUpdate()->find($this->id);

        return $account->update(['balance' => $account->balance + $amount]);
    }

    public function debit(int $amount): bool
    {
        $account = $this->lockForUpdate()->find($this->id);

        return $account->update(['balance' => $account->balance - $amount]);
    }

    public function hasAmmount(int $amount): bool
    {
        return $this->balance >= $amount;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function paidTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_payer_id');
    }

    public function receivedTransactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'account_receiver_id');
    }

    /**
     * @return Builder<Transaction>
     */
    public function transactions(): Builder
    {
        return Transaction::query()->where(function (Builder $query) {
            $query->where('account_payer_id', $this->id)
                ->orWhere('account_receiver_id', $this->id);
        });
    }
}
