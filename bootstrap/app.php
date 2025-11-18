<?php

use App\Http\Middleware\HandleAppearance;
use App\Http\Middleware\HandleInertiaRequests;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Middleware\AddLinkHeadersForPreloadedAssets;
use Illuminate\Support\Facades\Route;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__ . '/../routes/web.php',
        commands: __DIR__ . '/../routes/console.php',
        health: '/up',
        then: function () {
            // ==================== RUTAS API ====================
            
            // Rutas de autenticación y configuración
            Route::middleware('web')
                ->group(base_path('routes/auth.php'));
                
            Route::middleware('web')
                ->group(base_path('routes/settings.php'));
            
            // Rutas API con prefijo /api
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/accesos.php'));
                
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/roles.php'));
                
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/leads.php'));
                
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/negocios.php'));
                
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/seguimientos.php'));
                
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/terrenos.php'));
                
            // ✅ RUTAS DE WHATSAPP
            Route::middleware('api')
                ->prefix('api')
                ->group(base_path('routes/whatsapp.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->encryptCookies(except: ['appearance', 'sidebar_state']);

        $middleware->web(append: [
            HandleAppearance::class,
            HandleInertiaRequests::class,
            AddLinkHeadersForPreloadedAssets::class,
        ]);

        // Registrar middleware personalizado de roles
        $middleware->alias([
            'role' => \App\Http\Middleware\RoleMiddleware::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions) {
        //
    })->create();