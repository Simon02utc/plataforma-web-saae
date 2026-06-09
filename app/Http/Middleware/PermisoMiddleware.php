<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class PermisoMiddleware
{
    public function handle(Request $request, Closure $next, ...$permisos)
    {
        $guard = Auth::guard('personal');
        $user  = $guard->user();

        // 1) No autenticado
        if (!$user) {
            return $this->unauthorized($request, 401, 'No autenticado.');
        }

        // 2) Usuario inactivo
        if (!$user->activo) {
            $guard->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->unauthorized($request, 403, 'Usuario inactivo.');
        }

        // 3) Precargar roles y permisos (evita queries repetidas)
        $user->loadMissing('roles.permisos');

        // 4) No autorizado por permisos
        if (!$user->hasAnyPermission($permisos)) {
            if ($request->expectsJson()) {
                return response()->json(['status' => 'error', 'message' => 'No autorizado.'], 403);
            }

            //mandarlo a la pantalla de "sin rol / sin acceso"
            // (Si prefieres 403, cambia esto por: abort(403);)
            return redirect()->route('grup_personal.name_inicio_personal_sin_rol');
        }

        return $next($request);
    }

    private function unauthorized(Request $request, int $code, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], $code);
        }

        return redirect()->route('grup_personal.name_login_personal');
    }
}