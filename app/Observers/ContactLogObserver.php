<?php

namespace App\Observers;

use App\Models\Contact;
use Illuminate\Support\Facades\Log;

class ContactLogObserver
{
    /**
     * Só registra depois do commit, para não logar o que sofreu rollback.
     */
    public bool $afterCommit = true;

    /**
     * Handle the Contact "created" event.
     */
    public function created(Contact $contact): void
    {
        Log::info('Contato adicionado', $this->context($contact));
    }

    /**
     * Handle the Contact "deleted" event.
     */
    public function deleted(Contact $contact): void
    {
        Log::info('Contato removido', $this->context($contact));
    }

    /**
     * @return array<string, mixed>
     */
    private function context(Contact $contact): array
    {
        $account = $contact->account;

        return [
            'request_user_id' => request()->user()?->id,
            'id' => $contact->id,
            'user_id' => $contact->user_id,
            'account_id' => $contact->account_id,
            'agency' => $account?->agency,
            'number' => $account?->number,
            'digit' => $account?->digit,
        ];
    }
}
