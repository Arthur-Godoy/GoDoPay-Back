<?php

namespace App\Services;

use App\Models\Account;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class CreateAccount
{
    public const DEFAULT_NICKNAME = 'Conta principal';

    public function __construct(
        protected User $user,
        protected string $nickname
    ) {}

    public function createAccount(): Account
    {
        return DB::transaction(function () {
            $account = Account::create([
                'nickname' => $this->nickname,
                'agency' => '0001',
                'balance' => 0,
                'number' => $this->generateNumber(),
                'digit' => (string) random_int(0, 9),
                'user_id' => $this->user->id
            ]);

            $this->user->switchAccount($account);

            return $account;
        });
    }

    private function generateNumber(): string
    {
        do {
            $number = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Account::where('number', $number)->exists());

        return $number;
    }
}
