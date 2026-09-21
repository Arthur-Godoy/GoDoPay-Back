<?php

namespace App\Models;

use App\Enums\TransactionType;
use Database\Factories\TransactionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['type', 'account_payer_id', 'account_receiver_id', 'amount', 'was_returned', 'returned_at', 'return_of_transaction_id', 'is_returned_by_transaction_id'])]
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, HasUuids;

    public function accountPayer(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_payer_id');
    }

    public function accountReceiver(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_receiver_id');
    }

    public function returnOfTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'return_of_transaction_id');
    }

    public function isReturnedByTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'is_returned_by_transaction_id');
    }

    public function alreadyReturned(): bool
    {
        return $this->was_returned;
    }

    public function isReturnTransaction(): bool
    {
        return $this->return_of_transaction_id !== null;
    }

    #[Scope]
    protected function involvingAccount(Builder $query, Account $account): void
    {
        $query->where(function (Builder $query) use ($account) {
            $query->where('account_payer_id', $account->id)
                ->orWhere('account_receiver_id', $account->id);
        });
    }

    #[Scope]
    protected function filter(Builder $query, array $filters): void
    {
        $query->when($filters['type'], fn (Builder $query, string $type) => $query->where('type', $type))
            ->when($filters['start_date'], fn (Builder $query, string $date) => $query->whereDate('created_at', '>=', $date))
            ->when($filters['end_date'], fn (Builder $query, string $date) => $query->whereDate('created_at', '<=', $date))
            ->orderBy($filters['order_by'], $filters['order']);
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'integer',
            'was_returned' => 'boolean',
            'returned_at' => 'datetime',
        ];
    }
}
