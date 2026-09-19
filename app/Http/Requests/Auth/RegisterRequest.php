<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $document = preg_replace('/\D/', '', (string) $this->input('document'));

        $this->merge([
            'document' => $document,
            'document_type' => Str::length($document) === 14 ? 'cnpj' : 'cpf',
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'document' => ['required', 'string', 'regex:/^(\d{11}|\d{14})$/', 'unique:users,document'],
            'document_type' => ['required', 'in:cpf,cnpj'],
            'password' => ['required', 'string', 'confirmed', Password::defaults()],
        ];
    }
}
