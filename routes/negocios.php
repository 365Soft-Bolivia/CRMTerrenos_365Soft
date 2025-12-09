<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\NegocioController;
use App\Http\Controllers\Api\EmbudoController;
use Inertia\Inertia;

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {
    
    // VISTAS INERTIA
    Route::get('negocios', fn () => Inertia::render('Negocios/NegocioKanban'))->name('negocios.index');
    Route::get('negocios/embudos', fn () => Inertia::render('Negocios/EmbudoConfig'))->name('negocios.embudos');
    Route::get('negocios/{id}', fn ($id) => Inertia::render('Negocios/NegocioDetail', ['negocioId' => $id]))->name('negocios.show');

    // API ENDPOINTS - Negocios
    Route::prefix('api/negocios')->group(function () {
        Route::get('/', [NegocioController::class, 'index'])->name('api.negocios.index');
        Route::get('/tablero', [NegocioController::class, 'tablero'])->name('api.negocios.tablero');
        Route::get('/estadisticas', [NegocioController::class, 'estadisticas'])->name('api.negocios.estadisticas');
        // Rutas específicas con {id} deben ir antes de las genéricas
        Route::put('/{id}/etapa', [NegocioController::class, 'actualizarEtapa'])->name('api.negocios.actualizar-etapa');
        Route::get('/{id}', [NegocioController::class, 'show'])->name('api.negocios.show');
        Route::put('/{id}', [NegocioController::class, 'update'])->name('api.negocios.update');
        Route::delete('/{id}', [NegocioController::class, 'destroy'])->name('api.negocios.destroy');
    });

    // API ENDPOINTS - Embudos
    Route::prefix('api/embudos')->group(function () {
        Route::get('/', [EmbudoController::class, 'index'])->name('api.embudos.index');
        Route::get('/{id}', [EmbudoController::class, 'show'])->name('api.embudos.show');
        Route::post('/', [EmbudoController::class, 'store'])->name('api.embudos.store');
        Route::put('/{id}', [EmbudoController::class, 'update'])->name('api.embudos.update');
        Route::delete('/{id}', [EmbudoController::class, 'destroy'])->name('api.embudos.destroy');
        Route::post('/reordenar', [EmbudoController::class, 'reordenar'])->name('api.embudos.reordenar');
    });
});