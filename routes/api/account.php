<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/accounts', [AccountController::class, 'store'])->name('account.store');
    Route::post('/deposit/{account}', [AccountController::class, 'deposit'])->middleware('can:update,account')->name('account.deposit');
});
