<?php

namespace App\Http\Requests\RevertSolicitation;

use App\Enums\RevertSolicitationStatus;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListRevertSolicitationsRequest extends FormRequest
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
            'status' => ['nullable', Rule::enum(RevertSolicitationStatus::class)],
            'direction' => ['nullable', 'in:sent,received'],
        ];
    }
}
