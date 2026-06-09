<?php

namespace App\Http\Controllers\Personal;

use App\Http\Controllers\Controller;
use App\Models\PersonalSaae;
//use App\Models\RolesPersonalSaae;
//use App\Models\PermisosSaae;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;

class LoginPersonalController extends Controller
{

    public function iniciar_sesion_personal(Request $request)
    {
        $data = $request->validate([
            'email' => ['required','email'],
            'password' => ['required','string'],
            'remember' => ['nullable'], // checkbox opcional
        ]);

        // 1) Buscar usuario
        $user = PersonalSaae::where('email', $data['email'])->first();

        //Credenciales incorrectas (sin revelar si existe o no)
        if (!$user || !Hash::check($data['password'], $user->password)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Credenciales incorrectas.',
            ], 401);
        }

        // 2) Validar la cuenta si esat o no activa
        if (!$user->activo) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tu cuenta está desactivada.',
            ], 403);
        }

        // 3) Loguear con guard personal
        $remember = $request->boolean('remember');

        Auth::guard('personal')->login($user, $remember);

        // Seguridad: regenerar sesion
        $request->session()->regenerate();

        // 4) Actualizar ultimo acceso
        $user->forceFill(['ultimo_acceso_at' => now()])->save();

        $redirect = $this->redirectPorAcceso($user);

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión iniciada correctamente.',
            'redirect_url' => $redirect,
        ]);
    }


    private function redirectPorAcceso(PersonalSaae $user): string
    {   
        //“Si este usuario todavia no tiene cargadas sus relaciones roles y dentro de esos roles sus permisos, cargalas ahora; pero si ya estan cargadas, no vuelvas a consultar la base de datos”
        $user->loadMissing('roles.permisos');//roles = funcion de roles() que esta en Modelo de PersonalSaae
                                            //permisos = funcion de permisos() que esta en Modelo de RolesPersonalSaae


        if ($user->hasRole('admin') && \Route::has('grup_personal.name_panel_personal')) {
            return route('grup_personal.name_panel_personal');
        }
        
        //se define una lista de reglas donde si el usuario tiene este permiso, se manda a esa ruta
        $map = [
            ['permiso' => 'modulo_importacion.ver', 'route' => 'grup_personal.grup_modulo_importacion.name_importacion_asistencia'],
            ['permiso' => 'panel_personal.ver', 'route' => 'grup_personal.name_panel_personal'],
            ['permiso' => 'estudiantes.ver', 'route' => 'grup_personal.grup_estudiantes.name_gestion_estudiantes'],
            ['permiso' => 'asistencia_estudiantes.ver', 'route' => 'grup_personal.grup_asistencia_estudiantes.name_asistencia_reciente'],
            ['permiso' => 'auditoria_seguridad.ver', 'route' => 'grup_personal.grup_auditoria_seguridad.name_historial_importaciones'],
            ['permiso' => 'justificantes.ver', 'route' => 'grup_personal.grup_justificantes.name_bandeja_justificantes'],
            ['permiso' => 'alertas.ver', 'route' => 'grup_personal.grup_alertas.name_alertas'],
            ['permiso' => 'auditoria_seguridad.ver', 'route' => 'grup_personal.grup_auditoria_seguridad.name_ver_historial_modulo_importaciones'],
            ['permiso' => 'guia_manual_personal.ver', 'route' => 'grup_personal.grup_guia_manual.name_guia_manual_personal'],
            //se agregaran mas modulos en orden de prioridad
        ];

        foreach ($map as $item) {
            if ($user->hasPermission($item['permiso']) && \Route::has($item['route'])) {
                return route($item['route']);
            }
        }

         //esto para el PERSONAL SIN ROL (como una pagina de espera a que se le asigne un rol)
        return route('grup_personal.name_inicio_personal_sin_rol');
    }


    public function cerrar_sesion_personal(Request $request)
    {
        Auth::guard('personal')->logout();

        //invalidar sesion y regenerar token
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'status' => 'success',
            'message' => 'Sesión cerrada correctamente.',
            'redirect_url' => route('grup_personal.name_login_personal'),
        ]);
    }
}