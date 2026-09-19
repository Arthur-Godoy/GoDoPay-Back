<?php

use App\Models\Account;
use App\Models\User;
use App\Services\CreateAccount;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

test('register creates a default account and sets it as current account', function () {
    $this->postJson(route('auth.register'), [
        'name' => 'Fulano',
        'email' => 'fulano@example.com',
        'document' => '12345678901',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertCreated();

    $user = User::where('email', 'fulano@example.com')->first();
    $account = $user->account()->sole();

    expect($account->nickname)->toBe(CreateAccount::DEFAULT_NICKNAME)
        ->and($account->balance)->toBe(0)
        ->and($user->current_account_id)->toBe($account->id);
});

test('register does not create user nor account when request is invalid', function () {
    $this->postJson(route('auth.register'), [
        'name' => 'Fulano',
        'email' => 'fulano@example.com',
    ])->assertUnprocessable();

    expect(User::count())->toBe(0)
        ->and(Account::count())->toBe(0);
});

test('me returns current account id', function () {
    $user = User::factory()->create();
    $account = Account::factory()->for($user)->create();
    $user->switchAccount($account);

    Sanctum::actingAs($user, ['access']);

    $this->getJson(route('auth.me'))->assertOk()->assertJsonPath('current_account_id', $account->id);
});

test('creating an account switches current account to it', function () {
    $user = User::factory()->create();
    $user->switchAccount(Account::factory()->for($user)->create());

    Sanctum::actingAs($user, ['access']);

    $response = $this->postJson(route('account.store'), ['nickname' => 'Nova'])->assertCreated();

    expect($user->fresh()->current_account_id)->toBe($response->json('id'));
});

test('user can switch to own account', function () {
    $user = User::factory()->create();
    $user->switchAccount(Account::factory()->for($user)->create());
    $otherOwnAccount = Account::factory()->for($user)->create();

    Sanctum::actingAs($user, ['access']);

    $this->patchJson(route('auth.switch-account', $otherOwnAccount))
        ->assertOk()
        ->assertJsonPath('current_account_id', $otherOwnAccount->id);

    expect($user->fresh()->current_account_id)->toBe($otherOwnAccount->id);
});

test('user cannot switch to an account from another user', function () {
    $user = User::factory()->create();
    $ownAccount = Account::factory()->for($user)->create();
    $user->switchAccount($ownAccount);
    $foreignAccount = Account::factory()->create();

    Sanctum::actingAs($user, ['access']);

    $this->patchJson(route('auth.switch-account', $foreignAccount))->assertForbidden();

    expect($user->fresh()->current_account_id)->toBe($ownAccount->id);
});

test('switch account requires authentication', function () {
    $this->patchJson(route('auth.switch-account', Account::factory()->create()))->assertUnauthorized();
});
