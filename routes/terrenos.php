<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TerrenoController;

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {

    Route::prefix('api/terrenos')->group(function () {

        // API ENDPOINTS - Terrenos 
        Route::get('/dropdown', [TerrenoController::class, 'dropdown']);
        Route::get('/proyectos', [TerrenoController::class, 'proyectos']);
        Route::get('/categorias', [TerrenoController::class, 'categorias']);
        Route::get('/barrios', [TerrenoController::class, 'barrios']);
        Route::get('/cuadras', [TerrenoController::class, 'cuadras']);
        Route::get('/por-cuadra', [TerrenoController::class, 'porCuadra']);
        Route::get('/buscar-por-codigo', [TerrenoController::class, 'buscarPorCodigo']);

        // Rutas dinámicas (con {id}) 
        Route::get('/{id}', [TerrenoController::class, 'show']);

        //Ruta index
        Route::get('/', [TerrenoController::class, 'index']);
    });

});
