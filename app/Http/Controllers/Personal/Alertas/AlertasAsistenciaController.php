<?php

namespace App\Http\Controllers\Personal\Alertas;

use App\Http\Controllers\Controller;
use App\Models\AlertaAsistenciaEstudiante;
use App\Models\Periodo;
use App\Models\EstudianteConPersonalSaae;
use App\Models\EstudiantesSaae;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;

class AlertasAsistenciaController extends Controller
{
    //================== VISTAS ==================
    public function alertas(Request $request)
    {
        $periodos = Periodo::query()
            ->orderByDesc('fecha_fin')
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin']);

        return view('personal.alertas.alertas', compact('periodos'));
    }

    public function historial_alertas(Request $request)
    {
        $periodos = Periodo::query()
            ->orderByDesc('fecha_fin')
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin']);

        return view('personal.alertas.historial_alertas', compact('periodos'));
    }


    //=========== METODO PRIVADOS DE APOYO
    private function resolverNombreEstudiante($estudiante): string
    {
        if (!$estudiante) {
            return '—';
        }

        $nombreCompleto = trim((string) ($estudiante->nombre_completo ?? ''));
        if ($nombreCompleto !== '') {
            return $nombreCompleto;
        }

        $nombreSeparado = trim(
            (string) (($estudiante->nombre ?? '') . ' ' . ($estudiante->apellidos ?? ''))
        );

        return $nombreSeparado !== '' ? $nombreSeparado : '—';
    }

    private function resolverTipoAlertaTexto(string $tipo): string
    {
        return match ($tipo) {
            'FALTA_ACUMULADA' => 'FALTA ACUMULADA',
            'SUSPENSION_BECA_ESCOLAR' => 'SUSPENCIÓN DE BECA ESCOLAR',
            default => $tipo,
        };
    }




    //================== TABLA ALERTAS ==================
    public function tabla_alertas(Request $request): JsonResponse
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $buscar = trim((string) $request->input('buscar', ''));
        $periodoId = $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null;
        $tipo = trim((string) $request->input('tipo', ''));
        $estado = trim((string) $request->input('estado', 'PENDIENTE'));
        $perPage = (int) $request->input('per_page', 20);
        $page = max((int) $request->input('page', 1), 1);

        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $estadosPermitidos = ['PENDIENTE', 'ATENDIDA', 'CERRADA'];
        if (!in_array($estado, $estadosPermitidos, true)) {
            $estado = 'PENDIENTE';
        }


        if ($esAdmin) {

            $asignaciones = EstudiantesSaae::query()
                ->select('id', 'numero_control', 'nombre_completo', 'nombre', 'apellidos')
                ->when($buscar !== '', function ($query) use ($buscar) {
                    $query->where(function ($qq) use ($buscar) {

                        if (is_numeric($buscar)) {
                            $qq->orWhere('id', (int) $buscar);
                        }

                        $qq->orWhere('numero_control', 'like', "%{$buscar}%")
                            ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                            ->orWhere('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellidos', 'like', "%{$buscar}%");
                    });
                })
                ->orderByDesc('created_at')
                ->get();

            $estudiantesIds = $asignaciones
                ->pluck('id')
                ->filter()
                ->unique()
                ->values();

        } else {

            $asignaciones = EstudianteConPersonalSaae::query()
                ->with([
                    'asignacionConEstudiante:id,numero_control,nombre_completo,nombre,apellidos',
                ])
                ->where('personal_id', $personalId)
                ->where('activo', true)
                ->when($buscar !== '', function ($query) use ($buscar) {
                    $query->whereHas('asignacionConEstudiante', function ($q) use ($buscar) {
                        $q->where(function ($qq) use ($buscar) {

                            if (is_numeric($buscar)) {
                                $qq->orWhere('id', (int) $buscar);
                            }

                            $qq->orWhere('numero_control', 'like', "%{$buscar}%")
                                ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                                ->orWhere('nombre', 'like', "%{$buscar}%")
                                ->orWhere('apellidos', 'like', "%{$buscar}%");
                        });
                    });
                })
                ->orderByDesc('created_at')
                ->get();

            $estudiantesIds = $asignaciones
                ->pluck('estudiante_id')
                ->filter()
                ->unique()
                ->values();
        }


        if ($estudiantesIds->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0,
                    'periodo_id_usado' => $periodoId,
                    'estado_usado' => $estado,
                ]
            ]);
        }

        $alertas = AlertaAsistenciaEstudiante::query()
            ->with([
                'estudiante:id,numero_control,nombre_completo,nombre,apellidos',
                'periodo:id,nombre',
            ])
            ->whereIn('estudiante_id', $estudiantesIds)
            ->where('estado', $estado)
            ->when($periodoId, function ($query) use ($periodoId) {
                $query->where('periodo_id', $periodoId);
            })
            ->when($tipo !== '', function ($query) use ($tipo) {
                $query->where('tipo_alerta', $tipo);
            })
            ->orderByDesc('fecha_disparo')
            ->get();

        $items = $alertas->map(function ($alerta) {
            return [
                'id' => $alerta->id,
                'estudiante_id' => $alerta->estudiante_id,
                'numero_control' => $alerta->estudiante?->numero_control ?? '—',
                'nombre_estudiante' => $this->resolverNombreEstudiante($alerta->estudiante),
                'tipo_alerta' => $this->resolverTipoAlertaTexto($alerta->tipo_alerta),
                'regla_codigo' => $alerta->regla_codigo,
                'valor_detectado' => (int) $alerta->valor_detectado,
                'umbral_configurado' => $alerta->umbral_configurado !== null
                    ? (int) $alerta->umbral_configurado
                    : null,
                'fecha_referencia' => optional($alerta->fecha_referencia)?->format('Y-m-d'),
                'fecha_disparo' => optional($alerta->fecha_disparo)?->timezone('America/Mexico_City')->format('Y-m-d H:i:s'),
                'estado_alerta' => $alerta->estado,

                'correo_estado' => $alerta->correo_estado,
                'correo_enviado_at' => $alerta->correo_enviado_at
                    ? $alerta->correo_enviado_at->timezone('America/Mexico_City')->format('Y-m-d H:i:s')
                    : null,

                'correo_fallo_at' => $alerta->correo_fallo_at
                    ? $alerta->correo_fallo_at->timezone('America/Mexico_City')->format('Y-m-d H:i:s')
                    : null,
                'correo_error' => $alerta->correo_error,

                'periodo_id' => $alerta->periodo_id,
                'periodo_nombre' => $alerta->periodo?->nombre ?? '—',
            ];
        })->values();

        $total = $items->count();
        $offset = ($page - 1) * $perPage;
        $itemsPagina = $items->slice($offset, $perPage)->values();

        $paginado = new LengthAwarePaginator(
            $itemsPagina,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'data' => $itemsPagina,
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page' => $paginado->lastPage(),
                'per_page' => $paginado->perPage(),
                'total' => $paginado->total(),
                'from' => $paginado->firstItem() ?? 0,
                'to' => $paginado->lastItem() ?? 0,
                'periodo_id_usado' => $periodoId,
                'estado_usado' => $estado,
            ]
        ]);
    }


    public function resumen_alertas(Request $request): JsonResponse
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $periodoId = $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null;
        $estado = trim((string) $request->input('estado', 'PENDIENTE'));

        $estadosPermitidos = ['PENDIENTE', 'ATENDIDA', 'CERRADA'];

        if (!in_array($estado, $estadosPermitidos, true)) {
            $estado = 'PENDIENTE';
        }


        if ($esAdmin) {

            $estudiantesIds = EstudiantesSaae::query()
                ->pluck('id')
                ->filter()
                ->unique()
                ->values();

        } else {

            $estudiantesIds = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('activo', true)
                ->pluck('estudiante_id')
                ->filter()
                ->unique()
                ->values();
        }


        if ($estudiantesIds->isEmpty()) {
            return response()->json([
                'data' => [
                    'pendientes' => 0,
                    'normales' => 0,
                    'especiales' => 0,
                    'atendidas' => 0,
                ],
                'meta' => [
                    'periodo_id_usado' => $periodoId,
                    'estado_usado' => $estado,
                ],
            ]);
        }

        $baseQuery = AlertaAsistenciaEstudiante::query()
            ->whereIn('estudiante_id', $estudiantesIds)
            ->when($periodoId, function ($query) use ($periodoId) {
                $query->where('periodo_id', $periodoId);
            });

        $pendientes = (clone $baseQuery)
            ->where('estado', 'PENDIENTE')
            ->count();

        $normales = (clone $baseQuery)
            ->where('estado', $estado)
            ->where('tipo_alerta', 'FALTA_ACUMULADA')
            ->count();

        $especiales = (clone $baseQuery)
            ->where('estado', $estado)
            ->where('tipo_alerta', 'SUSPENSION_BECA_ESCOLAR')
            ->count();

        $atendidas = (clone $baseQuery)
            ->whereIn('estado', ['ATENDIDA', 'CERRADA'])
            ->count();

        return response()->json([
            'data' => [
                'pendientes' => $pendientes,
                'normales' => $normales,
                'especiales' => $especiales,
                'atendidas' => $atendidas,
            ],
            'meta' => [
                'periodo_id_usado' => $periodoId,
                'estado_usado' => $estado,
            ],
        ]);
    }


    public function ver_alerta(int $id): JsonResponse
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $alerta = AlertaAsistenciaEstudiante::query()
            ->with([
                'estudiante:id,numero_control,nombre_completo,nombre,apellidos,email,telefono',
                'periodo:id,nombre,fecha_inicio,fecha_fin',
                'asistenciaDiaria:id,fecha,estatus,justificada,fuente,primera_entrada,ultima_salida,conteo_marcaciones',
                'atendidaPor:id,nombre,apellidos,email',
            ])
            ->when(!$esAdmin, function ($query) use ($personalId) {
                $query->whereHas('estudiante.estudianteConAsignacionPersonal', function ($q) use ($personalId) {
                    $q->where('personal_id', $personalId)
                        ->where('activo', true);
                });
            })
            ->findOrFail($id);

        return response()->json([
            'alerta' => [
                'id' => $alerta->id,
                'tipo_alerta' => $this->resolverTipoAlertaTexto($alerta->tipo_alerta),
                'regla_codigo' => $alerta->regla_codigo,
                'valor_detectado' => (int) $alerta->valor_detectado,
                'umbral_configurado' => $alerta->umbral_configurado !== null
                    ? (int) $alerta->umbral_configurado
                    : null,
                'fecha_referencia' => optional($alerta->fecha_referencia)?->format('Y-m-d'),
                'fecha_disparo' => optional($alerta->fecha_disparo)?->timezone('America/Mexico_City')->format('Y-m-d H:i:s'),
                'estado_alerta' => $alerta->estado,

                'correo_estado' => $alerta->correo_estado,
                'correo_enviado_at' => $alerta->correo_enviado_at
                    ? $alerta->correo_enviado_at->timezone('America/Mexico_City')->format('Y-m-d H:i:s')
                    : null,

                'correo_fallo_at' => $alerta->correo_fallo_at
                    ? $alerta->correo_fallo_at->timezone('America/Mexico_City')->format('Y-m-d H:i:s')
                    : null,
                'correo_error' => $alerta->correo_error,

                'observaciones' => $alerta->observaciones,

                'estudiante' => [
                    'id' => $alerta->estudiante?->id,
                    'numero_control' => $alerta->estudiante?->numero_control,
                    'nombre' => $this->resolverNombreEstudiante($alerta->estudiante),
                    'email' => $alerta->estudiante?->email,
                    'telefono' => $alerta->estudiante?->telefono,
                ],
    
                'periodo' => $alerta->periodo ? [
                    'id' => $alerta->periodo->id,
                    'nombre' => $alerta->periodo->nombre,
                    'fecha_inicio' => optional($alerta->periodo->fecha_inicio)?->format('Y-m-d'),
                    'fecha_fin' => optional($alerta->periodo->fecha_fin)?->format('Y-m-d'),
                ] : null,

                'asistencia' => $alerta->asistenciaDiaria ? [
                    'fecha' => optional($alerta->asistenciaDiaria->fecha)?->format('Y-m-d'),
                    'estatus' => $alerta->asistenciaDiaria->estatus,
                    'fuente' => $alerta->asistenciaDiaria->fuente,
                    'primera_entrada' => $alerta->asistenciaDiaria->getRawOriginal('primera_entrada'),
                    'ultima_salida' => $alerta->asistenciaDiaria->getRawOriginal('ultima_salida'),
                    'conteo_marcaciones' => $alerta->asistenciaDiaria->conteo_marcaciones,
                ] : null,

                'atendida_por' => $alerta->atendidaPor ? [
                    'id' => $alerta->atendidaPor->id,
                    'nombre' => trim(($alerta->atendidaPor->nombre ?? '') . ' ' . ($alerta->atendidaPor->apellidos ?? '')),
                    'email' => $alerta->atendidaPor->email,
                ] : null,
            ]
        ]);
    }


    public function atender_alerta(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $alerta = AlertaAsistenciaEstudiante::query()
            ->where('estado', 'PENDIENTE')
            ->when(!$esAdmin, function ($query) use ($personalId) {
                $query->whereHas('estudiante.estudianteConAsignacionPersonal', function ($q) use ($personalId) {
                    $q->where('personal_id', $personalId)
                        ->where('activo', true);
                });
            })
            ->findOrFail($id);

        $alerta->update([
            'estado' => 'ATENDIDA',
            'atendida_por' => $personalId,
            'atendida_en' => now(),
            'observaciones' => $request->input('observaciones'),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'La alerta fue marcada como atendida correctamente.',
        ]);
    }

    public function cerrar_alerta(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'observaciones' => ['nullable', 'string', 'max:2000'],
        ]);

        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $alerta = AlertaAsistenciaEstudiante::query()
            ->whereIn('estado', ['PENDIENTE', 'ATENDIDA'])
            ->when(!$esAdmin, function ($query) use ($personalId) {
                $query->whereHas('estudiante.estudianteConAsignacionPersonal', function ($q) use ($personalId) {
                    $q->where('personal_id', $personalId)
                        ->where('activo', true);
                });
            })
            ->findOrFail($id);

        $alerta->update([
            'estado' => 'CERRADA',
            'atendida_por' => $personalId,
            'atendida_en' => now(),
            'observaciones' => $request->input('observaciones'),
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'La alerta fue cerrada correctamente.',
        ]);
    }




    //================== TABLA DE HISTORIAL DE ASISTENCIA ==================
    public function tabla_historial_alertas(Request $request): JsonResponse
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $buscar = trim((string) $request->input('buscar', ''));
        $periodoId = $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null;
        $tipo = trim((string) $request->input('tipo', ''));
        $estado = trim((string) $request->input('estado', ''));
        $perPage = (int) $request->input('per_page', 20);
        $page = max((int) $request->input('page', 1), 1);

        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }


        if ($esAdmin) {

            $asignaciones = EstudiantesSaae::query()
                ->select('id', 'numero_control', 'nombre_completo', 'nombre', 'apellidos')
                ->when($buscar !== '', function ($query) use ($buscar) {

                    $query->where(function ($qq) use ($buscar) {

                        if (is_numeric($buscar)) {
                            $qq->orWhere('id', (int) $buscar);
                        }

                        $qq->orWhere('numero_control', 'like', "%{$buscar}%")
                            ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                            ->orWhere('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellidos', 'like', "%{$buscar}%");
                    });

                })
                ->orderByDesc('created_at')
                ->get();

            $estudiantesIds = $asignaciones
                ->pluck('id')
                ->filter()
                ->unique()
                ->values();

        } else {

            $asignaciones = EstudianteConPersonalSaae::query()
                ->with([
                    'asignacionConEstudiante:id,numero_control,nombre_completo,nombre,apellidos',
                ])
                ->where('personal_id', $personalId)
                ->where('activo', true)
                ->when($buscar !== '', function ($query) use ($buscar) {

                    $query->whereHas('asignacionConEstudiante', function ($q) use ($buscar) {

                        $q->where(function ($qq) use ($buscar) {

                            if (is_numeric($buscar)) {
                                $qq->orWhere('id', (int) $buscar);
                            }

                            $qq->orWhere('numero_control', 'like', "%{$buscar}%")
                                ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                                ->orWhere('nombre', 'like', "%{$buscar}%")
                                ->orWhere('apellidos', 'like', "%{$buscar}%");
                        });

                    });

                })
                ->orderByDesc('created_at')
                ->get();

            $estudiantesIds = $asignaciones
                ->pluck('estudiante_id')
                ->filter()
                ->unique()
                ->values();
        }


        if ($estudiantesIds->isEmpty()) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0,
                    'periodo_id_usado' => $periodoId,
                    'estado_usado' => $estado,
                ]
            ]);
        }

        $alertas = AlertaAsistenciaEstudiante::query()
            ->with([
                'estudiante:id,numero_control,nombre_completo,nombre,apellidos',
                'periodo:id,nombre',
                'atendidaPor:id,nombre,apellidos,email',
            ])
            ->whereIn('estudiante_id', $estudiantesIds)
            ->whereIn('estado', ['ATENDIDA', 'CERRADA'])
            ->when($periodoId, function ($query) use ($periodoId) {
                $query->where('periodo_id', $periodoId);
            })
            ->when($tipo !== '', function ($query) use ($tipo) {
                $query->where('tipo_alerta', $tipo);
            })
            ->when($estado !== '', function ($query) use ($estado) {
                $query->where('estado', $estado);
            })
            ->orderByDesc('atendida_en')
            ->orderByDesc('fecha_disparo')
            ->get();

        $items = $alertas->map(function ($alerta) {
            return [
                'id' => $alerta->id,
                'estudiante_id' => $alerta->estudiante_id,
                'numero_control' => $alerta->estudiante?->numero_control ?? '—',
                'nombre_estudiante' => $this->resolverNombreEstudiante($alerta->estudiante),
                'tipo_alerta' => $this->resolverTipoAlertaTexto($alerta->tipo_alerta),
                'valor_detectado' => (int) $alerta->valor_detectado,
                'fecha_referencia' => optional($alerta->fecha_referencia)?->format('Y-m-d'),
                'fecha_disparo' => optional($alerta->fecha_disparo)?->timezone('America/Mexico_City')->format('Y-m-d H:i:s'),
                'estado_alerta' => $alerta->estado,
                'atendida_por_nombre' => $alerta->atendidaPor
                    ? trim(($alerta->atendidaPor->nombre ?? '') . ' ' . ($alerta->atendidaPor->apellidos ?? ''))
                    : '—',
                'atendida_en' => $alerta->getRawOriginal('atendida_en'),
                'periodo_id' => $alerta->periodo_id,
                'periodo_nombre' => $alerta->periodo?->nombre ?? '—',
            ];
        })->values();

        $total = $items->count();
        $offset = ($page - 1) * $perPage;
        $itemsPagina = $items->slice($offset, $perPage)->values();

        $paginado = new LengthAwarePaginator(
            $itemsPagina,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return response()->json([
            'data' => $itemsPagina,
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page' => $paginado->lastPage(),
                'per_page' => $paginado->perPage(),
                'total' => $paginado->total(),
                'from' => $paginado->firstItem() ?? 0,
                'to' => $paginado->lastItem() ?? 0,
                'periodo_id_usado' => $periodoId,
                'estado_usado' => $estado,
            ]
        ]);
    }


    public function resumen_historial_alertas(Request $request): JsonResponse
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $periodoId = $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null;
        $estado = trim((string) $request->input('estado', ''));


        if ($esAdmin) {

            $estudiantesIds = EstudiantesSaae::query()
                ->pluck('id')
                ->filter()
                ->unique()
                ->values();

        } else {

            $estudiantesIds = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('activo', true)
                ->pluck('estudiante_id')
                ->filter()
                ->unique()
                ->values();
        }


        if ($estudiantesIds->isEmpty()) {
            return response()->json([
                'data' => [
                    'total_historicas' => 0,
                    'atendidas' => 0,
                    'cerradas' => 0,
                    'gestionadas_hoy' => 0,
                ],
                'meta' => [
                    'periodo_id_usado' => $periodoId,
                    'estado_usado' => $estado,
                ]
            ]);
        }

        $baseQuery = AlertaAsistenciaEstudiante::query()
            ->whereIn('estudiante_id', $estudiantesIds)
            ->whereIn('estado', ['ATENDIDA', 'CERRADA'])
            ->when($periodoId, function ($query) use ($periodoId) {
                $query->where('periodo_id', $periodoId);
            });

        if ($estado !== '' && in_array($estado, ['ATENDIDA', 'CERRADA'], true)) {
            $baseQuery->where('estado', $estado);
        }

        $totalHistoricas = (clone $baseQuery)->count();

        $atendidas = (clone $baseQuery)
            ->where('estado', 'ATENDIDA')
            ->count();

        $cerradas = (clone $baseQuery)
            ->where('estado', 'CERRADA')
            ->count();

        $gestionadas = (clone $baseQuery)
            ->whereIn('estado', ['ATENDIDA', 'CERRADA'])
            ->count();

        return response()->json([
            'data' => [
                'total_historicas' => $totalHistoricas,
                'atendidas' => $atendidas,
                'cerradas' => $cerradas,
                'gestionadas' => $gestionadas,
            ],
            'meta' => [
                'periodo_id_usado' => $periodoId,
                'estado_usado' => $estado,
            ]
        ]);
    }

}