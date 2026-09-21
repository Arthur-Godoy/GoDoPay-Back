<?php

namespace App\Models;

use App\Observers\AccountLogObserver;
use Database\Factories\AccountFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[ObservedBy([AccountLogObserver::class])]
#[Fillable(['user_id', 'balance', 'nickname', 'agency', 'number', 'digit'])]
class Account extends Model
{
    /** @use HasFactory<AccountFactory> */
    use HasFactory, HasUuids;

    /** Teto do BIGINT signed do MySQL, em centavos. */
    public const int MAX_AMOUNT_IN_CENTS = 9223372036854775807;

    /** Valores anteriores capturados antes da gravação, usados pelo log. */
    public ?int $saldoAnteriorParaLog = null;

    public ?string $nomeAnteriorParaLog = null;

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
