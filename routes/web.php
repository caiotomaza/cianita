<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\VaultController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('/login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:5,1');
});

Route::post('/logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')->name('logout');

Route::middleware('auth')->group(function (): void {
    Route::view('/', 'app')->name('app');

    Route::prefix('api')->group(function (): void {
        Route::get('/vaults', [VaultController::class, 'index']);
        Route::post('/vaults', [VaultController::class, 'store']);
        Route::get('/vaults/{vault}/notes', [NoteController::class, 'index']);
        Route::post('/vaults/{vault}/notes', [NoteController::class, 'store']);
        Route::get('/vaults/{vault}/notes/{note}', [NoteController::class, 'show']);
        Route::put('/vaults/{vault}/notes/{note}', [NoteController::class, 'update']);
        Route::delete('/vaults/{vault}/notes/{note}', [NoteController::class, 'destroy']);
    });
});
