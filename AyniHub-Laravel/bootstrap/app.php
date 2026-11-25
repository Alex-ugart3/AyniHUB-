<?php

use App\Http\Middleware\EnsureRole;
use App\Http\Middleware\SecurityHeaders;
use App\Http\Middleware\SetLocale;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        // Cabeceras de seguridad (CSP, X-Frame-Options, etc.) en todas las respuestas web.
        $middleware->web(append: [SetLocale::class, SecurityHeaders::class]);
        $middleware->alias(['role' => EnsureRole::class]);
        // Si alguien sin sesión entra a una ruta protegida desde el navegador, va al login.
        $middleware->redirectGuestsTo('/#Login');
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        //
    })
    ->create();
