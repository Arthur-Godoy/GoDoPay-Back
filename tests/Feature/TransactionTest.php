<?php

namespace Tests\Feature;

use App\Models\Account;
use App\Models\Contact;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TransactionTest extends TestCase
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
        $this->receiverAccount = $this->accountFor(
            $this->receiver,
            100000,
            'Conta Principal',
        );
    }

    public function test_completes_a_transfer(): void
    {
        $this->authenticate($this->payer);
        $initialReceiverBalance = $this->receiverAccount->balance;
        $initialPayerBalance = $this->payerAccount->balance;

        $contact = $this->accountToContact($this->receiverAccount);

        $this->authenticate($this->payer);

        $transaction = $this->transfer($contact['id'], 15000)->assertOk()->json();

        $this->assertSame($initialPayerBalance - 15000, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance + 15000, $this->receiverAccount->fresh()->balance);
    }

    public function test_should_fail_not_enought_money(): void
    {
        $contact = $this->accountToContact($this->receiverAccount);
        $initialReceiverBalance = $this->receiverAccount->balance;
        $initialPayerBalance = $this->payerAccount->balance;

        $response = $this->transfer($contact->id, $initialPayerBalance + 1)
            ->assertStatus(400)
            ->json();

        $this->assertSame('Saldo insuficiente para realizar a transferência', $response);
        $this->assertSame($initialPayerBalance, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance, $this->receiverAccount->fresh()->balance);
    }

    public function test_should_fail_same_account(): void
    {
        $contact = $this->accountToContact($this->payerAccount);
        $initialPayerBalance = $this->payerAccount->balance;

        $response = $this->transfer($contact->id, $initialPayerBalance + 1)
            ->assertStatus(400)
            ->json();

        $this->assertSame('Não é possível transferir para a mesma conta', $response);
        $this->assertSame($initialPayerBalance, $this->payerAccount->fresh()->balance);
    }

    public function test_should_fail_user_not_own_account(): void
    {
        $contact = $this->accountToContact($this->payerAccount);
        $initialPayerBalance = $this->payerAccount->balance;

        $this->authenticate($this->payer);

        $response = $this->postJson(route('transaction.transfer'), [
            'type' => 'transfer',
            'account_payer_id' => $this->receiverAccount->id,
            'contact_id' => $contact->id,
            'amount' => 5000,
        ])->assertStatus(403)->json();

        $this->assertSame('Você não tem acesso a esta conta', $response);
        $this->assertSame($initialPayerBalance, $this->payerAccount->fresh()->balance);
    }

    public function test_should_transafer_and_reverse(): void
    {
        $contact = $this->accountToContact($this->receiverAccount);
        $initialReceiverBalance = $this->receiverAccount->balance;
        $initialPayerBalance = $this->payerAccount->balance;

        $transaction = $this->transfer($contact->id, 5000)->assertOk()->json();

        $this->assertSame($initialPayerBalance - 5000, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance + 5000, $this->receiverAccount->fresh()->balance);

        $this->authenticate($this->receiver);

        $this->postJson(route('transaction.revert', $transaction['id']))->assertOk();

        $this->assertSame($initialPayerBalance, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance, $this->receiverAccount->fresh()->balance);
    }

    public function test_should_fail_revert_when_already_reverted(): void
    {
        $contact = $this->accountToContact($this->receiverAccount);
        $initialReceiverBalance = $this->receiverAccount->balance;
        $initialPayerBalance = $this->payerAccount->balance;

        $transaction = $this->transfer($contact->id, 5000)->assertOk()->json();

        $this->authenticate($this->receiver);

        $this->postJson(route('transaction.revert', $transaction['id']))->assertOk();

        $this->authenticate($this->receiver);

        $response = $this->postJson(route('transaction.revert', $transaction['id']))
            ->assertStatus(400)
            ->json();

        $this->assertSame('Transação não pode ser Revertida', $response);
        $this->assertSame($initialPayerBalance, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance, $this->receiverAccount->fresh()->balance);
    }

    public function test_should_fail_revert_a_return_transaction(): void
    {
        $contact = $this->accountToContact($this->receiverAccount);
        $initialReceiverBalance = $this->receiverAccount->balance;
        $initialPayerBalance = $this->payerAccount->balance;

        $transaction = $this->transfer($contact->id, 5000)->assertOk()->json();

        $this->authenticate($this->receiver);

        $returnTransaction = $this->postJson(route('transaction.revert', $transaction['id']))
            ->assertOk()
            ->json();

        $this->authenticate($this->payer);

        $response = $this->postJson(route('transaction.revert', $returnTransaction[0]['id']))
            ->assertStatus(400)
            ->json();

        $this->assertSame('Transação não pode ser Revertida', $response);
        $this->assertSame($initialPayerBalance, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance, $this->receiverAccount->fresh()->balance);
    }

    public function test_should_fail_revert_when_user_is_the_payer(): void
    {
        $contact = $this->accountToContact($this->receiverAccount);
        $initialReceiverBalance = $this->receiverAccount->balance;
        $initialPayerBalance = $this->payerAccount->balance;

        $transaction = $this->transfer($contact->id, 5000)->assertOk()->json();

        $this->authenticate($this->payer);

        $response = $this->postJson(route('transaction.revert', $transaction['id']))
            ->assertStatus(403)
            ->json();

        $this->assertSame('Apenas quem recebeu a transação pode devolvê-la', $response['message']);
        $this->assertSame($initialPayerBalance - 5000, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance + 5000, $this->receiverAccount->fresh()->balance);
    }

    public function test_should_fail_revert_when_user_is_a_stranger(): void
    {
        $contact = $this->accountToContact($this->receiverAccount);
        $initialReceiverBalance = $this->receiverAccount->balance;
        $initialPayerBalance = $this->payerAccount->balance;

        $transaction = $this->transfer($contact->id, 5000)->assertOk()->json();

        $stranger = User::factory()->create();
        $this->accountFor($stranger, 100000);

        $this->authenticate($stranger);

        $response = $this->postJson(route('transaction.revert', $transaction['id']))
            ->assertStatus(403)
            ->json();

        $this->assertSame('Apenas quem recebeu a transação pode devolvê-la', $response['message']);
        $this->assertSame($initialPayerBalance - 5000, $this->payerAccount->fresh()->balance);
        $this->assertSame($initialReceiverBalance + 5000, $this->receiverAccount->fresh()->balance);
    }

    private function accountFor(User $user, int $balance, ?string $nickname = null): Account
    {
        $attributes = ['balance' => $balance];

        if ($nickname) {
            $attributes['nickname'] = $nickname;
        }

        $account = Account::factory()->for($user)->create($attributes);

        $user->forceFill(['current_account_id' => $account->id])->save();

        return $account;
    }

    private function accountToContact(Account $account): Contact
    {
        return $this->payer->contacts()->create([
            'account_id' => $account->id,
            'agency' => $account->agency,
            'number' => $account->number,
            'digit' => $account->digit,
        ]);
    }

    private function transfer(string $contactId, int $amount): TestResponse
    {
        $this->authenticate($this->payer);

        return $this->postJson(route('transaction.transfer'), [
            'type' => 'transfer',
            'account_payer_id' => $this->payerAccount->id,
            'contact_id' => $contactId,
            'amount' => $amount,
        ]);
    }

    private function authenticate(User $user): void
    {
        app('auth')->forgetGuards();

        Sanctum::actingAs($user, ['access']);
    }
}
