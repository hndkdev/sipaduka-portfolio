<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\API\KeluargaController;
use App\Http\Controllers\KeluargaAppscriptController;

Route::prefix('keluarga-appscript')->group(function () {
        Route::get('/', [KeluargaController::class, 'index'])->name('api/keluarga-appscript.index');
        Route::post('/', [KeluargaController::class, 'store']);
        Route::put('/{nomor_kk}', [KeluargaController::class, 'update']);
        Route::delete('/{nomor_kk}', [KeluargaController::class, 'destroy']);
    });

// Proxy to Apps Script (server-side) to avoid CORS/network issues
Route::get('/appscript/proxy', [KeluargaAppscriptController::class, 'apiIndex']);
