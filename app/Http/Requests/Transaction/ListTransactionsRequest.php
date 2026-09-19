<?php

namespace App\Http\Requests\Transaction;

use App\Enums\TransactionType;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListTransactionsRequest extends FormRequest
{
    public const array SORTABLE_COLUMNS = ['created_at', 'amount', 'type'];

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
            'type' => ['nullable', Rule::enum(TransactionType::class)],
            'start_date' => ['nullable', 'date'],
            'end_date' => ['nullable', 'date', 'after_or_equal:start_date'],
            'order_by' => ['nullable', Rule::in(self::SORTABLE_COLUMNS)],
            'order' => ['nullable', Rule::in(['asc', 'desc'])],
        ];
    }

    public function filters(): array
    {
        return [
            'type' => $this->validated('type'),
            'start_date' => $this->validated('start_date'),
            'end_date' => $this->validated('end_date'),
            'order_by' => $this->validated('order_by') ?? 'created_at',
            'order' => $this->validated('order') ?? 'desc',
        ];
    }
}
