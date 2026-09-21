<?php

use App\Http\Controllers\RevertSolicitationsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum'])->group(function () {
    Route::get('/{account}/solicitations', [RevertSolicitationsController::class, 'index'])->middleware('can:update,account')->name('solicitation.list');
    Route::put('/approve/{solicitation}', [RevertSolicitationsController::class, 'approve'])->middleware('can:approve,solicitation')->name('solicitation.approve');
    Route::put('/reject/{solicitation}', [RevertSolicitationsController::class, 'reject'])->middleware('can:reject,solicitation')->name('solicitation.reject');
    Route::delete('/delete/{solicitation}', [RevertSolicitationsController::class, 'destroy'])->middleware('can:delete,solicitation')->name('solicitation.destroy');
});
