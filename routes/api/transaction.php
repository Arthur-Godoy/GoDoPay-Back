<?php

use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/transfer', [TransactionController::class, 'transfer'])->name('transaction.transfer');
    Route::post('/deposit', [TransactionController::class, 'deposit'])->name('transaction.deposit');
    Route::get('/transactions', [TransactionController::class, 'list'])->name('transaction.list');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->middleware('can:show,transaction')->name('transaction.show');
    Route::post('/return/{transaction}', [TransactionController::class, 'revert'])->middleware(['can:revert,transaction', 'can:show,transaction'])->name('transaction.revert');
});
