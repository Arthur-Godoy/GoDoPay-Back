<?php

namespace App\Http\Controllers;

use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Account;
use App\Models\User;
use App\Services\CreateAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\NewAccessToken;
use Laravel\Sanctum\PersonalAccessToken;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->validated());

            $createAccountService = new CreateAccount($user, CreateAccount::DEFAULT_NICKNAME);
            $createAccountService->createAccount();

            return $user;
        });

        [$accessToken, $refreshToken] = $this->createTokenPair($user);

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'updated_at' => $user->updated_at,
            'access_token' => $accessToken->plainTextToken,
            'access_token_expires_at' => $accessToken->accessToken->expires_at->toIso8601String(),
            'refresh_token' => $refreshToken->plainTextToken,
            'refresh_token_expires_at' => $refreshToken->accessToken->expires_at->toIso8601String(),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $user = User::where('email', $request->validated('email'))->first();

        if (! $user || ! Hash::check($request->validated('password'), $user->password)) {
            return response()->json('Email ou Senha incorretos', 403);
        }

        [$accessToken, $refreshToken] = $this->createTokenPair($user);

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'current_account' => $user->currentAccount,
            'access_token' => $accessToken->plainTextToken,
            'refresh_token' => $refreshToken->plainTextToken,
        ], 200);
    }

    public function me(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'current_account' => $user->currentAccount,
        ], 200);
    }

    public function switchAccount(Request $request, Account $account): JsonResponse
    {
        $user = $request->user();

        $user->switchAccount($account);

        return response()->json([
            'name' => $user->name,
            'email' => $user->email,
            'created_at' => $user->created_at,
            'current_account' => $user->currentAccount,
        ], 200);
    }

    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();
        $refreshToken = $user->currentAccessToken();

        $user->tokens()->whereKey($this->pairedAccessTokenId($refreshToken))->delete();

        $accessToken = $this->createAccessToken($user);

        $refreshToken->update(['name' => $this->refreshTokenName($accessToken->accessToken)]);

        return response()->json([
            'access_token' => $accessToken->plainTextToken,
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $accessToken = $request->user()->currentAccessToken();

        $request->user()->tokens()->where('name', $this->refreshTokenName($accessToken))->delete();
        $accessToken->delete();

        return response()->json(null, JsonResponse::HTTP_NO_CONTENT);
    }

    private function createTokenPair(User $user): array
    {
        $accessToken = $this->createAccessToken($user);

        $refreshToken = $user->createToken(
            $this->refreshTokenName($accessToken->accessToken),
            ['refresh'],
            now()->addMinutes(config('sanctum.refresh_token_expiration')),
        );

        return [$accessToken, $refreshToken];
    }

    private function createAccessToken(User $user): NewAccessToken
    {
        return $user->createToken(
            'access',
            ['access'],
            now()->addMinutes(config('sanctum.access_token_expiration')),
        );
    }

    private function refreshTokenName(PersonalAccessToken $accessToken): string
    {
        return 'refresh:'.$accessToken->getKey();
    }

    private function pairedAccessTokenId(PersonalAccessToken $refreshToken): int
    {
        return (int) Str::after($refreshToken->name, 'refresh:');
    }
}
