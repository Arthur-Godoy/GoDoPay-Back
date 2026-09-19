<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\NotAccountOwnerException;
use App\Http\Requests\Transaction\ListTransactionsRequest;
use App\Http\Requests\Transaction\MakeTransferRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\Transaction;
use App\Services\MakeTransfer;
use App\Services\RevertTransfer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TransactionController extends Controller
{
    public function list(ListTransactionsRequest $request): JsonResponse
    {
        try {
            $transactions = Transaction::query()
                ->involvingAccount($request->user()->currentAccount)
                ->filter($request->filters())
                ->with([
                    'accountPayer',
                    'accountReceiver',
                    'returnOfTransaction',
                    'isReturnedByTransaction',
                ])
                ->simplePaginate(15);

            return response()->json([
                $transactions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'Error trying to list all transactions',
            ], 200);
        }
    }

    public function transfer(MakeTransferRequest $request): JsonResponse
    {
        try {
            $payer = Account::whereId($request['account_payer_id'])->firstOrFail();
            $contact = Contact::whereId($request['contact_id'])->firstOrFail();
            $receiver = $contact->account;

            $transferService = new MakeTransfer(
                $payer,
                $receiver,
                $request->user(),
                $request['amount']
            );

            $transaction = $transferService->makeTransfer();

            return response()->json($transaction, 200);
        } catch (InsufficientBalanceException $e) {
            return response()->json([$e->getMessage()], 400);
        } catch (NotAccountOwnerException $e) {
            return response()->json([$e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['Error while processing the transfer'], 400);
        }
    }

    public function revert(Transaction $transaction, Request $request)
    {
        try {
            $transactionService = new RevertTransfer(
                $transaction,
                $request->user()
            );

            $revertTransaction = $transactionService->makeRevert();

            return response()->json([
                $revertTransaction,
            ], 200);
        } catch (NotAccountOwnerException $e) {
            return response()->json([$e->getMessage()], 403);
        } catch (\Exception $e) {
            return response()->json(['Error while processing the transfer'], 400);
        }
    }

    public function show(Transaction $transaction) {}
}
