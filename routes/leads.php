<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\LeadController;
use Inertia\Inertia;

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {
    
    // VISTAS INERTIA
    Route::get('leads', [LeadController::class, 'webIndex'])->name('leads.index');
    Route::get('leads/crear', [LeadController::class, 'webCreate'])->name('leads.create');
    Route::get('leads/{id}', [LeadController::class, 'webShow'])->name('leads.show');
    Route::get('leads/{id}/editar', [LeadController::class, 'webEdit'])->name('leads.edit');
    Route::delete('leads/{id}', [LeadController::class, 'destroy'])->name('leads.destroy');

    // API ENDPOINTS - Leads
    Route::prefix('api/leads')->group(function () {
        Route::get('/', [LeadController::class, 'index'])->name('api.leads.index');
        Route::post('/', [LeadController::class, 'store'])->name('api.leads.store');
        Route::get('/{id}', [LeadController::class, 'show'])->name('api.leads.show');
        Route::put('/{id}', [LeadController::class, 'update'])->name('api.leads.update');
        Route::delete('/{id}', [LeadController::class, 'destroy'])->name('api.leads.destroy');
    });
});