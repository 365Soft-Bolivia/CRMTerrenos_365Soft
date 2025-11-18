<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NegocioController;
use Inertia\Inertia;

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {
    
    // VISTAS INERTIA
    Route::get('negocios', fn () => Inertia::render('Negocios/NegocioKanban'))->name('negocios.index');
    Route::get('negocios/{id}', fn ($id) => Inertia::render('Negocios/NegocioDetail', ['negocioId' => $id]))->name('negocios.show');

    // API ENDPOINTS - Negocios
    Route::prefix('api/negocios')->group(function () {
        Route::get('/', [NegocioController::class, 'index'])->name('api.negocios.index');
        Route::get('/tablero', [NegocioController::class, 'tablero'])->name('api.negocios.tablero');
        Route::get('/estadisticas', [NegocioController::class, 'estadisticas'])->name('api.negocios.estadisticas');
        Route::get('/{id}', [NegocioController::class, 'show'])->name('api.negocios.show');
        Route::put('/{id}', [NegocioController::class, 'update'])->name('api.negocios.update');
        Route::put('/{id}/etapa', [NegocioController::class, 'actualizarEtapa'])->name('api.negocios.actualizar-etapa');
        Route::delete('/{id}', [NegocioController::class, 'destroy'])->name('api.negocios.destroy');
    });
});