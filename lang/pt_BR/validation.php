<?php

return [
    'after_or_equal' => 'O campo :attribute deve ser uma data posterior ou igual a :date.',
    'date' => 'O campo :attribute não é uma data válida.',
    'email' => 'O campo :attribute deve ser um endereço de e-mail válido.',
    'confirmed' => 'A confirmação do campo :attribute não confere.',
    'enum' => 'O valor selecionado para :attribute é inválido.',
    'exists' => 'Não encontramos uma conta com os dados informados.',
    'in' => 'O valor selecionado para :attribute é inválido.',
    'integer' => 'O campo :attribute deve ser um número inteiro.',
    'min' => [
        'numeric' => 'O campo :attribute deve ser no mínimo :min.',
        'string' => 'O campo :attribute deve ter no mínimo :min caracteres.',
    ],
    'max' => [
        'numeric' => 'O campo :attribute não pode ser maior que :max.',
        'string' => 'O campo :attribute não pode ter mais que :max caracteres.',
    ],
    'prohibited_if' => 'O campo :attribute é proibido neste caso.',
    'required' => 'O campo :attribute é obrigatório.',
    'size' => [
        'numeric' => 'O campo :attribute deve ser :size.',
        'string' => 'O campo :attribute deve ter :size caracteres.',
    ],
    'string' => 'O campo :attribute deve ser um texto.',
    'unique' => 'Este :attribute já está em uso.',
    'uuid' => 'O campo :attribute deve ser um UUID válido.',

    'attributes' => [
        'agency' => 'agência',
        'amount' => 'valor',
        'contact_id' => 'contato',
        'account_payer_id' => 'conta de origem',
        'digit' => 'dígito',
        'document' => 'CPF ou CNPJ',
        'email' => 'e-mail',
        'end_date' => 'data final',
        'name' => 'nome',
        'nickname' => 'apelido',
        'number' => 'número da conta',
        'password' => 'senha',
        'start_date' => 'data inicial',
        'type' => 'tipo',
    ],
];
