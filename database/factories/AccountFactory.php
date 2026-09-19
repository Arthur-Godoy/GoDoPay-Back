<?php

namespace Database\Factories;

use App\Models\Account;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Account>
 */
class AccountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'nickname' => fake()->words(2, true),
            'agency' => '0001',
            'number' => fake()->unique()->numerify('########'),
            'digit' => (string) fake()->randomDigit(),
            'balance' => 0,
        ];
    }
}
