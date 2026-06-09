<?php

namespace App\Http\Controllers\Estudiante\MiAsistenciaEstudiante;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\EstudiantesSaae;

use App\Models\AsistenciaDiaria;
use App\Models\Periodo;

use Illuminate\Support\Str;

class MiAsistenciaEstudianteController extends Controller
{
    public function ver_asistencia_reciente()
    {
        $periodos = Periodo::query()
            ->orderByDesc('fecha_fin')
            ->get(['id', 'nombre']);

        return view('estudiantes.mi_asistencia.asistencia_reciente', compact('periodos'));
    }

    public function ver_hitorial_asistencia_estudiante()
    {
        $periodos = Periodo::query()
            ->orderByDesc('fecha_fin')
            ->get(['id', 'nombre']);

        return view('estudiantes.mi_asistencia.historial_asistencia', compact('periodos'));
    }


    //=========== METODO PRIVADOS DE APOYO A LOS DEMAS METODOS DE LAS TABLAS
    private function resolverPeriodoId(?int $periodoId): ?int
    {
    if ($periodoId) {
        return $periodoId;
    }

    return Periodo::query()
        ->where('activo', true)
        ->value('id')
        ?? Periodo::query()
            ->orderByDesc('fecha_inicio')
            ->value('id');
    }


    private function resolverNombreEstudiante($estudiante): string
    {
        $nombreArmado = trim(($estudiante?->nombre ?? '') . ' ' . ($estudiante?->apellidos ?? ''));

        return $nombreArmado !== ''
            ? $nombreArmado
            : ($estudiante?->nombre_completo ?? '—');
    }


    private function resolverEstadoAsistencia($asistencia): string
    {
        if (!$asistencia) {
            return 'SIN_REGISTRO';
        }

        if ($asistencia->estatus === 'FALTA' && $asistencia->justificada) {
            return 'JUSTIFICADA';
        }

        if ($asistencia->estatus === 'NO_APLICA') {
            return 'NO APLICA';
        }

        return $asistencia->estatus ?? '—';
    }



    //=========== TABLA DE ASISTENCIA RECIENTE
    public function tabla_asistencia_reciente(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $periodoId = $this->resolverPeriodoId(
            $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null
        );

        if (!$periodoId) {
            return response()->json([
                'data' => [],
            ]);
        }

        $estatus = $request->input('estatus', '');

        $registros = AsistenciaDiaria::query()
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoId)
            ->when($estatus !== '', function ($q) use ($estatus) {

                if ($estatus === 'JUSTIFICADA') {

                    $q->where('estatus', 'FALTA')
                    ->where('justificada', true);

                } elseif ($estatus === 'FALTA') {

                    $q->where('estatus', 'FALTA')
                    ->where('justificada', false);

                } elseif ($estatus === 'SIN_REGISTRO') {

                    $q->whereNull('estatus');

                } else {
                    $q->where('estatus', $estatus);
                }
            })
            ->orderByDesc('fecha')
            ->get();


        return response()->json([
            'data' => $registros->map(function ($registro) {
                return [
                    'id' => $registro->id,
                    'fecha' => $registro->getRawOriginal('fecha'),
                    'estatus_asistencia' => $this->resolverEstadoAsistencia($registro),
                    'fuente' => $registro->fuente,
                    'primera_entrada' => $registro->getRawOriginal('primera_entrada'),
                    'ultima_salida' => $registro->getRawOriginal('ultima_salida'),
                    'conteo_marcaciones' => $registro->conteo_marcaciones ?? 0,
                ];
            }),
        ]);

    }


    public function resumen_asistencia_reciente(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $periodoId = $this->resolverPeriodoId(
            $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null
        );

        if (!$periodoId) {
            return response()->json([
                'data' => [
                    'total_registros' => 0,
                    'presentes' => 0,
                    'faltas' => 0,
                    'no_aplica' => 0,
                    'porcentaje_asistencia' => 0,
                ],
            ]);
        }

        $base = AsistenciaDiaria::query()
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoId);

        $total = (clone $base)->count();
        $presentes = (clone $base)->where('estatus', 'PRESENTE')->count();

        $faltas = (clone $base)
            ->where('estatus', 'FALTA')
            ->where('justificada', false)
            ->count();

        $faltasJustificadas = (clone $base)
            ->where('estatus', 'FALTA')
            ->where('justificada', true)
            ->count();

        $noAplica = (clone $base)->where('estatus', 'NO_APLICA')->count();

        // para el porcentaje de asistencia, donde los que NO cuentan son: FALTAS, NO_APLICA y  ... 
        $totalEvaluable = $total - $noAplica;

        return response()->json([
            'data' => [
                'total_registros' => $total,
                'presentes' => $presentes,
                'faltas' => $faltas,
                'faltas_justificadas' => $faltasJustificadas,
                'no_aplica' => $noAplica,

                //cuenta PRESENTES + FALTAS JUSTIFICADAS
                'porcentaje_asistencia' => $totalEvaluable > 0
                    ? round((($presentes + $faltasJustificadas) / $totalEvaluable) * 100, 2)
                    : 0,
            ],
        ]);

    }


    public function detalle_asistencia_estudiante(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $periodoId = $this->resolverPeriodoId(
            $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null
        );

        if (!$periodoId) {
            return response()->json([
                'message' => 'No se encontró un periodo disponible.',
            ], 404);
        }

        $estudiante = EstudiantesSaae::query()
            ->where('id', $estudianteId)
            ->firstOrFail();

        $nombreArmado = trim(($estudiante->nombre ?? '') . ' ' . ($estudiante->apellidos ?? ''));
        $nombreMostrable = $nombreArmado !== '' ? $nombreArmado : ($estudiante->nombre_completo ?? '—');

        //para registros en el modal
        $registros = AsistenciaDiaria::query()
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoId)
            ->get();

        $total = $registros->count();

        $presentes = $registros
            ->where('estatus', 'PRESENTE')
            ->count();

        $faltas = $registros
            ->where('estatus', 'FALTA')
            ->where('justificada', false)
            ->count();

        $faltasJustificadas = $registros
            ->where('estatus', 'FALTA')
            ->where('justificada', true)
            ->count();

        $noAplica = $registros
            ->where('estatus', 'NO_APLICA')
            ->count();

        // para el porcentaje de asistencia, donde los que NO cuentan son: FALTAS, NO_APLICA y  ... 
        $totalEvaluable = $total - $noAplica;

        // Para la tabla del modal
        $detalle = AsistenciaDiaria::query()
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoId)
            ->orderByDesc('fecha')
            ->get()
            ->map(fn ($a) => [
                'fecha' => $a->getRawOriginal('fecha'),
                'estatus_asistencia' => $this->resolverEstadoAsistencia($a),
                'fuente' => $a->fuente,
                'primera_entrada' => $a->getRawOriginal('primera_entrada'),
                'ultima_salida' => $a->getRawOriginal('ultima_salida'),
                'conteo_marcaciones' => $a->conteo_marcaciones ?? 0,
            ]);

        return response()->json([
            'data' => [
                'id' => $estudiante->id,
                'numero_control' => $estudiante->numero_control,
                'nombre_estudiante' => $nombreMostrable,
                'metricas' => [
                    'total_registros' => $total,
                    'presentes' => $presentes,
                    'faltas' => $faltas,
                    'faltas_justificadas' => $faltasJustificadas,
                    'no_aplica' => $noAplica,

                    //cuenta PRESENTES + FALTAS JUSTIFICADAS
                    'porcentaje_asistencia' => $totalEvaluable > 0
                        ? round((($presentes + $faltasJustificadas) / $totalEvaluable) * 100, 2)
                        : 0,
                ],
                'detalle' => $detalle,
            ],
        ]);

    }


    //=========== TABLA DE HISTORIAL DE ASISTENCIA
    public function tabla_historial_asistencia_estudiante(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $periodoId = $this->resolverPeriodoId(
            $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null
        );

        if (!$periodoId) {
            return response()->json([
                'message' => 'No se encontró un periodo válido para consultar.'
            ], 422);
        }

        $estudiante = EstudiantesSaae::query()
            ->where('id', $estudianteId)
            ->firstOrFail();

        $registros = AsistenciaDiaria::query()
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoId)
            ->orderByDesc('fecha')
            ->get();

        $detalle = $registros->map(function ($a) {
            return [
                'fecha' => $a->getRawOriginal('fecha'),
                'estatus_asistencia' => $this->resolverEstadoAsistencia($a),
                'fuente' => $a->fuente,
                'primera_entrada' => $a->getRawOriginal('primera_entrada'),
                'ultima_salida' => $a->getRawOriginal('ultima_salida'),
                'conteo_marcaciones' => $a->conteo_marcaciones ?? 0,
            ];
        })->values();

        $total = $registros->count();
        $presentes = $registros->where('estatus', 'PRESENTE')->count();

        $faltas = $registros
            ->where('estatus', 'FALTA')
            ->where('justificada', false)
            ->count();

        $faltasJustificadas = $registros
            ->where('estatus', 'FALTA')
            ->where('justificada', true)
            ->count();

        $noAplica = $registros->where('estatus', 'NO_APLICA')->count();

        // para el porcentaje de asistencia, donde los que NO cuentan son: FALTAS, NO_APLICA y  ... 
        $totalEvaluable = $total - $noAplica;

        return response()->json([
            'data' => [
                'id' => $estudiante->id,
                'numero_control' => $estudiante->numero_control,
                'nombre_estudiante' => $this->resolverNombreEstudiante($estudiante),
                'detalle' => $detalle,
                'metricas' => [
                    'total_registros' => $total,
                    'presentes' => $presentes,
                    'faltas' => $faltas,
                    'faltas_justificadas' => $faltasJustificadas,
                    'no_aplica' => $noAplica,

                    //cuenta PRESENTES + FALTAS JUSTIFICADAS
                    'porcentaje_asistencia' => $totalEvaluable > 0
                        ? round((($presentes + $faltasJustificadas) / $totalEvaluable) * 100, 2)
                        : 0,
                ],
            ],
        ]);

    }

}