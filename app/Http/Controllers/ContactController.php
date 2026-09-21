<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contact\StoreContactRequest;
use App\Models\Account;
use App\Models\Contact;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function list(Request $request): JsonResponse
    {
        try {
            $contacts = $request->user()->contacts()
                ->where('account_id', '!=', $request->user()->currentAccount->id)
                ->with(
                    [
                        'account:id,user_id,nickname,agency,number,digit',
                        'account.user:id,name',
                    ])
                ->get();

            return response()->json($contacts, 200);
        } catch (\Exception $e) {
            return response()->json('Não foi possível carregar seus contatos', 400);
        }
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        try {
            $account = Account::where($request->validated())->first();

            if (! $account) {
                return response()->json('Não foi possível encontrar a conta informada', 400);
            }

            $contact = Contact::firstOrCreate([
                'account_id' => $account->id,
                'user_id' => $request->user()->id,
            ]);

            return response()->json($contact, 201);
        } catch (\Exception $e) {
            return response()->json('Não foi possível adicionar o contato', 400);
        }
    }
}
