<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\NotAccountOwnerException;
use App\Http\Requests\Transaction\MakeTransferRequest;
use App\Models\Account;
use App\Models\Transaction;
use App\Services\Transfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function transfer(MakeTransferRequest $request): JsonResponse
    {
        $payer = Account::whereId($request['account_payer_id'])->firstOrFail();
        $receiver = Account::whereId($request['account_receiver_id'])->firstOrFail();

        $transferService = new Transfer($payer, $receiver, $request->user(), $request['amount']);

        $transaction = $transferService->makeTransfer();

        try {
            return response()->json($transaction, 200);
        } catch (InsufficientBalanceException $e) {
            return response()->json([$e->getMessage()], 400);
        } catch (NotAccountOwnerException $e) {
            return response()->json([$e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(["Error while processing the transfer"], 400);
        }

    }

    public function deposit(Request $request)
    {
        
    }

    public function show(Transaction $transaction)
    {
        
    }
}
