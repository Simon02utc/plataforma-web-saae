<?php

namespace App\Http\Controllers\Estudiante\PanelEstudiante;

use App\Http\Controllers\Controller;
use App\Models\AlertaAsistenciaEstudiante;
use App\Models\AsistenciaDiaria;
use App\Models\JustificanteEstudiante;
use App\Models\Periodo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


class PanelEstudianteController extends Controller
{
    public function ver_panel_estudiante(Request $request)
    {
        /* ========== AUTENTICACION ========== */
        $estudiante = auth('estudiante')->user();
        $estudianteId = $estudiante->id;


        /* ========== PARAMETROS DE FILTRO ========== */
        $periodos = Periodo::query()
            ->whereHas('estudiantes', function ($q) use ($estudianteId) {
                $q->where('estudiantes_saae.id', $estudianteId);
            })
            ->orderByDesc('fecha_inicio')
            ->get();


        // ===== SELECT DE PERIODO
        $periodoIdSeleccionado = $request->filled('periodo_id')
            ? (int) $request->input('periodo_id')
            : null;


        // cargar automaticamente el mas reciente
        if (!$request->has('periodo_id')) {

            $periodo = $periodos->first();

            $periodoIdSeleccionado = $periodo?->id;

        } else {

            // todos los periodos
            if (!$periodoIdSeleccionado) {

                $periodo = null;
            } else {
                $periodo = $periodos
                    ->firstWhere('id', $periodoIdSeleccionado);
            }
        }


        /* ========== SIN PERIODOS ========== */
        if ($periodos->isEmpty()) {

            return view('estudiantes.panel_estudiante', [
                'estudiante' => $estudiante,
                'periodo' => null,
                'periodos' => collect(),
                'periodoIdSeleccionado' => null,
                'resumenAsistencia' => [],
                'graficaAsistencia' => [],
                'graficaAsistenciaDias' => [],
                'resumenAlertas' => [],
                'resumenJustificantes' => [],
                'ultimasAlertas' => collect(),
                'ultimosJustificantes' => collect(),
            ]);
        }


        /* ========== BASE ASISTENCIA ========== */
        $baseAsistencia = AsistenciaDiaria::query()
            ->where('estudiante_id', $estudianteId)
            ->where('esperado', true);

        if ($periodoIdSeleccionado) {
            $baseAsistencia->where('periodo_id', $periodoIdSeleccionado);
        }


        /* ========== TARJETAS RESUMEN ========== */
        $totalDias = (clone $baseAsistencia)->count();

        $presentes = (clone $baseAsistencia)
            ->where('estatus', 'PRESENTE')
            ->count();

        $faltasJustificadas = (clone $baseAsistencia)
            ->where('estatus', 'FALTA')
            ->where('justificada', true)
            ->count();

        $faltas = (clone $baseAsistencia)
            ->where('estatus', 'FALTA')
            ->where(function ($q) {
                $q->where('justificada', false)
                    ->orWhereNull('justificada');
            })
            ->count();

        $noAplica = (clone $baseAsistencia)
            ->where('estatus', 'NO_APLICA')
            ->count();

        // para el porcentaje de asistencia, donde los que NO cuentan son: FALTAS, NO_APLICA y  ... 
        $totalEvaluable = $totalDias - $noAplica;

        //cuenta PRESENTES + FALTAS JUSTIFICADAS
        $porcentaje = $totalEvaluable > 0
                ? round((($presentes + $faltasJustificadas) / $totalEvaluable) * 100, 2)
                : 0;


        $resumenAsistencia = [
            'total_dias' => $totalDias,
            'presentes' => $presentes,
            'faltas' => $faltas,
            'faltas_justificadas' => $faltasJustificadas,
            'no_aplica' => $noAplica,
            'porcentaje' => $porcentaje,
        ];


        /* ========== GRAFICA RESUMEN ========== */
        $graficaAsistencia = [
            'labels' => ['Presentes', 'Faltas', 'Justificadas', 'No aplica'],
            'data' => [$presentes, $faltas, $faltasJustificadas, $noAplica],
        ];


        /* ========== GRAFICA ASISTENCIA POR DIA ========== */
        $asistenciaPorDiaQuery = AsistenciaDiaria::query()
            ->where('estudiante_id', $estudianteId)
            ->where('esperado', true);

        if ($periodoIdSeleccionado) {
            $asistenciaPorDiaQuery->where('periodo_id', $periodoIdSeleccionado);
        }

        $asistenciaPorDia = $asistenciaPorDiaQuery
            ->orderBy('fecha')
            ->get([
                'fecha',
                'estatus',
                'justificada'
            ]);

        $graficaAsistenciaDias = [
            'labels' => $asistenciaPorDia
                ->map(fn ($item) =>
                    optional($item->fecha)->format('d/m')
                )
                ->values(),

            'data' => $asistenciaPorDia
                ->map(function ($item) {

                    // presente
                    if ($item->estatus === 'PRESENTE') {
                        return 3;
                    }

                    // falta justificada
                    if (
                        $item->estatus === 'FALTA'
                        && $item->justificada
                    ) {
                        return 2;
                    }

                    // falta normal
                    if ($item->estatus === 'FALTA') {
                        return 1;
                    }

                    // no aplica
                    return 0;
                })
                ->values(),

            'estados' => $asistenciaPorDia
                ->map(function ($item) {

                    if ($item->estatus === 'PRESENTE') {
                        return 'Presente';
                    }

                    if (
                        $item->estatus === 'FALTA'
                        && $item->justificada
                    ) {
                        return 'Falta justificada';
                    }

                    if ($item->estatus === 'FALTA') {
                        return 'Falta';
                    }

                    return 'No aplica';
                })
                ->values(),
        ];


        /* ========== ALERTAS ========== */
        $alertasQuery = AlertaAsistenciaEstudiante::query()
            ->where('estudiante_id', $estudianteId);

        if ($periodoIdSeleccionado) {
            $alertasQuery->where('periodo_id', $periodoIdSeleccionado);
        }


        $alertasPorEstado = (clone $alertasQuery)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');


        $resumenAlertas = [
            'pendientes' => $alertasPorEstado['PENDIENTE'] ?? 0,
            'atendidas'  => $alertasPorEstado['ATENDIDA'] ?? 0,
            'cerradas'   => $alertasPorEstado['CERRADA'] ?? 0,
        ];


        /* alertas de asistencia */
        $ultimasAlertas = AlertaAsistenciaEstudiante::query()
            ->where('estudiante_id', $estudianteId);

        if ($periodoIdSeleccionado) {
            $ultimasAlertas->where('periodo_id', $periodoIdSeleccionado);
        }

        $ultimasAlertas = $ultimasAlertas
            ->orderByDesc('fecha_referencia')
            ->limit(50)
            ->get();


        /* ========== JUSTIFICANTES ========== */
        $justificantesQuery = JustificanteEstudiante::query()
            ->where('estudiante_id', $estudianteId);

        if ($periodoIdSeleccionado) {
            $justificantesQuery->where('periodo_id', $periodoIdSeleccionado);
        }

        $justificantesPorEstado = (clone $justificantesQuery)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');


        $resumenJustificantes = [
            'pendientes' => $justificantesPorEstado['PENDIENTE'] ?? 0,
            'aprobados' => $justificantesPorEstado['APROBADO'] ?? 0,
            'rechazados' => $justificantesPorEstado['RECHAZADO'] ?? 0,
        ];


        $ultimosJustificantes = (clone $justificantesQuery)
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();


        /* ========== RETORNO ========== */
        return view('estudiantes.panel_estudiante', compact(
            'estudiante',
            'periodo',
            'periodos',
            'periodoIdSeleccionado',
            'resumenAsistencia',
            'graficaAsistencia',
            'graficaAsistenciaDias',
            'resumenAlertas',
            'resumenJustificantes',
            'ultimasAlertas',
            'ultimosJustificantes'
        ));
    }

}