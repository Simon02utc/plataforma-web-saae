<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EstudiantesSaae;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;


class LoginEstudiantesController extends Controller
{
    public function iniciar_sesion_estudiante(Request $request) {
        $data = $request->validate([
            'email' => ['required', 'email', 'ends_with:@cenidet.tecnm.mx'], //'ends_with:@cenidet.tecnm.mx' solo entren con @cenidet.tecnm.mx:
            'password' => ['required','string'],
            'remember' => ['nullable'], // checkbox opcional
        ]);

        // 1) Buscar usuario
        $user = EstudiantesSaae::where('email', $data['email'])->first();

        //Credenciales incorrectas (sin revelar si existe o no)
        if (!$user || !$user->password || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // 2) Validar la cuenta si esat o no activa
        if (!$user->activo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu cuenta no esta activa.',
            ], 403);
        }

        // 3) Loguear con guard estudiante
        $remember = $request->boolean('remember');

        Auth::guard('estudiante')->login($user, $remember);

        //seguridad: regenerar sesion
        $request->session()->regenerate();

        //actualizar ultimo acceso
        $user->forceFill(['ultimo_acceso_at' => now()])->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión iniciada correctamente.',
            'redirect_url' => route('grup_estudiante.name_panel_estudiante'),
        ]);
    }


    public function cerrar_sesion_estudiante(Request $request)
    {
        Auth::guard('estudiante')->logout();

        //invalidar sesion y regenerar token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente.',
            'redirect_url' => route('grup_estudiante.name_login_estudiante'),
        ]);
    }
}
