<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\RoleMiddleware;
use App\Http\Middleware\PermisoMiddleware;
use App\Http\Middleware\NoCache;

use Illuminate\Http\Request;


return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )

    ->withMiddleware(function (Middleware $middleware): void {

        //Registro de aliases de middleware
        $middleware->alias([
            'role' => RoleMiddleware::class,
            'permiso' => PermisoMiddleware::class,
            'no.cache' => NoCache::class,
        ]);


        //Cuando alguien no este autenticado, en vez de usar el default, se manda al login del personal
        $middleware->redirectGuestsTo(function (Request $request) {

            if ($request->is('estudiante/*')) {
                return route('grup_estudiante.name_login_estudiante');
            }

            // Solo aplica a rutas del personal/admin
            if ($request->is('personal/*') || $request->is('admin/*')) {
                return route('grup_personal.name_login_personal');
            }

            // Para cualquier otro guard/ruta protegida
            //return '/login'; // o '/', o la ruta que tú¿u quieras
            return route('grup_personal.name_login_estudiante');
        });


        //$middleware->append(SecurityHeaders::class);

    })

    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })->create();