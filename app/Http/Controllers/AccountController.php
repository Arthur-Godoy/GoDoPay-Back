<?php

namespace App\Http\Controllers;

use App\Http\Requests\Account\StoreAccountRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;

class AccountController extends Controller
{
    public function store(StoreAccountRequest $request): JsonResponse
    {
        $account = $request->user()->account()->create([
            'nickname' => $request->validated('nickname'),
            'agency' => '0001',
            'balance' => 0,
            'number' => $this->generateNumber(),
            'digit' => (string) random_int(0, 9),
        ]);

        return response()->json($account, 201);
    }

    private function generateNumber(): string
    {
        do {
            // Número da conta com 8 dígitos (00000000 a 99999999).
            $number = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Account::where('number', $number)->exists());

        return $number;
    }
}
