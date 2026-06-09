<?php

namespace App\Http\Controllers\Personal\PersonalAcceso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersonalSaae;
use App\Services\NotificacionesPersonal\NotificacionPersonalService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class GestionAdministradoresController extends Controller
{
    public function ver_tabla_administradores()
    {
        return view('personal.personal_acceso.gestion_administradores');
    }

    private function queryAdministradores()
    {
        return PersonalSaae::query()
            ->whereHas('roles', function ($q) {
                $q->where('clave', 'admin');
            });
    }

    private function obtenerAdministrador(int $id): PersonalSaae
    {
        return $this->queryAdministradores()
            ->with(['roles:id,clave,nombre,created_at'])
            ->findOrFail($id);
    }


    //=========== TABLA DEL ADMINISTRADOR
    public function listado_administradores(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = $this->queryAdministradores()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            })
            ->with([
                'roles:id,clave,nombre,descripcion',
            ])
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($personal) {
                return [
                    'id' => $personal->id,
                    'nombre_personal' => $personal->nombre,
                    'apellidos_personal' => $personal->apellidos,
                    'correo_electronico' => $personal->email,
                    'telefono' => $personal->telefono,
                    'estado_cuenta_personal' => (bool) $personal->activo,
                    'roles' => $personal->roles->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'clave' => $rol->clave,
                            'nombre' => $rol->nombre,
                        ];
                    })->values(),
                    'ultimo_acceso' => optional($personal->ultimo_acceso_at)->toIso8601String(),
                    'registrado_en' => optional($personal->created_at)->toIso8601String(),
                    'editado_en' => optional($personal->updated_at)->toIso8601String(),
                ];
            })
            ->values();

        return response()->json([
            'data' => $items,
        ]);
    }


    public function ver_roles_administrador(int $id)
    {
        $administrador = $this->obtenerAdministrador($id);

        return response()->json([
            'data' => [
                'id' => $administrador->id,
                'nombre' => $administrador->nombre,
                'apellidos' => $administrador->apellidos,
                'correo' => $administrador->email,
                'total_roles' => $administrador->roles->count(),
                'roles_del_personal' => $administrador->roles
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(function ($rol) {
                        return [
                            'id_rol' => $rol->id,
                            'clave_rol' => $rol->clave,
                            'nombre_rol' => $rol->nombre,
                            'creado_en' => optional($rol->created_at)->toIso8601String(),
                        ];
                    }),
            ]
        ]);
    }

    public function ver_administrador(int $id)
    {
        $administrador = $this->obtenerAdministrador($id);

        return response()->json([
            'data' => [
                'id' => $administrador->id,
                'nombre_personal' => $administrador->nombre,
                'apellidos' => $administrador->apellidos,
                'email' => $administrador->email,
                'telefono' => $administrador->telefono,
                'estado_cuenta_personal' => (bool) $administrador->activo,

                'roles_seleccionados' => $administrador->roles->pluck('id')->values(),
                'roles_disponibles' => $administrador->roles
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'clave' => $rol->clave,
                            'nombre_rol' => $rol->nombre,
                            'creado_en' => optional($rol->created_at)->toIso8601String(),
                        ];
                    }),
            ]
        ]);
    }


    //=========== FUNCION DEL BOTON DE EDITAR UN ADMINISTRADOR
    public function editar_administrador(Request $request, int $id, NotificacionPersonalService $notificacionPersonalService) 
    {
        $administrador = $this->obtenerAdministrador($id);

        $correoAnterior = $administrador->email;

        $datosAnteriores = [
            'nombre' => $administrador->nombre,
            'apellidos' => $administrador->apellidos,
            'email' => $administrador->email,
            'telefono' => $administrador->telefono,
            'activo' => (bool) $administrador->activo,
        ];

        $request->merge([
            'nombre' => Str::of($request->input('nombre', ''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '')
                ->trim()
                ->title()
                ->toString(),

            'apellidos' => Str::of($request->input('apellidos', ''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '')
                ->trim()
                ->title()
                ->toString(),

            'email' => Str::of($request->input('email', ''))
                ->lower()
                ->replaceMatches('/\s+/', '')
                ->trim()
                ->toString(),

            'telefono' => Str::of($request->input('telefono', ''))
                ->replaceMatches('/\D+/', '')
                ->trim()
                ->toString(),
        ]);

        $data = $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                ],
                'apellidos' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                ],
                'email' => [
                    'required',
                    'email',
                    'max:190',
                    Rule::unique('personal_saae', 'email')->ignore($administrador->id),
                ],
                'telefono' => [
                    'required',
                    'regex:/^\d{10}$/',
                    Rule::unique('personal_saae', 'telefono')->ignore($administrador->id),
                ],
                'password' => [
                    'nullable',
                    'string',
                    'min:6',
                    'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$/',
                ],
                'activo' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'nombre.required' => 'El nombre es obligatorio.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',

                'apellidos.required' => 'Los apellidos son obligatorios.',
                'apellidos.regex' => 'Los apellidos solo pueden contener letras y espacios.',

                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'El correo no tiene un formato válido.',
                'email.unique' => 'Ese correo electrónico ya está registrado. Verifícalo.',

                'telefono.required' => 'El número de teléfono es obligatorio.',
                'telefono.regex' => 'El número de teléfono debe tener exactamente 10 dígitos.',
                'telefono.unique' => 'Ese número de teléfono ya está registrado. Verifícalo.',

                'password.confirmed' => 'Las contraseñas no coinciden.',
                'password.regex' => 'La contraseña debe contener al menos 6 caracteres e incluir 1 mayúscula, 1 número y 1 símbolo (!@#$%^&*).',
            ]
        );

        $administrador = DB::transaction(function () use ($administrador, $data) {
            $cargarDatos = [
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'activo' => (bool) $data['activo'],
            ];

            if (!empty($data['password'])) {
                $cargarDatos['password'] = Hash::make($data['password']);
            }

            $administrador->update($cargarDatos);

            return $administrador->fresh(['roles']);
        });

        $cambiosRealizados = [];

        if ($datosAnteriores['nombre'] !== $administrador->nombre) {
            $cambiosRealizados[] = [
                'campo' => 'Nombre',
                'antes' => $datosAnteriores['nombre'],
                'despues' => $administrador->nombre,
            ];
        }

        if ($datosAnteriores['apellidos'] !== $administrador->apellidos) {
            $cambiosRealizados[] = [
                'campo' => 'Apellidos',
                'antes' => $datosAnteriores['apellidos'],
                'despues' => $administrador->apellidos,
            ];
        }

        if ($datosAnteriores['email'] !== $administrador->email) {
            $cambiosRealizados[] = [
                'campo' => 'Correo electrónico',
                'antes' => $datosAnteriores['email'],
                'despues' => $administrador->email,
            ];
        }

        if ($datosAnteriores['telefono'] !== $administrador->telefono) {
            $cambiosRealizados[] = [
                'campo' => 'Número de teléfono',
                'antes' => $datosAnteriores['telefono'],
                'despues' => $administrador->telefono,
            ];
        }

        if ((bool) $datosAnteriores['activo'] !== (bool) $administrador->activo) {
            $cambiosRealizados[] = [
                'campo' => 'Estado de cuenta',
                'antes' => $datosAnteriores['activo'] ? 'Activado' : 'Desactivado',
                'despues' => $administrador->activo ? 'Activado' : 'Desactivado',
            ];
        }

        if (!empty($data['password'])) {
            $cambiosRealizados[] = [
                'campo' => 'Contraseña',
                'antes' => 'No visible',
                'despues' => 'Fue actualizada',
            ];
        }

        try {
            $correoEnviado = $notificacionPersonalService->enviarCorreoActualizacionDatosPersonal(
                $administrador,
                $correoAnterior,
                cambiosRealizados: $cambiosRealizados
            );
        } catch (\Throwable $e) {
            report($e);
            $correoEnviado = false;
        }

        return response()->json([
            'status' => 'success',
            'message' => $correoEnviado
                ? 'Administrador actualizado correctamente.'
                : 'Administrador actualizado correctamente, pero no se pudo enviar el correo.',
            'id' => $administrador->id,
        ], 200);
    }


    //=========== FUNCION DEL BOTON DE ELIMINAR UN ADMINISTRADOR
    public function eliminar_administrador(int $id)
    {
        $administrador = $this->queryAdministradores()
            ->withCount([
                'personalConAsignacion',
            ])
            ->with('roles:id,clave')
            ->findOrFail($id);

        $bloqueos = [];

        if ((int) auth('personal')->id() === (int) $administrador->id) {
            return response()->json([
                'message' => 'No puedes eliminar tu propia cuenta de administrador mientras tienes la sesión iniciada.'
            ], 403);
        }

        $totalAdministradores = $this->queryAdministradores()->count();
        if ($totalAdministradores <= 1) {
            return response()->json([
                'message' => 'No se puede eliminar el último administrador de la plataforma.'
            ], 409);
        }

        if ($administrador->personal_con_asignacion_count > 0) {
            $bloqueos[] = 'asignaciones con estudiantes';
        }

        if (!empty($bloqueos)) {
            return response()->json([
                'message' => 'No se puede eliminar este administrador porque tiene registros relacionados: ' . implode(', ', $bloqueos) . '.'
            ], 409);
        }

        DB::transaction(function () use ($administrador) {
            $administrador->roles()->detach();
            $administrador->delete();
        });

        return response()->json([
            'message' => 'Administrador eliminado correctamente.'
        ]);
    }
}