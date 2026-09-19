<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\Account\DepositRequest;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Models\Account;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

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
            $number = str_pad((string) random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
        } while (Account::where('number', $number)->exists());

        return $number;
    }

    public function deposit(Account $account, DepositRequest $request)
    {
        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                "type" => TransactionType::Deposit->value,
                "account_receiver_id" => $account->id,
                "amount" => $request['amount']
            ]);

            $account->credit($request['amount']);

            DB::commit();

            return response()->json([
                $transaction
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                "Error while processing deposit"
            ], 400);
        }
    }
}
