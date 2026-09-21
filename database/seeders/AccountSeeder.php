<?php

namespace Database\Seeders;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Database\Seeder;

class AccountSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'test@example.com')->firstOrFail();

        if ($user->accounts()->exists()) {
            return;
        }

        $firstAccount = Account::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'Conta Padrão',
        ]);

        $secondAccount = Account::factory()->create([
            'user_id' => $user->id,
            'nickname' => 'Conta Secundária',
        ]);

        $user->switchAccount($firstAccount);

        Contact::factory()->create([
            'user_id' => $user->id,
            'account_id' => $secondAccount->id,
        ]);
    }
}
