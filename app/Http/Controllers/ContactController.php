<?php

namespace App\Http\Controllers;

use App\Http\Requests\Contact\StoreContactRequest;
use App\Models\Account;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $contacts = $request->user()->contacts()->get();

        return response()->json($contacts, 200);
    }

    public function store(StoreContactRequest $request): JsonResponse
    {
        $account = Account::where($request->validated())->firstOrFail();

        $contact = $request->user()->contacts()->firstOrCreate(
            ['account_id' => $account->id],
            [
                'agency' => $account->agency,
                'number' => $account->number,
                'digit' => $account->digit,
            ],
        );

        return response()->json($contact, 201);
    }
}
