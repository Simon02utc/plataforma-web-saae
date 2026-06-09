<?php

namespace App\Http\Controllers\Personal\PersonalAcceso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersonalSaae;
use App\Models\RolesPersonalSaae;
use App\Models\PermisosSaae;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use App\Services\NotificacionesPersonal\NotificacionPersonalService; //Utilizacion del correo electronico para notificar

class RegistrarPersonalSaaeController extends Controller
{

    public function registrar_personal(Request $request, NotificacionPersonalService $notificacionPersonalService)
    {
        //Verificar los datos
        $request->merge([
            'nombre' => Str::of($request->input('nombre',''))
                ->replaceMatches('/\s+/', ' ') //con espacios
                ->replaceMatches('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '')
                ->trim()
                ->title()
                ->toString(),
            
            'apellidos' => Str::of($request->input('apellidos',''))
                ->replaceMatches('/\s+/', ' ') //con espacios
                ->replaceMatches('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '')
                ->trim()
                ->title()
                ->toString(),

            'email' => Str::of($request->input('email',''))
                ->lower()
                ->replaceMatches('/\s+/', '')// sin espacios
                ->trim()
                ->toString(),

            'telefono' => Str::of($request->input('telefono', ''))
                ->replaceMatches('/\D+/', '')// deja solo numeros
                ->trim()
                ->toString(),
        ]);

        //obtener el ID del rol con la clave "admin" (si existe)
        $adminRoleId = RolesPersonalSaae::where('clave', 'admin')->value('id');

        $data = $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u'
                ],
                'apellidos' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u'
                ],
                'email' => [
                    'required',
                    'email',
                    'max:190',
                    'unique:personal_saae,email'
                ],
                'telefono' => [
                    'required',
                    'regex:/^\d{10}$/',
                    'unique:personal_saae,telefono'
                ],

                //Laravel tiene una regla de validacion de contrañas, entonces el <input> de contraseña  su name= tiene que decir "password"
                //Y el <input> de repetir la contraseña su name= tiene que decir "password_confirmation"
                //Por ello la regla 'confirmed' le dice a Laravel que: Tienes un campo: password ---> Entonces debe existir otro campo llamado: password_confirmation  ---> Y deben ser identicos
                'password' => [
                    'required',
                    'string',
                    'min:6',
                    'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$/'//Minino 6 caracteres (1 letra mayuscula, 1 número y 1 simbolo)
                ],
                'activo' => [
                    'required',
                    'boolean'
                ],

                // roles[]
                'roles' => [
                    'nullable',
                    'array'
                ],
                'roles.*' => [
                    'integer',
                    'exists:roles_personal_saae,id'
                ],
            ],
            [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.regex'    => 'El nombre solo puede contener letras y espacios.',

                'apellidos.required' => 'Los apellidos son obligatorios.',
                'apellidos.regex'    => 'Los apellidos solo puede contener letras y espacios.',

                'email.required'   => 'El correo es obligatorio',
                'email.email'   => 'El correo no tiene un formato válido.',
                'email.unique'  => 'Ese correo electrónico ya está registrado. Verifícalo.',

                'telefono.required' => 'El número de teléfono es obligatorio.',
                'telefono.regex' => 'El número de teléfono debe tener exactamente 10 dígitos.',
                'telefono.unique' => 'Ese número de teléfono ya está registrado. Verifícalo.',

                'password.confirmed' => 'Las contraseñas no coinciden.',
                'password.regex'     => 'La contraseña debe contener al menos 6 caracteres e incluir 1 mayúscula, 1 número y 1 símbolo (!@#$%^&*).',

                'roles.required' => 'Selecciona al menos un rol.',
                'roles.min'      => 'Selecciona al menos un rol.',
            ]
        );

        $passwordPlano = $data['password']; //para enviar la contra

        $personal = DB::transaction(function () use ($data, $adminRoleId) {

            //Solo 1 personal con el Rol de clave "admin"
            if ($adminRoleId && !empty($data['roles']) && in_array((int)$adminRoleId, array_map('intval', $data['roles']), true)) {

                //bloquea el registro del rol admin
                RolesPersonalSaae::where('id', $adminRoleId)->lockForUpdate()->first();

                $yaHayAdmin = DB::table('personal_con_rol_saae')
                    ->where('role_id', $adminRoleId)
                    ->exists();

                if ($yaHayAdmin) {
                    throw ValidationException::withMessages([
                        'roles' => 'El rol Administrador (admin) ya está asignado. Solo puede existir un administrador.',
                    ]);
                }
            }

            $personal = PersonalSaae::create([
                'nombre' => $data['nombre'] ?? null,
                'apellidos' => $data['apellidos'] ?? null,
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'password' => Hash::make($data['password']),
                'activo' => (bool)($data['activo'] ?? true),
            ]);

            //se mandan los roles, asigna (1 o varios)
            if (!empty($data['roles'])) {
                $personal->roles()->sync($data['roles']);
            }

            return $personal;
        });

        $correoEnviado = $notificacionPersonalService->enviarCorreoRegistro($personal, $passwordPlano);

        return response()->json([
            'status' => 'success',
            'message' => $correoEnviado
                ? 'Personal registrado correctamente.'
                : 'Personal registrado correctamente, pero no se pudo enviar el correo.',
            'id' => $personal->id,
        ], 201);

    }
}