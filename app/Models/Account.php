<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Account extends Model
{
    /** @use HasFactory<\Database\Factories\AccountFactory> */
    use HasFactory;

    public function credit(int $val): bool
    {
        $account = $this->lockForUpdate()->find($this->id);

       return $account->update(['balance'=> $account->balance + $val]);
    }

    public function debit(int $val): bool
    {
        $account = $this->lockForUpdate()->find($this->id);

       return $account->update(['balance'=> $account->balance + $val]);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
