<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\ServiceProvider;

/**
 * Proveedor principal de la aplicación.
 * Aquí se registran servicios globales; en AyniHub define el límite de
 * intentos de autenticación (protección contra fuerza bruta).
 */
class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Máximo 5 intentos por minuto por contacto + IP, y 20 por minuto por IP en total
        // (frena también a quien prueba muchos correos distintos desde el mismo equipo).
        RateLimiter::for('auth', function (Request $request) {
            $respuesta = fn () => response()->json(
                ['message' => __('Demasiados intentos. Espera un minuto e inténtalo de nuevo.')], 429
            );

            return [
                Limit::perMinute(5)->by(strtolower((string) $request->input('contact')).'|'.$request->ip())->response($respuesta),
                Limit::perMinute(20)->by($request->ip())->response($respuesta),
            ];
        });
    }
}
