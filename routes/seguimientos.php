<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\SeguimientoController;

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {

    // API ENDPOINTS - Seguimientos
    Route::prefix('api/seguimientos')->group(function () {
        Route::get('/tipos', [SeguimientoController::class, 'tipos'])->name('api.seguimientos.tipos');
        Route::get('/pendientes', [SeguimientoController::class, 'pendientes'])->name('api.seguimientos.pendientes');
        Route::get('/{negocioId}', [SeguimientoController::class, 'index'])->name('api.seguimientos.index');
        Route::post('/', [SeguimientoController::class, 'store'])->name('api.seguimientos.store');
        Route::get('/detalle/{id}', [SeguimientoController::class, 'show'])->name('api.seguimientos.show');
        Route::put('/{id}', [SeguimientoController::class, 'update'])->name('api.seguimientos.update');
        Route::put('/{id}/recordatorio', [SeguimientoController::class, 'marcarRecordatorio'])->name('api.seguimientos.recordatorio');
        Route::delete('/{id}', [SeguimientoController::class, 'destroy'])->name('api.seguimientos.destroy');
    });
});