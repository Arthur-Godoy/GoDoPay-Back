<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:5,1')->group(function () {
    Route::post('/register', [AuthController::class, 'register'])->name('auth.register');
    Route::post('/login', [AuthController::class, 'login'])->name('auth.login');
});

Route::post('/refresh', [AuthController::class, 'refresh'])->middleware(['auth:sanctum', 'abilities:refresh'])->name('auth.refresh');

Route::middleware(['auth:sanctum', 'abilities:access'])->group(function () {
    Route::get('/me', [AuthController::class, 'me'])->name('auth.me');
    Route::post('/logout', [AuthController::class, 'logout'])->name('auth.logout');
    Route::patch('/switch-account/{account}', [AuthController::class, 'switchAccount'])->middleware('can:update,account')->name('auth.switch-account');
});
