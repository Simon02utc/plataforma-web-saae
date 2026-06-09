<?php

namespace App\Http\Controllers\Estudiante\MisAlertas;

use App\Http\Controllers\Controller;
use App\Models\AlertaAsistenciaEstudiante;
use App\Models\Periodo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;


class AlertasAsistenciaEstudianteController extends Controller
{
    
    //================== VISTAS ==================
    public function alertas(Request $request)
    {
        $periodos = Periodo::query()
            ->orderByDesc('fecha_fin')
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin']);

        return view('estudiantes.mis_alertas.alertas', compact('periodos'));
    }

    public function historial_alertas(Request $request)
    {
        $periodos = Periodo::query()
            ->orderByDesc('fecha_fin')
            ->get(['id', 'nombre', 'fecha_inicio', 'fecha_fin']);

        return view('estudiantes.mis_alertas.historial_alertas', compact('periodos'));
    }


    //================== METODO PRIVADOS DE APOYO
    private function resolverNombrePersonal($personal): string
    {
        if (!$personal) {
            return '—';
        }

        return trim(($personal->nombre ?? '') . ' ' . ($personal->apellidos ?? '')) ?: '—';
    }

    private function resolverTipoAlertaTexto(string $tipo): string
    {
        return match ($tipo) {
            'FALTA_ACUMULADA' => 'FALTA ACUMULADA',
            'SUSPENSION_BECA_ESCOLAR' => 'SUSPENCIÓN DE BECA ESCOLAR',
            default => $tipo,
        };
    }
    //============================================


    // ================== TABLA DE JUSTIFICANTES ==================
    public function tabla_alertas(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $buscar = trim((string) $request->input('buscar', ''));
        $periodoId = $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null;
        $tipo = $request->input('tipo', '');
        $estado = $request->input('estado', '');
        $perPage = (int) $request->input('per_page', 20);

        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $paginado = AlertaAsistenciaEstudiante::query()
            ->with(['periodo:id,nombre'])
            ->where('estudiante_id', $estudianteId)
            ->when($periodoId, fn ($q) => $q->where('periodo_id', $periodoId))
            ->when($tipo !== '', fn ($q) => $q->where('tipo_alerta', $tipo))
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($qq) use ($buscar) {
                    $qq->where('tipo_alerta', 'like', "%{$buscar}%")
                        ->orWhere('estado', 'like', "%{$buscar}%")
                        ->orWhere('observaciones', 'like', "%{$buscar}%");
                });
            })
            ->orderByDesc('fecha_disparo')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginado->getCollection()->map(function ($a) {
                return [
                    'id' => $a->id,
                    'periodo' => $a->periodo?->nombre,
                    'tipo_alerta' => $this->resolverTipoAlertaTexto($a->tipo_alerta),
                    'estado_alerta' => $a->estado,
                    'estado_texto' => $a->estado,
                    'valor_detectado' => $a->valor_detectado,
                    'fecha_referencia' => $a->getRawOriginal('fecha_referencia'),
                    'fecha_disparo' => $a->getRawOriginal('fecha_disparo'),
                ];
            })->values(),
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page' => $paginado->lastPage(),
                'per_page' => $paginado->perPage(),
                'total' => $paginado->total(),
                'from' => $paginado->firstItem(),
                'to' => $paginado->lastItem(),
            ],
        ]);
    }


    public function resumen_alertas(Request $request): JsonResponse
    {
        $estudianteId = auth('estudiante')->id();

        $periodoId = $request->filled('periodo_id')
            ? (int) $request->input('periodo_id')
            : null;

        $tipo = trim((string) $request->input('tipo', ''));
        $estado = trim((string) $request->input('estado', ''));

        $tiposPermitidos = [
            'FALTA_ACUMULADA',
            'SUSPENSION_BECA_ESCOLAR',
        ];

        $estadosPermitidos = [
            'PENDIENTE',
            'ATENDIDA',
            'CERRADA',
        ];

        if ($tipo !== '' && !in_array($tipo, $tiposPermitidos, true)) {
            $tipo = '';
        }

        if ($estado !== '' && !in_array($estado, $estadosPermitidos, true)) {
            $estado = '';
        }

        $baseQuery = AlertaAsistenciaEstudiante::query()
            ->where('estudiante_id', $estudianteId)
            ->when($periodoId, function ($query) use ($periodoId) {
                $query->where('periodo_id', $periodoId);
            })
            ->when($tipo !== '', function ($query) use ($tipo) {
                $query->where('tipo_alerta', $tipo);
            })
            ->when($estado !== '', function ($query) use ($estado) {
                $query->where('estado', $estado);
            });

        $pendientes = (clone $baseQuery)
            ->where('estado', 'PENDIENTE')
            ->count();

        $normales = (clone $baseQuery)
            ->where('tipo_alerta', 'FALTA_ACUMULADA')
            ->count();

        $especiales = (clone $baseQuery)
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
                'tipo_usado' => $tipo,
                'estado_usado' => $estado,
            ],
        ]);
    }


    public function ver_alerta(int $id)
    {
        $estudianteId = auth('estudiante')->id();

        $alerta = AlertaAsistenciaEstudiante::query()
            ->with(['periodo:id,nombre'])
            ->where('estudiante_id', $estudianteId)
            ->where('id', $id)
            ->firstOrFail();

        return response()->json([
            'alerta' => [
                'id' => $alerta->id,
                'tipo_alerta' => $alerta->tipo_alerta,
                'estado_alerta' => $alerta->estado,
                'estado_texto' => $alerta->estado,
                'periodo' => [
                    'nombre' => $alerta->periodo?->nombre,
                ],
                'valor_detectado' => $alerta->valor_detectado,
                'fecha_referencia' => $alerta->getRawOriginal('fecha_referencia'),
                'fecha_disparo' => $alerta->getRawOriginal('fecha_disparo'),
                'observaciones' => $alerta->observaciones,
            ],
        ]);
    }



    //================== TABLA DE HISTORIAL DE ASISTENCIA ==================
    public function tabla_historial_alertas(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $buscar = trim((string) $request->input('buscar', ''));
        $periodoId = $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null;
        $tipo = trim((string) $request->input('tipo', ''));
        $estado = trim((string) $request->input('estado', ''));
        $perPage = (int) $request->input('per_page', 20);

        if (!in_array($perPage, [10, 20, 50, 100], true)) {
            $perPage = 20;
        }

        $paginado = AlertaAsistenciaEstudiante::query()
            ->with(['periodo:id,nombre', 'atendidaPor:id,nombre,apellidos'])
            ->where('estudiante_id', $estudianteId)
            ->whereIn('estado', ['ATENDIDA', 'CERRADA'])
            ->when($periodoId, fn ($q) => $q->where('periodo_id', $periodoId))
            ->when($tipo !== '', fn ($q) => $q->where('tipo_alerta', $tipo))
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado))
            ->when($buscar !== '', function ($q) use ($buscar) {
                $q->where(function ($qq) use ($buscar) {
                    $qq->where('tipo_alerta', 'like', "%{$buscar}%")
                        ->orWhere('estado', 'like', "%{$buscar}%")
                        ->orWhere('observaciones', 'like', "%{$buscar}%");
                });
            })
            ->orderByDesc('atendida_en')
            ->orderByDesc('fecha_disparo')
            ->paginate($perPage);

        return response()->json([
            'data' => $paginado->getCollection()->map(function ($a) {
                return [
                    'id' => $a->id,
                    'periodo' => $a->periodo?->nombre,
                    'tipo_alerta' => $this->resolverTipoAlertaTexto($a->tipo_alerta),
                    'estado_alerta' => $a->estado,
                    'valor_detectado' => $a->valor_detectado,
                    'fecha_referencia' => $a->getRawOriginal('fecha_referencia'),
                    'fecha_disparo' => $a->getRawOriginal('fecha_disparo'),
                    'atendida_en' => $a->getRawOriginal('atendida_en'),
                    'atendida_por_nombre' => $this->resolverNombrePersonal($a->atendidaPor),
                ];
            })->values(),
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page' => $paginado->lastPage(),
                'per_page' => $paginado->perPage(),
                'total' => $paginado->total(),
                'from' => $paginado->firstItem(),
                'to' => $paginado->lastItem(),
            ],
        ]);
    }


    public function resumen_historial_alertas(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $periodoId = $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null;
        $tipo = trim((string) $request->input('tipo', ''));
        $estado = trim((string) $request->input('estado', ''));

        $baseQuery = AlertaAsistenciaEstudiante::query()
            ->where('estudiante_id', $estudianteId)
            ->whereIn('estado', ['ATENDIDA', 'CERRADA'])
            ->when($periodoId, fn ($q) => $q->where('periodo_id', $periodoId))
            ->when($tipo !== '', fn ($q) => $q->where('tipo_alerta', $tipo))
            ->when($estado !== '', fn ($q) => $q->where('estado', $estado));

        return response()->json([
            'data' => [
                'total_historicas' => (clone $baseQuery)->count(),
                'atendidas' => (clone $baseQuery)->where('estado', 'ATENDIDA')->count(),
                'cerradas' => (clone $baseQuery)->where('estado', 'CERRADA')->count(),
                'gestionadas' => (clone $baseQuery)->whereIn('estado', ['ATENDIDA', 'CERRADA'])->count(),
            ],
        ]);
    }


}