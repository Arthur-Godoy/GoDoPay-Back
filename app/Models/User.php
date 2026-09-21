<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable(['name', 'email', 'password', 'document', 'document_type', 'current_account_id'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    use HasApiTokens, HasFactory, HasUuids, Notifiable;

    public function accounts(): HasMany
    {
        return $this->hasMany(Account::class);
    }

    public function contacts(): HasMany
    {
        return $this->hasMany(Contact::class);
    }

    public function currentAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'current_account_id');
    }

    public function revertRequests(): HasManyThrough
    {
        return $this->hasManyThrough(
            RevertSolicitations::class,
            Account::class,
            'user_id',
            'requester_account_id',
        );
    }

    public function switchAccount(Account $account): bool
    {
        if ($account->user->id !== $this->id) {
            return false;
        }

        return $this->fill(['current_account_id' => $account->id])->save();
    }

    public function populateCurrentAccountIfNull(): void
    {
        if (empty($this->current_account_id)) {
            $this->switchAccount($this->accounts()->first());
        }
    }

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
