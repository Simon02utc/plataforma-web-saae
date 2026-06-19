<?php

namespace App\Http\Controllers\Personal\Estudiantes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AreasEspecialidadEstudiantesSaae;
use App\Models\EstatusEscolaresEstudiantesSaae;
use App\Models\EstudiantesSaae;
use App\Models\EstudianteConDatosEscolares;
use App\Models\PersonalSaae;
use App\Models\RolesPersonalSaae;
use App\Models\EstudianteConPersonalSaae;
use App\Services\Estudiantes\NotificacionesEstudiantesService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;

class ListadoEstudiantesSaaeController extends Controller
{
    //=========== PINTADO DE LA TABLA DE ESTUDIANTES   
    public function listado_estudiantes(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $areaId = $request->filled('area_id') ? (int) $request->input('area_id') : null;
        $estatusId = $request->filled('estatus_id') ? (int) $request->input('estatus_id') : null;
        $activo = $request->input('activo', '');
        $perPage = (int) $request->input('per_page', 50);

        if (!in_array($perPage, [20, 50, 100, 200, 300, 400, 500], true)) {
            $perPage = 50;
        }

        $paginado = EstudiantesSaae::query()
            ->with([
                'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
                'estudiantesConDatosEscolares.datoEscolarDeEstatus:id,nombre',
            ])

            // buscador general
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('numero_control', 'like', "%{$buscar}%")
                        ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            })

            // filtro por área
            ->when($areaId, function ($query) use ($areaId) {
                $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($areaId) {
                    $q->where('especialidad_id', $areaId);
                });
            })

            // filtro por estatus
            ->when($estatusId, function ($query) use ($estatusId) {
                $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($estatusId) {
                    $q->where('estatus_escolar_id', $estatusId);
                });
            })

            // filtro por estado de cuenta
            ->when($activo !== '' && $activo !== null, function ($query) use ($activo) {
                $query->where('activo', (bool) $activo);
            })

            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(function ($estudiante) {
                $datos = $estudiante->estudiantesConDatosEscolares;

                return [
                    'id' => $estudiante->id,
                    'numero_control' => $estudiante->numero_control,
                    'nombre_completo_estudiante' => $estudiante->nombre_completo
                        ?: trim(($estudiante->nombre ?? '') . ' ' . ($estudiante->apellidos ?? '')),
                    'correo_electronico' => $estudiante->email,
                    'telefono' => $estudiante->telefono,
                    'datos_escolares_resumen' => $datos ? 'Con datos' : 'Sin datos', //para no mostrar contenido y que se agrande la columna
                    'estado_cuenta_estudiante' => (bool) $estudiante->activo,
                    'ultimo_acceso' => optional($estudiante->ultimo_acceso_at)->toIso8601String(),
                    'registrado_en' => optional($estudiante->created_at)->toIso8601String(),
                    'editado_en' => optional($estudiante->updated_at)->toIso8601String(),
                ];
            });

        return response()->json([
            'data' => $paginado->items(),
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page' => $paginado->lastPage(),
                'per_page' => $paginado->perPage(),
                'total' => $paginado->total(),
                'from' => $paginado->firstItem(),
                'to' => $paginado->lastItem(),
            ]
        ]);
    }


    //=========== FUNCION DEL BOTON DE VER DATOS ESCOLARES DEL ESTUDIANTE
    public function ver_datos_escolares_estudiante($id)
    {
        $estudiante = EstudiantesSaae::with([
            'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
            'estudiantesConDatosEscolares.datoEscolarDeEstatus:id,nombre',
            'estudiantesConDatosEscolares.datoEscolarDeUltimaImportacion:id,archivo_nombre,tipo_importacion,importado_en',
        ])->findOrFail($id);

        $datos = $estudiante->estudiantesConDatosEscolares;

        return response()->json([
            'data' => [
                'id' => $estudiante->id,
                'numero_control' => $estudiante->numero_control,
                'nombre_completo' => $estudiante->nombre_completo
                    ?: trim(($estudiante->nombre ?? '') . ' ' . ($estudiante->apellidos ?? '')),
                'anio_ingreso' => $datos?->anio_ingreso,
                'mes_ingreso' => $this->nombreMes($datos?->mes_ingreso), //hacer que el mes sea texto con nombreMes, que es un metodo privado que esta abajo
                'periodo_ingreso_texto' => $datos?->periodo_ingreso_texto,
                'area_especialidad' => $datos?->datoEscolarDeAreaEspecialidad?->nombre,
                'estatus_escolar' => $datos?->datoEscolarDeEstatus?->nombre,
                'ultima_importacion' => $datos?->datoEscolarDeUltimaImportacion ? [
                    'id' => $datos->datoEscolarDeUltimaImportacion->id,
                    'archivo_nombre' => $datos->datoEscolarDeUltimaImportacion->archivo_nombre,
                    'tipo_importacion' => $datos->datoEscolarDeUltimaImportacion->tipo_importacion,
                    'importado_en' => optional($datos->datoEscolarDeUltimaImportacion->importado_en)->toIso8601String(),
                ] : null,
            ]
        ]);
    }


    //=========== FUNCION DEL BOTON DE VER O REALIZAR ASIGNACIONES DE ESTUDIANTES CON PERSONAL, VER, GUARDAR, DESACTIVAR, REACTIVAR Y ELIMINAR
    public function ver_asignaciones_estudiante(int $id)
    {
        $estudiante = EstudiantesSaae::query()
            ->with([
                'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
                'estudiantesConDatosEscolares.datoEscolarDeEstatus:id,nombre',
                'estudianteConAsignacionPersonal' => function ($q) {
                    $q->with([
                        'asignacionConPersonal:id,nombre,apellidos,email',
                        'asignacionConRol:id,clave,nombre',
                    ])->orderByDesc('activo')->orderByDesc('updated_at');
                }
            ])
            ->findOrFail($id);

        $rolesDisponibles = RolesPersonalSaae::query()
            ->where('clave', '!=', 'admin')
            ->orderBy('created_at')
            ->get(['id', 'clave', 'nombre']);

        $personalDisponible = PersonalSaae::query()
            ->where('activo', true)
            ->sinAdmin()
            ->whereHas('roles', function ($q) {
                $q->where('clave', '!=', 'admin');
            })
            ->orderBy('created_at')
            ->get()
            ->map(function ($p) {
                return [
                    'id' => $p->id,
                    'nombre_completo' => trim(($p->nombre ?? '') . ' ' . ($p->apellidos ?? '')),
                    'email' => $p->email,
                ];
            })
            ->values();

        $asignacionesActivas = $estudiante->estudianteConAsignacionPersonal
            ->where('activo', true)
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'personal_id' => $a->personal_id,
                    'nombre_personal' => trim(($a->asignacionConPersonal->nombre ?? '') . ' ' . ($a->asignacionConPersonal->apellidos ?? '')),
                    'email_personal' => $a->asignacionConPersonal->email ?? null,
                    'role_id' => $a->role_id,
                    'clave_rol' => $a->asignacionConRol->clave ?? null,
                    'nombre_rol' => $a->asignacionConRol->nombre ?? null,
                    'activo' => true,
                ];
            })
            ->values();

        $asignacionesInactivas = $estudiante->estudianteConAsignacionPersonal
            ->where('activo', false)
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'personal_id' => $a->personal_id,
                    'nombre_personal' => trim(($a->asignacionConPersonal->nombre ?? '') . ' ' . ($a->asignacionConPersonal->apellidos ?? '')),
                    'email_personal' => $a->asignacionConPersonal->email ?? null,
                    'role_id' => $a->role_id,
                    'clave_rol' => $a->asignacionConRol->clave ?? null,
                    'nombre_rol' => $a->asignacionConRol->nombre ?? null,
                    'activo' => false,
                ];
            })
            ->values();

        return response()->json([
            'data' => [
                'id' => $estudiante->id,
                'numero_control' => $estudiante->numero_control,
                'nombre_completo' => $estudiante->nombre_completo,
                'area_especialidad' => $estudiante->estudiantesConDatosEscolares?->datoEscolarDeAreaEspecialidad?->nombre,
                'estatus_escolar' => $estudiante->estudiantesConDatosEscolares?->datoEscolarDeEstatus?->nombre,
                'asignaciones_activas' => $asignacionesActivas,
                'asignaciones_inactivas' => $asignacionesInactivas,
                'roles_disponibles' => $rolesDisponibles->values(),
                'personal_disponible' => $personalDisponible,
            ]
        ]);
    }


    public function guardar_asignacion_estudiante(Request $request, int $id)
    {
        $data = $request->validate([
            'role_id' => ['required', 'integer', 'exists:roles_personal_saae,id'],
            'personal_id' => ['required', 'integer', 'exists:personal_saae,id'],
        ]);

        $estudiante = EstudiantesSaae::findOrFail($id);

        $personalValido = PersonalSaae::query()
            ->where('id', $data['personal_id'])
            ->where('activo', true)
            ->whereHas('roles', function ($q) use ($data) {
                $q->where('roles_personal_saae.id', $data['role_id']);
            })
            ->exists();

        if (!$personalValido) {
            return response()->json([
                'message' => 'El personal seleccionado no tiene asignado ese rol.'
            ], 422);
        }

        $asignacionExacta = EstudianteConPersonalSaae::query()
            ->where('estudiante_id', $estudiante->id)
            ->where('personal_id', $data['personal_id'])
            ->where('role_id', $data['role_id'])
            ->first();

        if ($asignacionExacta && $asignacionExacta->activo) {
            return response()->json([
                'message' => 'Esa asignación ya existe y ya está activa.'
            ], 422);
        }

        DB::transaction(function () use ($data, $estudiante, $asignacionExacta) {
            // Solo una activa por estudiante + rol
            EstudianteConPersonalSaae::query()
                ->where('estudiante_id', $estudiante->id)
                ->where('role_id', $data['role_id'])
                ->where('activo', true)
                ->update([
                    'activo' => false,
                    'updated_at' => now(),
                ]);

            if ($asignacionExacta) {
                $asignacionExacta->update([
                    'activo' => true,
                ]);
            } else {
                EstudianteConPersonalSaae::create([
                    'estudiante_id' => $estudiante->id,
                    'personal_id' => $data['personal_id'],
                    'role_id' => $data['role_id'],
                    'activo' => true,
                ]);
            }
        });

        return response()->json([
            'message' => 'Asignación académica guardada correctamente.'
        ]);
    }


    public function desactivar_asignacion_estudiante(int $id)
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
            'message' => 'Asignación académica desactivada correctamente.'
        ]);
    }


    public function reactivar_asignacion_estudiante(int $id)
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
                'message' => 'No se puede reactivar porque el personal ya no tiene ese rol en el sistema o está inactivo.'
            ], 422);
        }

        $otraActiva = EstudianteConPersonalSaae::query()
            ->where('estudiante_id', $asignacion->estudiante_id)
            ->where('role_id', $asignacion->role_id)
            ->where('activo', true)
            ->exists();

        if ($otraActiva) {
            return response()->json([
                'message' => 'No se puede reactivar porque ya existe otra asignación activa para ese rol.'
            ], 422);
        }

        $asignacion->update([
            'activo' => true,
        ]);

        return response()->json([
            'message' => 'Asignación académica reactivada correctamente.'
        ]);
    }


    public function eliminar_asignacion_estudiante(int $id)
    {
        $asignacion = EstudianteConPersonalSaae::findOrFail($id);

        $asignacion->delete();

        return response()->json([
            'message' => 'Asignación académica eliminada definitivamente.'
        ]);
    }


    //=========== FUNCION DEL BOTON DE EDITAR ESTUDIANTE
    public function ver_estudiante(int $id)
    {
        $estudiante = EstudiantesSaae::with([
            'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
            'estudiantesConDatosEscolares.datoEscolarDeEstatus:id,nombre',
        ])->findOrFail($id);

        $datos = $estudiante->estudiantesConDatosEscolares;

        $areas = AreasEspecialidadEstudiantesSaae::query()
            ->where('activo', true)
            ->orderBy('created_at')
            ->get(['id', 'nombre'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'nombre_especialidad' => $item->nombre,
            ])
            ->values();

        $estatus = EstatusEscolaresEstudiantesSaae::query()
            ->where('activo', true)
            ->orderBy('created_at')
            ->get(['id', 'nombre'])
            ->map(fn ($item) => [
                'id' => $item->id,
                'nombre_estatus' => $item->nombre,
            ])
            ->values();

        return response()->json([
            'data' => [
                'id' => $estudiante->id,
                'numero_control' => $estudiante->numero_control,
                'nombre_completo' => $estudiante->nombre_completo,
                'nombre_estudiante' => $estudiante->nombre,
                'apellidos_estudiante' => $estudiante->apellidos,
                'email' => $estudiante->email,
                'telefono' => $estudiante->telefono,
                'estado_cuenta_estudiante' => (bool) $estudiante->activo,
                'anio_ingreso' => $datos?->anio_ingreso,
                'mes_ingreso' => $datos?->mes_ingreso,
                'especialidad_seleccionada_id' => $datos?->especialidad_id,
                'estatus_seleccionado_id' => $datos?->estatus_escolar_id,
                'areas_especialidad_disponibles' => $areas,
                'estatus_escolares_disponibles' => $estatus,
            ]
        ]);
    }


    public function editar_estudiante(Request $request, int $id, NotificacionesEstudiantesService $notificacionesEstudiantesService) {
        $estudiante = EstudiantesSaae::with([
            'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
            'estudiantesConDatosEscolares.datoEscolarDeEstatus:id,nombre',
        ])->findOrFail($id);

        $datosEscolaresActuales = $estudiante->estudiantesConDatosEscolares;

        $correoAnterior = $estudiante->email;

        $datosAnteriores = [
            'numero_control' => $estudiante->numero_control,
            'nombre' => $estudiante->nombre,
            'apellidos' => $estudiante->apellidos,
            'email' => $estudiante->email,
            'telefono' => $estudiante->telefono,
            'activo' => (bool) $estudiante->activo,
            'anio_ingreso' => $datosEscolaresActuales?->anio_ingreso,
            'mes_ingreso' => $datosEscolaresActuales?->mes_ingreso,
            'especialidad_id' => $datosEscolaresActuales?->especialidad_id,
            'estatus_id' => $datosEscolaresActuales?->estatus_escolar_id,
            'especialidad_nombre' => $datosEscolaresActuales?->datoEscolarDeAreaEspecialidad?->nombre,
            'estatus_nombre' => $datosEscolaresActuales?->datoEscolarDeEstatus?->nombre,
        ];

        $request->merge([
            'numero_control' => Str::of($request->input('numero_control', ''))
                ->upper()
                ->replaceMatches('/\s+/', '')
                ->replaceMatches('/[^A-Z0-9]/', '')
                ->trim()
                ->toString(),

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

            'anio_ingreso' => $request->filled('anio_ingreso') ? (int) $request->input('anio_ingreso') : null,
            'mes_ingreso' => $request->filled('mes_ingreso') ? (int) $request->input('mes_ingreso') : null,
            'activo' => $request->boolean('activo'),
        ]);

        $data = $request->validate(
            [
                'numero_control' => [
                    'required',
                    'string',
                    'size:8',
                    'regex:/^[A-Z][0-9]{2}[A-Z]{2}[0-9]{3}$/',
                    Rule::unique('estudiantes_saae', 'numero_control')->ignore($estudiante->id),
                ],
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
                    Rule::unique('estudiantes_saae', 'email')->ignore($estudiante->id),
                ],
                'telefono' => [
                    'required',
                    'regex:/^\d{10}$/',
                    Rule::unique('estudiantes_saae', 'telefono')->ignore($estudiante->id),
                ],
                'password' => [
                    'nullable',
                    'string',
                    'min:6',
                    'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$/',
                ],
                'anio_ingreso' => [
                    'required',
                    'integer',
                    'min:2000',
                    'max:' . now()->year,
                ],
                'mes_ingreso' => [
                    'required',
                    'integer',
                    'between:1,12',
                ],
                'area_id' => [
                    'required',
                    'exists:areas_especialidad_estudiantes_saae,id',
                ],
                'estatus_id' => [
                    'required',
                    'exists:estatus_escolares_estudiantes_saae,id',
                ],
                'activo' => [
                    'required',
                    'boolean',
                ],
            ],
            [
                'numero_control.unique' => 'Ese número de control ya está registrado.',
                'email.unique' => 'Ese correo electrónico ya está registrado.',
                'telefono.unique' => 'Ese número de teléfono ya está registrado.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
                'password.regex' => 'La contraseña debe contener al menos 6 caracteres e incluir 1 mayúscula, 1 número y 1 símbolo.',
            ]
        );

        $estudiante = DB::transaction(function () use ($estudiante, $data) {
            $datosActualizar = [
                'numero_control' => $data['numero_control'],
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'nombre_completo' => trim($data['nombre'] . ' ' . $data['apellidos']),
                'email' => $data['email'],
                'telefono' => $data['telefono'],
                'activo' => (bool) $data['activo'],
            ];

            if (!empty($data['password'])) {
                $datosActualizar['password'] = Hash::make($data['password']);
            }

            $estudiante->update($datosActualizar);

            $estudiante->estudiantesConDatosEscolares()->updateOrCreate(
                ['estudiante_id' => $estudiante->id],
                [
                    'anio_ingreso' => $data['anio_ingreso'],
                    'mes_ingreso' => $data['mes_ingreso'],
                    'especialidad_id' => $data['area_id'],
                    'estatus_escolar_id' => $data['estatus_id'],
                ]
            );

            return $estudiante->fresh([
                'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
                'estudiantesConDatosEscolares.datoEscolarDeEstatus:id,nombre',
            ]);
        });

        $datosEscolaresNuevos = $estudiante->estudiantesConDatosEscolares;

        $cambiosRealizados = [];

        if ($datosAnteriores['numero_control'] !== $estudiante->numero_control) {
            $cambiosRealizados[] = [
                'campo' => 'Número de control',
                'antes' => $datosAnteriores['numero_control'],
                'despues' => $estudiante->numero_control,
            ];
        }

        if ($datosAnteriores['nombre'] !== $estudiante->nombre) {
            $cambiosRealizados[] = [
                'campo' => 'Nombre',
                'antes' => $datosAnteriores['nombre'],
                'despues' => $estudiante->nombre,
            ];
        }

        if ($datosAnteriores['apellidos'] !== $estudiante->apellidos) {
            $cambiosRealizados[] = [
                'campo' => 'Apellidos',
                'antes' => $datosAnteriores['apellidos'],
                'despues' => $estudiante->apellidos,
            ];
        }

        if ($datosAnteriores['email'] !== $estudiante->email) {
            $cambiosRealizados[] = [
                'campo' => 'Correo electrónico',
                'antes' => $datosAnteriores['email'],
                'despues' => $estudiante->email,
            ];
        }

        if ($datosAnteriores['telefono'] !== $estudiante->telefono) {
            $cambiosRealizados[] = [
                'campo' => 'Número de teléfono',
                'antes' => $datosAnteriores['telefono'],
                'despues' => $estudiante->telefono,
            ];
        }

        if ((bool) $datosAnteriores['activo'] !== (bool) $estudiante->activo) {
            $cambiosRealizados[] = [
                'campo' => 'Estado de cuenta',
                'antes' => $datosAnteriores['activo'] ? 'Activado' : 'Desactivado',
                'despues' => $estudiante->activo ? 'Activado' : 'Desactivado',
            ];
        }

        if ((int) $datosAnteriores['anio_ingreso'] !== (int) $datosEscolaresNuevos?->anio_ingreso) {
            $cambiosRealizados[] = [
                'campo' => 'Año de ingreso',
                'antes' => $datosAnteriores['anio_ingreso'] ?: 'No definido',
                'despues' => $datosEscolaresNuevos?->anio_ingreso ?: 'No definido',
            ];
        }

        if ((int) $datosAnteriores['mes_ingreso'] !== (int) $datosEscolaresNuevos?->mes_ingreso) {
            $cambiosRealizados[] = [
                'campo' => 'Mes de ingreso',
                'antes' => $this->nombreMes($datosAnteriores['mes_ingreso']) ?: 'No definido',
                'despues' => $this->nombreMes($datosEscolaresNuevos?->mes_ingreso) ?: 'No definido',
            ];
        }

        if ((int) $datosAnteriores['especialidad_id'] !== (int) $datosEscolaresNuevos?->especialidad_id) {
            $cambiosRealizados[] = [
                'campo' => 'Área de especialidad',
                'antes' => $datosAnteriores['especialidad_nombre'] ?: 'No definida',
                'despues' => $datosEscolaresNuevos?->datoEscolarDeAreaEspecialidad?->nombre ?: 'No definida',
            ];
        }

        if ((int) $datosAnteriores['estatus_id'] !== (int) $datosEscolaresNuevos?->estatus_escolar_id) {
            $cambiosRealizados[] = [
                'campo' => 'Estatus escolar',
                'antes' => $datosAnteriores['estatus_nombre'] ?: 'No definido',
                'despues' => $datosEscolaresNuevos?->datoEscolarDeEstatus?->nombre ?: 'No definido',
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
            $correoEnviado = $notificacionesEstudiantesService->enviarCorreoActualizacionDatosEstudiante(
                $estudiante,
                $correoAnterior,
                $cambiosRealizados
            );
        } catch (\Throwable $e) {
            report($e);
            $correoEnviado = false;
        }

        return response()->json([
            'status' => 'success',
            'message' => $correoEnviado
                ? 'Estudiante actualizado correctamente.'
                : 'Estudiante actualizado correctamente, pero no se pudo enviar el correo.',
            'id' => $estudiante->id,
        ], 200);
    }


    //=========== FUNCION DEL BOTON DE ELIMINAR ESTUDIANTE
    public function eliminar_estudiante($id)
    {
        $estudiante = EstudiantesSaae::withCount([
            'periodoEstudiantes',
            'inscripcionesReloj',
            'marcaciones',
            'asistenciasDiarias',
            'estudianteConAsignacionPersonal',
        ])->with('estudiantesConDatosEscolares')->findOrFail($id);

        $bloqueos = [];

        if ($estudiante->periodo_estudiantes_count > 0) {
            $bloqueos[] = 'periodos/inscripciones académicas';
        }

        if ($estudiante->inscripciones_reloj_count > 0) {
            $bloqueos[] = 'inscripciones de reloj';
        }

        if ($estudiante->marcaciones_count > 0) {
            $bloqueos[] = 'marcaciones de asistencia';
        }

        if ($estudiante->asistencias_diarias_count > 0) {
            $bloqueos[] = 'asistencia diaria';
        }

        if ($estudiante->estudiante_con_asignacion_personal_count > 0) {
            $bloqueos[] = 'asignaciones con personal';
        }

        if (!empty($bloqueos)) {
            return response()->json([
                'message' => 'No se puede eliminar al estudiante porque tiene registros relacionados: ' . implode(', ', $bloqueos) . '. Se recomienda desactivar la cuenta.'
            ], 409);
        }

        DB::transaction(function () use ($estudiante) {
            $estudiante->estudiantesConDatosEscolares()->delete();
            $estudiante->delete();
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Estudiante eliminado correctamente.',
            'id' => $id,
        ], 200);
    }


    public function exportar_estudiantes_excel(Request $request): StreamedResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));
        $areaId = $request->filled('area_id') ? (int) $request->input('area_id') : null;
        $estatusId = $request->filled('estatus_id') ? (int) $request->input('estatus_id') : null;
        $activo = $request->input('activo', '');

        $estudiantes = EstudiantesSaae::query()
            ->with([
                'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
                'estudiantesConDatosEscolares.datoEscolarDeEstatus:id,nombre',
            ])

            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('numero_control', 'like', "%{$buscar}%")
                        ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                        ->orWhere('nombre', 'like', "%{$buscar}%")
                        ->orWhere('apellidos', 'like', "%{$buscar}%")
                        ->orWhere('email', 'like', "%{$buscar}%");
                });
            })

            ->when($areaId, function ($query) use ($areaId) {
                $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($areaId) {
                    $q->where('especialidad_id', $areaId);
                });
            })

            ->when($estatusId, function ($query) use ($estatusId) {
                $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($estatusId) {
                    $q->where('estatus_escolar_id', $estatusId);
                });
            })

            ->when($activo !== '' && $activo !== null, function ($query) use ($activo) {
                $query->where('activo', (bool) $activo);
            })

            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estudiantes');

        $colorPrincipal = '1B396A';
        $colorSecundarioSuave = 'EAF0F8';
        $colorBorde = 'C9D4E5';
        $colorTextoOscuro = '1F2937';
        $colorVerdeSuave = 'E8F5E9';
        $colorRojoSuave = 'FDECEC';
        $colorInfoSuave = 'EEF4FF';

        // ===================== RESUMEN SUPERIOR =====================
        $sheet->setCellValue('A1', 'Sección');
        $sheet->setCellValue('B1', 'Listado de estudiantes');

        $sheet->setCellValue('A2', 'Reporte');
        $sheet->setCellValue('B2', 'Exportación de estudiantes');

        $sheet->setCellValue('A3', 'Búsqueda aplicada');
        $sheet->setCellValue('B3', $buscar !== '' ? $buscar : 'Sin filtro');

        $sheet->setCellValue('A4', 'Filtro de área');
        $sheet->setCellValue('B4', $areaId ?: 'Todos');

        $sheet->setCellValue('A5', 'Filtro de estatus');
        $sheet->setCellValue('B5', $estatusId ?: 'Todos');

        $sheet->setCellValue('A6', 'Filtro de estado');
        $sheet->setCellValue('B6', $activo === '' ? 'Todos' : ((bool) $activo ? 'Activados' : 'Desactivados'));

        $sheet->setCellValue('A7', 'Total exportado');
        $sheet->setCellValue('B7', $estudiantes->count());

        $sheet->setCellValue('A8', 'Fecha de exportación');
        $sheet->setCellValue('B8', now('America/Mexico_City')->format('d/m/Y H:i:s'));

        // ===================== ENCABEZADOS =====================
        $filaEncabezado = 10;

        $sheet->setCellValue("A{$filaEncabezado}", 'ID');
        $sheet->setCellValue("B{$filaEncabezado}", 'Número de control');
        $sheet->setCellValue("C{$filaEncabezado}", 'Nombre completo');
        $sheet->setCellValue("D{$filaEncabezado}", 'Correo electrónico');
        $sheet->setCellValue("E{$filaEncabezado}", 'Teléfono');
        $sheet->setCellValue("F{$filaEncabezado}", 'Área de especialidad');
        $sheet->setCellValue("G{$filaEncabezado}", 'Estatus escolar');
        $sheet->setCellValue("H{$filaEncabezado}", 'Datos escolares');
        $sheet->setCellValue("I{$filaEncabezado}", 'Estado cuenta');
        $sheet->setCellValue("J{$filaEncabezado}", 'Último acceso');
        $sheet->setCellValue("K{$filaEncabezado}", 'Registrado en');
        $sheet->setCellValue("L{$filaEncabezado}", 'Editado en');

        $fila = 11;

        foreach ($estudiantes as $estudiante) {
            $datos = $estudiante->estudiantesConDatosEscolares;

            $nombreCompleto = $estudiante->nombre_completo
                ?: trim(($estudiante->nombre ?? '') . ' ' . ($estudiante->apellidos ?? ''));

            $area = $datos?->datoEscolarDeAreaEspecialidad?->nombre ?? '—';
            $estatus = $datos?->datoEscolarDeEstatus?->nombre ?? '—';
            $datosResumen = $datos ? 'Con datos' : 'Sin datos';
            $estadoCuenta = $estudiante->activo ? 'Activado' : 'Desactivado';

            $sheet->setCellValue("A{$fila}", $estudiante->id);
            $sheet->setCellValue("B{$fila}", $estudiante->numero_control ?? '—');
            $sheet->setCellValue("C{$fila}", $nombreCompleto ?: '—');
            $sheet->setCellValue("D{$fila}", $estudiante->email ?? '—');
            $sheet->setCellValue("E{$fila}", $estudiante->telefono ?? '—');
            $sheet->setCellValue("F{$fila}", $area);
            $sheet->setCellValue("G{$fila}", $estatus);
            $sheet->setCellValue("H{$fila}", $datosResumen);
            $sheet->setCellValue("I{$fila}", $estadoCuenta);
            $sheet->setCellValue(
                "J{$fila}",
                $estudiante->ultimo_acceso_at?->timezone('America/Mexico_City')?->format('d/m/Y H:i:s') ?? '—'
            );
            $sheet->setCellValue(
                "K{$fila}",
                $estudiante->created_at?->timezone('America/Mexico_City')?->format('d/m/Y H:i:s') ?? '—'
            );
            $sheet->setCellValue(
                "L{$fila}",
                $estudiante->updated_at?->timezone('America/Mexico_City')?->format('d/m/Y H:i:s') ?? '—'
            );

            $fila++;
        }

        $ultimaFila = max($fila - 1, $filaEncabezado);

        // ===================== ESTILOS =====================
        $sheet->getStyle('A1:B8')->getFont()->setBold(true);
        $sheet->getStyle('A1:B8')->getFont()->getColor()->setARGB($colorPrincipal);
        $sheet->getStyle('A1:B8')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorSecundarioSuave);

        $sheet->getStyle('A1:B8')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        $sheet->getStyle("A{$filaEncabezado}:L{$filaEncabezado}")->getFont()->setBold(true);
        $sheet->getStyle("A{$filaEncabezado}:L{$filaEncabezado}")->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle("A{$filaEncabezado}:L{$filaEncabezado}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorPrincipal);

        $sheet->getStyle("A{$filaEncabezado}:L{$filaEncabezado}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension($filaEncabezado)->setRowHeight(24);

        $sheet->getStyle("A{$filaEncabezado}:L{$ultimaFila}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        $sheet->getStyle("A11:L{$ultimaFila}")->getFont()->getColor()->setARGB($colorTextoOscuro);

        $sheet->getStyle("A11:A{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("H11:L{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Resaltar estado de datos escolares
        for ($f = 11; $f <= $ultimaFila; $f++) {
            $datosEscolares = $sheet->getCell("H{$f}")->getValue();
            $estadoCuenta = $sheet->getCell("I{$f}")->getValue();

            $sheet->getStyle("H{$f}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($datosEscolares === 'Con datos' ? $colorInfoSuave : $colorRojoSuave);

            $sheet->getStyle("I{$f}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($estadoCuenta === 'Activado' ? $colorVerdeSuave : $colorRojoSuave);
        }

        $sheet->setAutoFilter("A{$filaEncabezado}:L{$ultimaFila}");
        $sheet->freezePane('A11');

        // ===================== ANCHOS =====================
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(18);
        $sheet->getColumnDimension('C')->setWidth(30);
        $sheet->getColumnDimension('D')->setWidth(32);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(24);
        $sheet->getColumnDimension('G')->setWidth(22);
        $sheet->getColumnDimension('H')->setWidth(16);
        $sheet->getColumnDimension('I')->setWidth(16);
        $sheet->getColumnDimension('J')->setWidth(22);
        $sheet->getColumnDimension('K')->setWidth(22);
        $sheet->getColumnDimension('L')->setWidth(22);

        $sheet->getStyle("B1:B8")->getAlignment()->setWrapText(true);
        $sheet->getStyle("B11:L{$ultimaFila}")->getAlignment()->setWrapText(true);

        $nombreArchivo = 'estudiantes_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }


    private function nombreMes($mes): string
    {
        $meses = [
            1 => 'Enero',
            2 => 'Febrero',
            3 => 'Marzo',
            4 => 'Abril',
            5 => 'Mayo',
            6 => 'Junio',
            7 => 'Julio',
            8 => 'Agosto',
            9 => 'Septiembre',
            10 => 'Octubre',
            11 => 'Noviembre',
            12 => 'Diciembre',
        ];

        return $meses[(int) $mes] ?? '—'; //se puede poner texto en — pero mejor asi
    }

}