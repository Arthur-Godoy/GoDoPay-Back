<?php

namespace App\Http\Controllers;

use App\Enums\RevertSolicitationStatus;
use App\Exceptions\AlreadyReturnedException;
use App\Exceptions\InsufficientBalanceException;
use App\Exceptions\NotAccountOwnerException;
use App\Exceptions\SameAccountTransferException;
use App\Http\Requests\Transaction\ListTransactionsRequest;
use App\Http\Requests\Transaction\MakeTransferRequest;
use App\Http\Requests\Transaction\StoreRevertSolicitationRequest;
use App\Models\Account;
use App\Models\Contact;
use App\Models\RevertSolicitations;
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
                    'accountPayer:id,nickname,agency,number,digit',
                    'accountReceiver:id,nickname,agency,number,digit',
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

            return response()->json($this->withAccounts($transaction), 200);
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
            $transactionService = new RevertTransfer($transaction);

            $revertTransaction = $transactionService->makeRevert();

            return response()->json([
                $this->withAccounts($revertTransaction),
            ], 200);
        } catch (NotAccountOwnerException $e) {
            return response()->json($e->getMessage(), 403);
        } catch (AlreadyReturnedException $e) {
            return response()->json($e->getMessage(), 400);
        } catch (\Exception $e) {
            return response()->json('Não foi possível concluir a transferência', 400);
        }
    }

    public function createRevertSolicitation(Transaction $transaction): JsonResponse
    {
        try {
            if ($transaction->revertSolicitation()) {
                return response()->json('Já existe solicitação para essa Transação', 400);
            }

            $solicitation = RevertSolicitations::create([
                'transaction_id' => $transaction->id,
                'requester_account_id' => $transaction->account_payer_id,
                'approver_account_id' => $transaction->account_receiver_id,
                'status' => RevertSolicitationStatus::Pending,
            ]);

            return response()->json($solicitation, 201);
        } catch (\Exception $e) {
            return response()->json('Não foi possível solicitar a devolução', 400);
        }
    }

    /**
     * Recarrega as contas apenas com os campos exibidos, evitando devolver
     * saldo e dono de contas de terceiros.
     */
    private function withAccounts(Transaction $transaction): Transaction
    {
        return $transaction->load([
            'accountPayer:id,nickname,agency,number,digit',
            'accountReceiver:id,nickname,agency,number,digit',
        ]);
    }

    public function show(Transaction $transaction)
    {
        try {
            $transaction = Transaction::whereId($transaction->id)
                ->with([
                    'accountPayer:id,nickname,agency,number,digit',
                    'accountReceiver:id,nickname,agency,number,digit',
                    'returnOfTransaction:id,amount,created_at',
                    'isReturnedByTransaction:id,amount,created_at',
                ])->first();

            return response()->json($transaction, 200);
        } catch (\Exception $e) {
            return response()->json('Transação não encontrada', 404);
        }
    }
}
