<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('auth/Login');
})->name('home');

Route::get('dashboard', function () {
    return Inertia::render('Dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

// Rutas protegidas por rol de administrador
Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {

    //VISTAS INERTIA
    Route::get('proyectos', fn () => Inertia::render('Proyectos'))->name('proyectos');
    Route::get('terrenos', fn () => Inertia::render('Terrenos'))->name('terrenos');
    Route::get('categorias', fn () => Inertia::render('Categorias'))->name('categorias');
    
    // ✅ AGREGAR ESTA LÍNEA - RUTA DE VISTA DE WHATSAPP
    Route::get('whatsapp', fn () => Inertia::render('Whatsapp/WhatsappMain'))->name('whatsapp');
    
    Route::get('whatsapp/qr', fn () => Inertia::render('Whatsapp/WhatsappQRCode'))->name('whatsapp.qr');


    
        
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
require __DIR__.'/accesos.php';
require __DIR__.'/roles.php';
require __DIR__.'/leads.php';
require __DIR__.'/negocios.php';
require __DIR__.'/seguimientos.php';
require __DIR__.'/terrenos.php';
// require __DIR__.'/whatsapp.php'; // ❌ COMENTAR O ELIMINAR ESTA LÍNEA (ya está en bootstrap/app.php)