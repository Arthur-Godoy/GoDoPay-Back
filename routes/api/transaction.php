<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/transfer', [TransactionController::class, 'transfer'])->name('transaction.transfer');
    Route::post('/deposit', [TransactionController::class, 'deposit'])->name('transaction.deposit');
    Route::get('/{transaction}', [TransactionController::class, 'show'])->name('transaction.show');
    Route::post('/return/{transaction}', [TransactionController::class, 'revert'])->name('transaction.revert');
});
