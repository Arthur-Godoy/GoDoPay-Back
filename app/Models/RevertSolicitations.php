<?php

namespace App\Models;

use App\Enums\RevertSolicitationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['transaction_id', 'requester_account_id', 'approver_account_id', 'status'])]
class RevertSolicitations extends Model
{
    public function requester(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'requester_account_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'approver_account_id');
    }

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function approve(): void
    {
        $this->fill(['status' => RevertSolicitationStatus::Approved])->save();
    }

    public function reject(): void
    {
        $this->fill(['status' => RevertSolicitationStatus::Refused])->save();
    }

    public function isPending(): bool
    {
        return $this->status === RevertSolicitationStatus::Pending;
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => RevertSolicitationStatus::class,
        ];
    }
}
