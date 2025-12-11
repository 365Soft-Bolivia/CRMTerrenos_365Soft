<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\TerrenoController;
use App\Http\Controllers\Api\MapaController;

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
        Route::post('/buscar-por-codigo', [TerrenoController::class, 'buscarPorCodigo']);

        // Rutas dinámicas (con {id}) 
        Route::get('/{id}', [TerrenoController::class, 'show']);

        //Ruta index
        Route::get('/', [TerrenoController::class, 'index']);
    });

    // API ENDPOINTS - Mapa (para selección de terrenos)
    Route::prefix('api/mapa')->group(function () {
        Route::get('/proyectos', [MapaController::class, 'getProyectos']);
        Route::get('/proyectos/{proyectoId}', [MapaController::class, 'getProyecto']);
        Route::get('/proyectos/{proyectoId}/barrios', [MapaController::class, 'getBarriosGeoJSON']);
        Route::get('/proyectos/{proyectoId}/cuadras', [MapaController::class, 'getCuadrasGeoJSON']);
        Route::get('/proyectos/{proyectoId}/terrenos', [MapaController::class, 'getTerrenosGeoJSON']);
        Route::get('/proyectos/{proyectoId}/terrenos-disponibles', [MapaController::class, 'getTerrenosDisponiblesGeoJSON']);
        Route::get('/proyectos/{proyectoId}/categorias', [MapaController::class, 'getCategorias']);
    });

});