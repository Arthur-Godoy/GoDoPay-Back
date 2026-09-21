<?php

namespace Tests\Feature;

use App\Enums\RevertSolicitationStatus;
use App\Models\Account;
use App\Models\Contact;
use App\Models\RevertSolicitations;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class RevertSolicitationTest extends TestCase
{
    use RefreshDatabase;

    private User $payer;

    private Account $payerAccount;

    private User $receiver;

    private Account $receiverAccount;

    protected function setUp(): void
    {
        parent::setUp();

        $this->payer = User::factory()->create();
        $this->payerAccount = $this->accountFor($this->payer, 100000);

        $this->receiver = User::factory()->create(['name' => 'Maria Souza']);
        $this->receiverAccount = $this->accountFor($this->receiver, 100000);
    }

    public function test_payer_creates_a_revert_solicitation(): void
    {
        $transaction = $this->transfer(5000);

        $this->authenticate($this->payer);

        $solicitation = $this->postJson(route('transaction.revert.solicitate', $transaction->id))
            ->assertStatus(201)
            ->json();

        $this->assertSame($transaction->id, $solicitation['transaction_id']);
        $this->assertSame($this->payerAccount->id, $solicitation['requester_account_id']);
        $this->assertSame($this->receiverAccount->id, $solicitation['approver_account_id']);
        $this->assertSame(RevertSolicitationStatus::Pending->value, $solicitation['status']);
    }

    public function test_should_fail_to_solicitate_when_user_is_the_receiver(): void
    {
        $transaction = $this->transfer(5000);

        $this->authenticate($this->receiver);

        $response = $this->postJson(route('transaction.revert.solicitate', $transaction->id))
            ->assertStatus(403)
            ->json();

        $this->assertSame('Apenas quem pagou a transação pode solicitar uma devolução', $response['message']);
        $this->assertDatabaseCount('revert_solicitations', 0);
    }

    public function test_should_fail_to_solicitate_when_user_is_a_stranger(): void
    {
        $transaction = $this->transfer(5000);

        $stranger = User::factory()->create();
        $this->accountFor($stranger, 100000);

        $this->authenticate($stranger);

        $this->postJson(route('transaction.revert.solicitate', $transaction->id))
            ->assertStatus(403);

        $this->assertDatabaseCount('revert_solicitations', 0);
    }

    public function test_should_fail_to_solicitate_twice_while_pending(): void
    {
        $transaction = $this->transfer(5000);

        $this->authenticate($this->payer);
        $this->postJson(route('transaction.revert.solicitate', $transaction->id))->assertStatus(201);

        $this->authenticate($this->payer);
        $this->postJson(route('transaction.revert.solicitate', $transaction->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors('transaction');

        $this->assertDatabaseCount('revert_solicitations', 1);
    }

    public function test_should_fail_to_solicitate_an_already_returned_transaction(): void
    {
        $transaction = $this->transfer(5000);

        $this->authenticate($this->receiver);
        $this->postJson(route('transaction.revert', $transaction->id))->assertOk();

        $this->authenticate($this->payer);
        $this->postJson(route('transaction.revert.solicitate', $transaction->id))
            ->assertStatus(422)
            ->assertJsonValidationErrors('transaction');
    }

    public function test_should_fail_to_solicitate_a_return_transaction(): void
    {
        $transaction = $this->transfer(5000);

        $this->authenticate($this->receiver);
        $returnTransaction = $this->postJson(route('transaction.revert', $transaction->id))
            ->assertOk()
            ->json();

        $this->authenticate($this->receiver);
        $this->postJson(route('transaction.revert.solicitate', $returnTransaction[0]['id']))
            ->assertStatus(422)
            ->assertJsonValidationErrors('transaction');
    }

    public function test_receiver_approves_the_solicitation(): void
    {
        $initialPayerBalance = $this->payerAccount->balance;
        $initialReceiverBalance = $this->receiverAccount->balance;

        $transaction = $this->transfer(5000);
        $solicitation = $this->solicitate($transaction);

        $this->authenticate($this->receiver);
        $this->putJson(route('solicitation.approve', $solicitation->id))->assertOk();

        $this->assertSame(RevertSolicitationStatus::Approved, $solicitation->fresh()->status);
        $this->assertTrue($transaction->fresh()->alreadyReturned());
        $this->assertSame($initialPayerBalance, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance, $this->receiverAccount->fresh()->balance);
    }

    public function test_should_fail_to_approve_when_user_is_the_payer(): void
    {
        $transaction = $this->transfer(5000);
        $solicitation = $this->solicitate($transaction);

        $this->authenticate($this->payer);
        $response = $this->putJson(route('solicitation.approve', $solicitation->id))
            ->assertStatus(403)
            ->json();

        $this->assertSame('Apenas quem recebeu a transação pode aprovar a devolução', $response['message']);
        $this->assertSame(RevertSolicitationStatus::Pending, $solicitation->fresh()->status);
    }

    public function test_receiver_rejects_the_solicitation(): void
    {
        $initialPayerBalance = $this->payerAccount->balance;

        $transaction = $this->transfer(5000);
        $solicitation = $this->solicitate($transaction);

        $this->authenticate($this->receiver);
        $this->putJson(route('solicitation.reject', $solicitation->id))->assertOk();

        $this->assertSame(RevertSolicitationStatus::Refused, $solicitation->fresh()->status);
        $this->assertFalse($transaction->fresh()->alreadyReturned());
        $this->assertSame($initialPayerBalance - 5000, $this->payerAccount->fresh()->balance);
    }

    public function test_should_fail_to_reject_when_user_is_the_payer(): void
    {
        $transaction = $this->transfer(5000);
        $solicitation = $this->solicitate($transaction);

        $this->authenticate($this->payer);
        $response = $this->putJson(route('solicitation.reject', $solicitation->id))
            ->assertStatus(403)
            ->json();

        $this->assertSame('Apenas quem recebeu a transação pode rejeitar a devolução', $response['message']);
    }

    public function test_should_fail_to_answer_a_solicitation_twice(): void
    {
        $transaction = $this->transfer(5000);
        $solicitation = $this->solicitate($transaction);

        $this->authenticate($this->receiver);
        $this->putJson(route('solicitation.reject', $solicitation->id))->assertOk();

        $this->authenticate($this->receiver);
        $response = $this->putJson(route('solicitation.approve', $solicitation->id))
            ->assertStatus(400)
            ->json();

        $this->assertSame('Esta solicitação de devolução já foi respondida', $response);
    }

    public function test_requester_deletes_the_solicitation(): void
    {
        $transaction = $this->transfer(5000);
        $solicitation = $this->solicitate($transaction);

        $this->authenticate($this->payer);
        $this->deleteJson(route('solicitation.destroy', $solicitation->id))->assertOk();

        $this->assertDatabaseMissing('revert_solicitations', ['id' => $solicitation->id]);
    }

    public function test_should_fail_to_delete_when_user_is_the_receiver(): void
    {
        $transaction = $this->transfer(5000);
        $solicitation = $this->solicitate($transaction);

        $this->authenticate($this->receiver);
        $response = $this->deleteJson(route('solicitation.destroy', $solicitation->id))
            ->assertStatus(403)
            ->json();

        $this->assertSame('Apenas quem solicitou a devolução pode excluí-la', $response['message']);
        $this->assertDatabaseHas('revert_solicitations', ['id' => $solicitation->id]);
    }

    public function test_requester_lists_own_solicitations(): void
    {
        $transaction = $this->transfer(5000);
        $this->solicitate($transaction);

        $this->authenticate($this->payer);
        $solicitations = $this->listFor(null);

        $this->assertCount(1, $solicitations);
        $this->assertSame($transaction->id, $solicitations[0]['transaction_id']);
    }

    public function test_lists_only_sent_solicitations(): void
    {
        [$sent] = $this->solicitationsInBothDirections();

        $this->authenticate($this->payer);
        $solicitations = $this->listFor('sent');

        $this->assertCount(1, $solicitations);
        $this->assertSame($sent->id, $solicitations[0]['id']);
    }

    public function test_lists_only_received_solicitations(): void
    {
        [, $received] = $this->solicitationsInBothDirections();

        $this->authenticate($this->payer);
        $solicitations = $this->listFor('received');

        $this->assertCount(1, $solicitations);
        $this->assertSame($received->id, $solicitations[0]['id']);
    }

    public function test_lists_both_directions_without_the_filter(): void
    {
        [$sent, $received] = $this->solicitationsInBothDirections();

        $this->authenticate($this->payer);
        $ids = array_column($this->listFor(null), 'id');

        $this->assertCount(2, $ids);
        $this->assertContains($sent->id, $ids);
        $this->assertContains($received->id, $ids);
    }

    public function test_should_fail_to_list_solicitations_of_someone_elses_account(): void
    {
        $this->solicitationsInBothDirections();

        $stranger = User::factory()->create();
        $this->accountFor($stranger, 100000);

        $this->authenticate($stranger);

        $response = $this->getJson(route('solicitation.list', [
            'account' => $this->payerAccount->id,
            'status' => RevertSolicitationStatus::Pending->value,
        ]))->assertStatus(403)->json();

        $this->assertSame('Você não tem acesso a esta conta', $response['message']);
    }

    public function test_lists_nothing_for_an_account_without_solicitations(): void
    {
        $this->solicitationsInBothDirections();

        $stranger = User::factory()->create();
        $strangerAccount = $this->accountFor($stranger, 100000);

        $this->authenticate($stranger);

        $this->assertCount(0, $this->listFor(null, $strangerAccount));
        $this->assertCount(0, $this->listFor('sent', $strangerAccount));
        $this->assertCount(0, $this->listFor('received', $strangerAccount));
    }

    public function test_should_fail_to_list_with_an_invalid_direction(): void
    {
        $this->authenticate($this->payer);

        $this->getJson(route('solicitation.list', [
            'account' => $this->payerAccount->id,
            'status' => RevertSolicitationStatus::Pending->value,
            'direction' => 'sideways',
        ]))->assertStatus(422)->assertJsonValidationErrors('direction');
    }

    /**
     * @return array{0: RevertSolicitations, 1: RevertSolicitations}
     */
    private function solicitationsInBothDirections(): array
    {
        $sentTransaction = $this->transfer(5000);
        $sent = $this->solicitate($sentTransaction);

        $receivedTransaction = $this->transferBack(3000);
        $received = $this->solicitateAs($this->receiver, $receivedTransaction);

        return [$sent, $received];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function listFor(?string $direction, ?Account $account = null): array
    {
        return $this->getJson(route('solicitation.list', array_filter([
            'account' => ($account ?? $this->payerAccount)->id,
            'status' => RevertSolicitationStatus::Pending->value,
            'direction' => $direction,
        ])))->assertOk()->json();
    }

    private function solicitate(Transaction $transaction): RevertSolicitations
    {
        return $this->solicitateAs($this->payer, $transaction);
    }

    private function solicitateAs(User $user, Transaction $transaction): RevertSolicitations
    {
        $this->authenticate($user);

        $solicitation = $this->postJson(route('transaction.revert.solicitate', $transaction->id))
            ->assertStatus(201)
            ->json();

        return RevertSolicitations::findOrFail($solicitation['id']);
    }

    private function transfer(int $amount): Transaction
    {
        return $this->transferBetween($this->payer, $this->payerAccount, $this->receiverAccount, $amount);
    }

    private function transferBack(int $amount): Transaction
    {
        return $this->transferBetween($this->receiver, $this->receiverAccount, $this->payerAccount, $amount);
    }

    private function transferBetween(User $user, Account $from, Account $to, int $amount): Transaction
    {
        $contact = $this->accountToContact($user, $to);

        $this->authenticate($user);

        $transaction = $this->postJson(route('transaction.transfer'), [
            'type' => 'transfer',
            'account_payer_id' => $from->id,
            'contact_id' => $contact->id,
            'amount' => $amount,
        ])->assertOk()->json();

        return Transaction::findOrFail($transaction['id']);
    }

    private function accountFor(User $user, int $balance): Account
    {
        $account = Account::factory()->for($user)->create(['balance' => $balance]);

        $user->forceFill(['current_account_id' => $account->id])->save();

        return $account;
    }

    private function accountToContact(User $owner, Account $account): Contact
    {
        return $owner->contacts()->create([
            'account_id' => $account->id,
            'agency' => $account->agency,
            'number' => $account->number,
            'digit' => $account->digit,
        ]);
    }

    private function authenticate(User $user): void
    {
        app('auth')->forgetGuards();

        Sanctum::actingAs($user, ['access']);
    }
}
