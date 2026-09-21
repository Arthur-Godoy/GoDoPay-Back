<?php

namespace App\Http\Controllers;

use App\Enums\TransactionType;
use App\Http\Requests\Account\DepositRequest;
use App\Http\Requests\Account\StoreAccountRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\CreateAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        try {
            $accounts = $request->user()->accounts;

            return response()->json([
                $accounts,
            ], 200);
        } catch (\Exception $e) {
            return response()->json('Não foi possível carregar suas contas', 400);
        }
    }

    public function store(StoreAccountRequest $request): JsonResponse
    {
        $createAccountService = new CreateAccount(
            $request->user(),
            $request->validated('nickname')
        );

        $account = $createAccountService->createAccount();

        return response()->json($account, 201);
    }

    public function deposit(Account $account, DepositRequest $request): JsonResponse
    {
        DB::beginTransaction();

        try {
            $transaction = Transaction::create([
                'type' => TransactionType::Deposit->value,
                'account_receiver_id' => $account->id,
                'amount' => $request['amount'],
            ]);

            $account->credit($request['amount']);

            DB::commit();

            return response()->json([
                $transaction,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'Error while processing deposit',
            ], 400);
        }
    }
}
