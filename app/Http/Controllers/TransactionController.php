<?php

namespace App\Http\Controllers;

use App\Exceptions\AlreadyReturnedException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\NotAccountOwnerException;
use App\Exceptions\SameAccountTransferException;
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
            $request->user()->populateCurrentAccountIfNull();

            $transactions = Transaction::query()
                ->involvingAccount($request->user()->currentAccount)
                ->filter($request->filters())
                ->with([
                    'accountPayer:id,agency,number,digit',
                    'accountReceiver:id,agency,number,digit',
                ])
                ->paginate(15);

            return response()->json([
                $transactions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json('Não foi possível carregar o extrato', 400);
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
        } catch (InsufficientBalanceException|SameAccountTransferException $e) {
            return response()->json($e->getMessage(), 400);
        } catch (NotAccountOwnerException $e) {
            return response()->json($e->getMessage(), 403);
        } catch (\Exception $e) {
            return response()->json('Não foi possível concluir a transferência', 400);
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
            return response()->json($e->getMessage(), 403);
        } catch (AlreadyReturnedException $e) {
            return response()->json($e->getMessage(), 400);
        } catch (\Exception $e) {
            return response()->json('Não foi possível concluir a transferência', 400);
        }
    }

    public function show(Transaction $transaction)
    {
        try {
            $transaction = Transaction::whereId($transaction->id)
                ->with([
                    'accountPayer',
                    'accountReceiver',
                    'returnOfTransaction',
                    'isReturnedByTransaction',
                ])->first();

            return response()->json($transaction, 200);
        } catch (\Exception $e) {
            return response()->json('Transação não encontrada', 404);
        }
    }
}
