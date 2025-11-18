<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TerrenoController;

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {

    // API ENDPOINTS - Terrenos (Solo lectura)
    Route::prefix('api/terrenos')->group(function () {
        Route::get('/', [TerrenoController::class, 'index'])->name('api.terrenos.index');
        Route::get('/dropdown', [TerrenoController::class, 'dropdown'])->name('api.terrenos.dropdown');
        Route::get('/proyectos', [TerrenoController::class, 'proyectos'])->name('api.terrenos.proyectos');
        Route::get('/categorias', [TerrenoController::class, 'categorias'])->name('api.terrenos.categorias');
        Route::get('/{id}', [TerrenoController::class, 'show'])->name('api.terrenos.show');
    });
});