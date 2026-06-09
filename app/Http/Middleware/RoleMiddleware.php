<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
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

        // 3) Cargar roles una sola vez (evita queries repetidas)
        $user->loadMissing('roles');

        // 4) No tiene rol
        if (!$user->hasAnyRole($roles)) {
            return $this->unauthorized($request, 403, 'No autorizado.');
        }

        return $next($request);
    }

    private function unauthorized(Request $request, int $code, string $message)
    {
        if ($request->expectsJson()) {
            return response()->json(['status' => 'error', 'message' => $message], $code);
        }

        // Si no esta autenticado o esta inactivo: al login
        if ($code === 401 || $message === 'Usuario inactivo.') {
            return redirect()->route('grup_personal.name_login_personal');
        }

        abort(403);
    }
}