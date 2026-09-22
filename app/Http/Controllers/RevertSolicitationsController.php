<?php

namespace App\Http\Controllers;

use App\Exceptions\SolicitationNotPendingException;
use App\Http\Requests\RevertSolicitation\ListRevertSolicitationsRequest;
use App\Models\Account;
use App\Models\RevertSolicitations;
use App\Services\RevertSolicitationService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class RevertSolicitationsController extends Controller
{
    public function index(ListRevertSolicitationsRequest $request, Account $account): JsonResponse
    {
        try {
            $direction = $request->validated('direction');
            $status = $request->validated('status');

            $solicitations = RevertSolicitations::query()
                ->where(function (Builder $query) use ($account, $direction) {
                    match ($direction) {
                        'sent' => $query->where('requester_account_id', $account->id),
                        'received' => $query->where('approver_account_id', $account->id),
                    };
                })
                ->when($status, fn (Builder $query) => $query->where('status', $status))
                ->with([
                    'requester:id,nickname,agency,number,digit',
                    'approver:id,nickname,agency,number,digit',
                    'transaction',
                ])
                ->latest()
                ->paginate(15);

            return response()->json([
                $solicitations,
            ], 200);
        } catch (\Exception $e) {
            return response()->json('Erro ao listar Solicitações de devolução', 200);
        }
    }

    public function approve(RevertSolicitations $solicitation): JsonResponse
    {
        try {
            DB::beginTransaction();
            (new RevertSolicitationService($solicitation))->approve();
            DB::commit();

            return response()->json('Devolução feita com sucesso', 200);
        } catch (SolicitationNotPendingException $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json('Erro ao aprovar devolução', 400);
        }
    }

    public function reject(RevertSolicitations $solicitation): JsonResponse
    {
        try {
            DB::beginTransaction();
            (new RevertSolicitationService($solicitation))->reject();
            DB::commit();

            return response()->json('Devolução rejeitada com sucesso', 200);
        } catch (SolicitationNotPendingException $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json('Erro ao rejeitar devolução', 400);
        }
    }

    public function destroy(RevertSolicitations $solicitation): JsonResponse
    {
        try {
            DB::beginTransaction();
            (new RevertSolicitationService($solicitation))->delete();
            DB::commit();

            return response()->json('Deletado', 200);
        } catch (SolicitationNotPendingException $e) {
            DB::rollBack();

            return response()->json($e->getMessage(), 400);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json('Erro ao deletar devolução', 400);
        }
    }
}
