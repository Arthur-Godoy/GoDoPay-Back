<?php

namespace Database\Factories;

use App\Enums\TransactionType;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'type' => TransactionType::Transfer,
            'account_payer_id' => Account::factory(),
            'account_receiver_id' => Account::factory(),
            'amount' => fake()->numberBetween(100, 100000),
            'was_returned' => false,
            'returned_at' => null,
            'return_of_transaction_id' => null,
            'is_returned_by_transaction_id' => null,
        ];
    }

    /**
     * Indicate that the transaction was returned.
     */
    public function returned(): static
    {
        return $this->state(fn (array $attributes) => [
            'was_returned' => true,
            'returned_at' => now(),
        ]);
    }

    public function deposit(): static
    {
        return $this->state(fn (array $attributes) => [
            'type' => TransactionType::Deposit,
            'account_payer_id' => null,
        ]);
    }
}
