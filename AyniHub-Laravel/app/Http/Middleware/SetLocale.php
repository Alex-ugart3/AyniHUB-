<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/** Usa el idioma elegido en la página (cabecera X-Lang: es | qu) para los mensajes del servidor. */
class SetLocale
{
    public function handle(Request $request, Closure $next): Response
    {
        $lang = $request->header('X-Lang');
        app()->setLocale(in_array($lang, ['es', 'qu'], true) ? $lang : config('app.locale', 'es'));

        return $next($request);
    }
}
