<?php

namespace App\Http\Controllers\Personal\PersonalAcceso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersonalSaae;
use App\Models\RolesPersonalSaae;
use App\Models\EstudiantesSaae;
use App\Models\EstudianteConPersonalSaae;
use App\Services\NotificacionesPersonal\NotificacionPersonalService; //Utilizacion del correo electronico para notificar
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ListadoPersonalSaaeController extends Controller
{
    public function ver_tabla_personal()
    {
        $roles = RolesPersonalSaae::query()
            ->where('clave', '!=', 'admin')
            ->orderBy('created_at')
            ->get();//colocar los roles, excepto el admin y ponerlos por su fecha de creacion
        
        //identificar el rol con la clave "admin" y tomar su id
        $adminRoleId = RolesPersonalSaae::where('clave','admin')->value('id');

        $adminOcupado = $adminRoleId
            ? DB::table('personal_con_rol_saae')->where('role_id', $adminRoleId)->exists()
            : false;
        return view('personal.personal_acceso.listado_personal', compact('roles', 'adminRoleId', 'adminOcupado'));
    }


    //===========TABLA DEL PERSONAL
    public function listado_personal(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));
        
        //no mostrar el personal con el rol admin
        $items = PersonalSaae::query()
            ->sinAdmin()

            //buscador
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%")
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
                    'estado_cuenta_personal' => $personal->activo,
                    'roles' => $personal->roles->map(function ($rol) {
                        return [
                            'id' => $rol->id,
                            'clave' => $rol->clave,
                            'nombre' => $rol->nombre,
                        ];
                    })->values(),
                    'ultimo_acceso' => optional($personal->ultimo_acceso_at)->toIso8601String(),
                    'registrado_en' => optional($personal->created_at)->toIso8601String(),
                    'editado_en' => optional($personal->updated_at)->toIso8601String()
                ];
            });
        
        return response()->json(['data' => $items]);
    }


    public function ver_roles_personal(int $id)
    {

        $personal = PersonalSaae::with(['roles:id,clave,nombre,created_at'])->findOrFail($id);

        //no mostrar el personal con el rol admin    
        if ($personal->esAdmin()) {
            return response()->json([
                'message' => 'El personal administrador no se puede consultar desde esta sección.'
            ], 403);
        }

        return response()->json([
            'data' => [
                'id' => $personal->id,
                'nombre' => $personal->nombre,
                'apellidos' => $personal->apellidos,
                'correo' => $personal->email,
                'total_roles' => $personal->roles->count(),
                'roles_del_personal' => $personal->roles
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


    public function ver_personal(int $id)
    {
        $personal = PersonalSaae::with(['roles:id,clave,nombre,created_at'])->findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$personal) {
            return response()->json([
                'message' => 'Este personal ya no existe o fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        //no mostrar el personal con el rol admin
        if ($personal->esAdmin()) {
            return response()->json([
                'message' => 'El personal administrador no se puede consultar desde esta sección.'
            ], 403);
        }

        $rolesDisponibles = RolesPersonalSaae::query()
            ->where('clave', '!=', 'admin') //excluir al administrador
            ->orderByDesc('created_at')
            ->get(['id', 'clave', 'nombre', 'created_at'])
            ->map(function ($rol) {
                return [
                    'id' => $rol->id,
                    'clave' => $rol->clave,
                    'nombre_rol' => $rol->nombre,
                    'creado_en' => optional($rol->created_at)->toIso8601String(),
                ];
            });


        return response()->json([
            'data' => [
                'id' => $personal->id,
                'nombre_personal' => $personal->nombre,
                'apellidos' => $personal->apellidos,
                'email' => $personal->email,
                'telefono' => $personal->telefono,
                'estado_cuenta_personal' => (bool) $personal->activo,
                'roles_seleccionados' => $personal->roles->pluck('id')->values(),
                'roles_disponibles' => $rolesDisponibles,
            ]
        ]);
    }


    //=========== FUNCION DEL BOTON DE ESTUDIANTES ASIGNADOS AL PERSONAL, VER, DESACTIVAR, REACTIVAR Y ELIMINAR    
    public function ver_estudiantes_asignados_personal(int $id)
    {
        $personal = PersonalSaae::query()
            ->with([
                'personalConAsignacion' => function ($q) {
                    $q->with([
                        'asignacionConEstudiante:id,numero_control,nombre_completo,nombre,apellidos',
                        'asignacionConRol:id,clave,nombre',
                    ])->orderByDesc('activo')->orderByDesc('updated_at');
                }
            ])
            ->findOrFail($id);

        $asignacionesActivas = $personal->personalConAsignacion
            ->where('activo', true)
            ->map(function ($a) {
                $estudiante = $a->asignacionConEstudiante;

                $nombreArmado = trim(
                    (($estudiante?->nombre ?? '') . ' ' . ($estudiante?->apellidos ?? ''))
                );

                return [
                    'id' => $a->id,
                    'estudiante_id' => $a->estudiante_id,
                    'numero_control' => $estudiante?->numero_control,
                    'nombre_estudiante' => $nombreArmado !== '' ? $nombreArmado : ($estudiante?->nombre_completo ?? '—'),
                    'role_id' => $a->role_id,
                    'clave_rol' => $a->asignacionConRol?->clave,
                    'nombre_rol' => $a->asignacionConRol?->nombre,
                    'activo' => true,
                ];
            })
            ->values();

        $asignacionesInactivas = $personal->personalConAsignacion
            ->where('activo', false)
            ->map(function ($a) {
                $estudiante = $a->asignacionConEstudiante;

                $nombreArmado = trim(
                    (($estudiante?->nombre ?? '') . ' ' . ($estudiante?->apellidos ?? ''))
                );

                return [
                    'id' => $a->id,
                    'estudiante_id' => $a->estudiante_id,
                    'numero_control' => $estudiante?->numero_control,
                    'nombre_estudiante' => $nombreArmado !== '' ? $nombreArmado : ($estudiante?->nombre_completo ?? '—'),
                    'role_id' => $a->role_id,
                    'clave_rol' => $a->asignacionConRol?->clave,
                    'nombre_rol' => $a->asignacionConRol?->nombre,
                    'activo' => false,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'id' => $personal->id,
                'nombre_completo' => trim(($personal->nombre ?? '') . ' ' . ($personal->apellidos ?? '')),
                'email' => $personal->email,
                'asignaciones_activas' => $asignacionesActivas,
                'asignaciones_inactivas' => $asignacionesInactivas,
            ]
        ]);
    }


    public function desactivar_asignacion_personal(int $id)
    {
        $asignacion = EstudianteConPersonalSaae::findOrFail($id);

        if (!$asignacion->activo) {
            return response()->json([
                'message' => 'La asignación ya está desactivada.'
            ], 422);
        }

        $asignacion->update([
            'activo' => false,
        ]);

        return response()->json([
            'message' => 'Asignación desactivada correctamente.'
        ]);
    }


    public function reactivar_asignacion_personal(int $id)
    {
        $asignacion = EstudianteConPersonalSaae::findOrFail($id);

        if ($asignacion->activo) {
            return response()->json([
                'message' => 'La asignación ya está activa.'
            ], 422);
        }

        $personalValido = PersonalSaae::query()
            ->where('id', $asignacion->personal_id)
            ->where('activo', true)
            ->whereHas('roles', function ($q) use ($asignacion) {
                $q->where('roles_personal_saae.id', $asignacion->role_id);
            })
            ->exists();

        if (!$personalValido) {
            return response()->json([
                'message' => 'No se puede reactivar porque el personal ya no tiene ese rol o está inactivo.'
            ], 422);
        }

        $otraActiva = EstudianteConPersonalSaae::query()
            ->where('estudiante_id', $asignacion->estudiante_id)
            ->where('role_id', $asignacion->role_id)
            ->where('activo', true)
            ->exists();

        if ($otraActiva) {
            return response()->json([
                'message' => 'No se puede reactivar porque ya existe otra asignación activa para ese estudiante y rol.'
            ], 422);
        }

        $asignacion->update([
            'activo' => true,
        ]);

        return response()->json([
            'message' => 'Asignación reactivada correctamente.'
        ]);
    }


    public function eliminar_asignacion_personal(int $id)
    {
        $asignacion = EstudianteConPersonalSaae::findOrFail($id);

        $asignacion->delete();

        return response()->json([
            'message' => 'Asignación eliminada definitivamente.'
        ]);
    }


    //=========== FUNCION DEL BOTON DE EDITAR PERSONAL
    public function editar_personal(Request $request, int $id, NotificacionPersonalService $notificacionPersonalService)
    {
        $personal = PersonalSaae::with('roles:id,nombre,clave')->findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$personal) {
            return response()->json([
                'message' => 'No se puede guardar porque este personal ya fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }


        //no mostrar el personal con el rol admin
        if ($personal->esAdmin()) {
            return response()->json([
                'message' => 'El personal administrador no se puede editar desde esta sección.'
            ], 403);
        }


        //Guardado del correo anterior antes de actualizar, para notificar de los cambios al anterior y nuevo, si es que se actualizo el correo, si no, lo manda al que tenenia
        $correoAnterior = $personal->email;

        //de mismo modo guardar estos datos antes de que se actualisen
        $datosAnteriores = [
            'nombre' => $personal->nombre,
            'apellidos' => $personal->apellidos,
            'email' => $personal->email,
            'telefono' => $personal->telefono,
            'activo' => (bool) $personal->activo,
            'roles_ids' => $personal->roles->pluck('id')->sort()->values()->all(),
            'roles_nombres' => $personal->roles->pluck('nombre')->sort()->values()->all(),
        ];


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
                    Rule::unique('personal_saae', 'email')->ignore($personal->id),
                ],
                'telefono' => [
                    'required',
                    'regex:/^\d{10}$/',
                    Rule::unique('personal_saae', 'telefono')->ignore($personal->id),
                ],

                //Laravel tiene una regla de validacion de contrañas, entonces el <input> de contraseña  su name= tiene que decir "password"
                //Y el <input> de repetir la contraseña su name= tiene que decir "password_confirmation"
                //Por ello la regla 'confirmed' le dice a Laravel que: Tienes un campo: password ---> Entonces debe existir otro campo llamado: password_confirmation  ---> Y deben ser identicos
                'password' => [
                    'nullable',
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
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',

                'apellidos.required' => 'Los apellidos son obligatorios.',
                'apellidos.regex' => 'Los apellidos solo puede contener letras y espacios.',

                'email.required' => 'El correo es obligatorio',
                'email.email' => 'El correo no tiene un formato válido.',
                'email.unique' => 'Ese correo electrónico ya está registrado. Verifícalo.',

                'telefono.required' => 'El número de teléfono es obligatorio.',
                'telefono.regex' => 'El número de teléfono debe tener exactamente 10 dígitos.',
                'telefono.unique' => 'Ese número de teléfono ya está registrado. Verifícalo.',

                'password.confirmed' => 'Las contraseñas no coinciden.',
                'password.regex' => 'La contraseña debe contener al menos 6 caracteres e incluir 1 mayúscula, 1 número y 1 símbolo (!@#$%^&*).',
            ]
        );

        //No permitir que se asigne el rol admin a otro personal
        if (!empty($data['roles']) && $adminRoleId && in_array((int) $adminRoleId, array_map('intval', $data['roles']), true)) {
            throw ValidationException::withMessages([
                'roles' => 'El rol Administrador no se puede asignar desde esta sección.',
            ]);
        }


        $personal = DB::transaction(function () use ($personal, $data) {

            $cargarDatos = [
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'activo' => (bool) $data['activo'],
            ];

            //se altualiza la contraseña si vienen datos
            if (!empty($data['password'])) {
                $cargarDatos['password'] = Hash::make($data['password']);
            }

            $personal->update($cargarDatos);
            $personal->roles()->sync($data['roles'] ?? []);

            return $personal->fresh(['roles']);
        });


        //=========LISTADO DE CAMBIOS=========
        $cambiosRealizados = [];

        if ($datosAnteriores['nombre'] !== $personal->nombre) {
            $cambiosRealizados[] = [
                'campo' => 'Nombre',
                'antes' => $datosAnteriores['nombre'],
                'despues' => $personal->nombre,
            ];
        }

        if ($datosAnteriores['apellidos'] !== $personal->apellidos) {
            $cambiosRealizados[] = [
                'campo' => 'Apellidos',
                'antes' => $datosAnteriores['apellidos'],
                'despues' => $personal->apellidos,
            ];
        }

        if ($datosAnteriores['email'] !== $personal->email) {
            $cambiosRealizados[] = [
                'campo' => 'Correo electrónico',
                'antes' => $datosAnteriores['email'],
                'despues' => $personal->email,
            ];
        }

        if ($datosAnteriores['telefono'] !== $personal->telefono) {
            $cambiosRealizados[] = [
                'campo' => 'Numero de teléfono',
                'antes' => $datosAnteriores['telefono'],
                'despues' => $personal->telefono,
            ];
        }

        if ((bool) $datosAnteriores['activo'] !== (bool) $personal->activo) {
            $cambiosRealizados[] = [
                'campo' => 'Estado de cuenta',
                'antes' => $datosAnteriores['activo'] ? 'Activado' : 'Desactivado',
                'despues' => $personal->activo ? 'Activado' : 'Desactivado',
            ];
        }

        $rolesActualesIds = $personal->roles->pluck('id')->sort()->values()->all();
        $rolesActualesNombres = $personal->roles->pluck('nombre')->sort()->values()->all();

        if ($datosAnteriores['roles_ids'] !== $rolesActualesIds) {
            $cambiosRealizados[] = [
                'campo' => 'Roles asignados',
                'antes' => !empty($datosAnteriores['roles_nombres']) ? implode(', ', $datosAnteriores['roles_nombres']) : 'Sin roles',
                'despues' => !empty($rolesActualesNombres) ? implode(', ', $rolesActualesNombres) : 'Sin roles',
            ];
        }

        if (!empty($data['password'])) {
            $cambiosRealizados[] = [
                'campo' => 'Contraseña',
                'antes' => 'No visible',
                'despues' => 'Fue actualizada',
            ];
        }
        //====================================


        //con el try{} para evitar una excepcion, que terminarai en datos guardados correctamente, pero respuesta 500 al frontend
        try {
            $correoEnviado = $notificacionPersonalService->enviarCorreoActualizacionDatosPersonal(
                $personal,
                $correoAnterior, //para mandarlo al correo anterior y al nuevo, si es que se actualizo el correo, si no, lo manda al que tenenia
                cambiosRealizados: $cambiosRealizados //mandar lo que se actualizo del personal
            );
        } catch (\Throwable $e) {
            report($e);
            $correoEnviado = false;
        }


        return response()->json([
            'status' => 'success',
            'message' => $correoEnviado
                ? 'Personal actualizado correctamente.'
                : 'Personal actualizado correctamente, pero no se pudo enviar el correo.',
            'id' => $personal->id,
        ], 200);
    }


    //=========== FUNCION DEL BOTON DE ELIMINAR UN PERSONAL
    public function eliminar_personal(int $id)
    {
        $personal = PersonalSaae::withCount([
            'personalConAsignacion',
        ])->with('roles:id,clave')->findOrFail($id);

        $bloqueos = [];

        if ($personal->esAdmin()) {
            return response()->json([
                'message' => 'No se puede eliminar el personal administrador.'
            ], 403);
        }

        if ($personal->personal_con_asignacion_count > 0) {
            $bloqueos[] = 'asignaciones con estudiantes';
        }

        if (!empty($bloqueos)) {
            return response()->json([
                'message' => 'No se puede eliminar este personal porque tiene registros relacionados: ' . implode(', ', $bloqueos) . '.'
            ], 409);
        }

        DB::transaction(function () use ($personal) {
            $personal->roles()->detach(); //“Quita todas las relaciones de ese registro con la tabla pivote de roles.”
            $personal->delete();
        });

        return response()->json([
            'message' => 'Personal eliminado correctamente.'
        ]);
    }



    public function exportar_personal_excel(Request $request): StreamedResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $personalItems = PersonalSaae::query()
            ->sinAdmin()
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
                'roles:id,clave,nombre',
            ])
            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Personal');

        $colorPrincipal = '1B396A';
        $colorSecundarioSuave = 'EAF0F8';
        $colorBorde = 'C9D4E5';
        $colorTextoOscuro = '1F2937';
        $colorVerdeSuave = 'E8F5E9';
        $colorRojoSuave = 'FDECEC';

        // ===================== RESUMEN SUPERIOR =====================
        $sheet->setCellValue('A1', 'Sección');
        $sheet->setCellValue('B1', 'Listado del personal');

        $sheet->setCellValue('A2', 'Reporte');
        $sheet->setCellValue('B2', 'Exportación de personal');

        $sheet->setCellValue('A3', 'Búsqueda aplicada');
        $sheet->setCellValue('B3', $buscar !== '' ? $buscar : 'Sin filtro');

        $sheet->setCellValue('A4', 'Total exportado');
        $sheet->setCellValue('B4', $personalItems->count());

        $sheet->setCellValue('A5', 'Fecha de exportación');
        $sheet->setCellValue('B5', now('America/Mexico_City')->format('d/m/Y H:i:s'));

        $sheet->setCellValue('A6', 'Nota');
        $sheet->setCellValue('B6', 'No se incluye al personal administrativo.');

        // ===================== ENCABEZADOS =====================
        $filaEncabezado = 8;

        $sheet->setCellValue("A{$filaEncabezado}", 'ID');
        $sheet->setCellValue("B{$filaEncabezado}", 'Nombre');
        $sheet->setCellValue("C{$filaEncabezado}", 'Apellidos');
        $sheet->setCellValue("D{$filaEncabezado}", 'Correo');
        $sheet->setCellValue("E{$filaEncabezado}", 'Teléfono');
        $sheet->setCellValue("F{$filaEncabezado}", 'Total roles');
        $sheet->setCellValue("G{$filaEncabezado}", 'Roles asignados');
        $sheet->setCellValue("H{$filaEncabezado}", 'Estado');
        $sheet->setCellValue("I{$filaEncabezado}", 'Último acceso');
        $sheet->setCellValue("J{$filaEncabezado}", 'Registrado en');
        $sheet->setCellValue("K{$filaEncabezado}", 'Editado en');

        $fila = 9;

        foreach ($personalItems as $personal) {
            $rolesTexto = $personal->roles->isNotEmpty()
                ? $personal->roles->map(function ($rol) {
                    return "{$rol->nombre} ({$rol->clave})";
                })->implode(', ')
                : 'Sin roles asignados';

            $estadoTexto = $personal->activo ? 'Activado' : 'Desactivado';

            $sheet->setCellValue("A{$fila}", $personal->id);
            $sheet->setCellValue("B{$fila}", $personal->nombre ?? '—');
            $sheet->setCellValue("C{$fila}", $personal->apellidos ?? '—');
            $sheet->setCellValue("D{$fila}", $personal->email ?? '—');
            $sheet->setCellValue("E{$fila}", $personal->telefono ?? '—');
            $sheet->setCellValue("F{$fila}", $personal->roles->count());
            $sheet->setCellValue("G{$fila}", $rolesTexto);
            $sheet->setCellValue("H{$fila}", $estadoTexto);
            $sheet->setCellValue(
                "I{$fila}",
                $personal->ultimo_acceso_at?->timezone('America/Mexico_City')->format('d/m/Y H:i:s') ?? '—'
            );
            $sheet->setCellValue(
                "J{$fila}",
                $personal->created_at?->timezone('America/Mexico_City')->format('d/m/Y H:i:s') ?? '—'
            );
            $sheet->setCellValue(
                "K{$fila}",
                $personal->updated_at?->timezone('America/Mexico_City')->format('d/m/Y H:i:s') ?? '—'
            );

            $fila++;
        }

        $ultimaFila = max($fila - 1, $filaEncabezado);

        // ===================== ESTILOS =====================
        $sheet->getStyle('A1:B6')->getFont()->setBold(true);
        $sheet->getStyle('A1:B6')->getFont()->getColor()->setARGB($colorPrincipal);
        $sheet->getStyle('A1:B6')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorSecundarioSuave);

        $sheet->getStyle('A1:B6')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        $sheet->getStyle("A{$filaEncabezado}:K{$filaEncabezado}")->getFont()->setBold(true);
        $sheet->getStyle("A{$filaEncabezado}:K{$filaEncabezado}")->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle("A{$filaEncabezado}:K{$filaEncabezado}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorPrincipal);

        $sheet->getStyle("A{$filaEncabezado}:K{$filaEncabezado}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension($filaEncabezado)->setRowHeight(24);

        $sheet->getStyle("A{$filaEncabezado}:K{$ultimaFila}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        $sheet->getStyle("A9:K{$ultimaFila}")->getFont()->getColor()->setARGB($colorTextoOscuro);

        $sheet->getStyle("A9:A{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("F9:F{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("H9:K{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Resaltar estado
        for ($f = 9; $f <= $ultimaFila; $f++) {
            $estado = $sheet->getCell("H{$f}")->getValue();

            $sheet->getStyle("H{$f}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($estado === 'Activado' ? $colorVerdeSuave : $colorRojoSuave);
        }

        $sheet->setAutoFilter("A{$filaEncabezado}:K{$ultimaFila}");
        $sheet->freezePane('A9');

        // ===================== ANCHOS =====================
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(24);
        $sheet->getColumnDimension('D')->setWidth(32);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(14);
        $sheet->getColumnDimension('G')->setWidth(45);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(22);
        $sheet->getColumnDimension('J')->setWidth(22);
        $sheet->getColumnDimension('K')->setWidth(22);

        $sheet->getStyle("B9:K{$ultimaFila}")->getAlignment()->setWrapText(true);
        $sheet->getStyle("B1:B6")->getAlignment()->setWrapText(true);

        $nombreArchivo = 'personal_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }

}