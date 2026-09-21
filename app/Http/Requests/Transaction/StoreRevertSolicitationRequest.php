<?php

namespace App\Http\Requests\Transaction;

use App\Enums\RevertSolicitationStatus;
use App\Models\RevertSolicitations;
use App\Models\Transaction;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreRevertSolicitationRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $transaction = $this->transaction();

                if ($transaction->alreadyReturned()) {
                    $validator->errors()->add('transaction', 'Esta transação já foi devolvida');
                }

                if ($transaction->isReturnTransaction()) {
                    $validator->errors()->add('transaction', 'Uma transação de devolução não pode ser devolvida');
                }

                if ($this->hasPendingSolicitation($transaction)) {
                    $validator->errors()->add('transaction', 'Já existe uma solicitação de devolução pendente para esta transação');
                }
            },
        ];
    }

    public function transaction(): Transaction
    {
        return $this->route('transaction');
    }

    private function hasPendingSolicitation(Transaction $transaction): bool
    {
        return RevertSolicitations::query()
            ->where('transaction_id', $transaction->id)
            ->where('status', RevertSolicitationStatus::Pending)
            ->exists();
    }
}
