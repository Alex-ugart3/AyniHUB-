<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Restringe una ruta a ciertos roles. Uso: ->middleware('role:productor,gestor')
 * Capa extra de seguridad sobre las Policies (el rol se verifica antes de entrar al controlador).
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['message' => __('Debes iniciar sesión.')], 401);
        }

        if (! in_array($user->role, $roles, true)) {
            return response()->json(['message' => __('Tu rol no tiene permiso para esta acción.')], 403);
        }

        return $next($request);
    }
}
