<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionType;
use App\Models\Account;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MakeTransferRequest extends FormRequest
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
        return [
            'type' => ['required', Rule::enum(TransactionType::class)],
            'account_payer_id' => [
                'required',
                'uuid',
                'exists:accounts,id',
            ],
            'contact_id' => [
                'required',
                'uuid',
                Rule::exists('contacts', 'id')
                    ->where('user_id', $this->user()?->id),
            ],
            'amount' => ['required', 'integer', 'min:1', 'max:'.Account::MAX_AMOUNT_IN_CENTS],
        ];
    }
}
