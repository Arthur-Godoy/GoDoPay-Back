<?php

use App\Http\Controllers\AccountController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/account/create', [AccountController::class, 'store'])->name('account.store');
    Route::get('/accounts', [AccountController::class, 'list'])->name('account.list');
    Route::post('/deposit/{account}', [AccountController::class, 'deposit'])->middleware('can:update,account')->name('account.deposit');
});
