<?php

namespace App\Http\Controllers\Personal\PanelPersonal;

use App\Http\Controllers\Controller;
use App\Models\AlertaAsistenciaEstudiante;
use App\Models\AsistenciaDiaria;
use App\Models\EstudianteConPersonalSaae;
use App\Models\EstudiantesSaae;
use App\Models\JustificanteEstudiante;
use App\Models\Periodo;
use App\Models\RolesPersonalSaae;
use App\Models\AreasEspecialidadEstudiantesSaae;
use App\Models\EstatusEscolaresEstudiantesSaae;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;


use PhpOffice\PhpSpreadsheet\Spreadsheet; 
use PhpOffice\PhpSpreadsheet\Writer\Xlsx; 
use Symfony\Component\HttpFoundation\StreamedResponse; 
use PhpOffice\PhpSpreadsheet\Style\Alignment; 
use PhpOffice\PhpSpreadsheet\Style\Border; 
use PhpOffice\PhpSpreadsheet\Style\Fill; 
use PhpOffice\PhpSpreadsheet\Style\Color;


class PanelPersonalController extends Controller
{

    public function ver_panel_personal(Request $request)
    {
        $data = $this->obtenerDatosDashboard($request);

        return view('personal.panel_personal', $data);
    }

    private function obtenerDatosDashboard(Request $request)
    {
        /* ========== AUTENTICACION DEL USUARIOS ========== */
        $personal    = auth('personal')->user();
        $personalId  = $personal->id;
        $esAdmin     = $personal->esAdmin();


        /* ========== ROLES CON ESTUDIANTES ASIGNADOS (solo para no-admin) ========== */
        // Para el admin no aplica el filtro de roles. Pero para el resto, se obtiene unicamente los roles que tengan al menos 1 estudiante activo asignado
        // a este personal.
        $rolesConEstudiantes = collect();

        if (!$esAdmin) {
            // IDs de roles que este personal tiene Y que tienen ≥1 estudiante activo asignado
            $rolesConEstudiantes = RolesPersonalSaae::query()
                ->whereHas('RolConAsignacion', function ($q) use ($personalId) {
                    $q->where('personal_id', $personalId)
                        ->where('activo', true);
                })
                // solo roles que el personal tenga realmente asignados
                ->whereHas('personal', function ($q) use ($personalId) {
                    $q->where('personal_saae.id', $personalId);
                })
                ->get(['id', 'nombre', 'clave']);
        }


        /* ========== PARAMETROS DE FILTRO ========== */
        $periodos = Periodo::query()
            ->orderByDesc('fecha_inicio')
            ->get();

        //no hay periodos
        $hayPeriodos = $periodos->isNotEmpty();

        $areasEspecialidad = AreasEspecialidadEstudiantesSaae::query()
            ->where('activo', true)
            ->orderByDesc('created_at')
            ->get();

        $estatusEscolares = EstatusEscolaresEstudiantesSaae::query()
            ->where('activo', true)
            ->orderByDesc('created_at')
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

            // si elege "Todos los periodos"
            if (!$periodoIdSeleccionado) {

                $periodo = null;

            } else {
                $periodo = $periodos
                    ->firstWhere('id', $periodoIdSeleccionado);
            }
        }


        // ===== SELECT DE ROL
        $rolIdSeleccionado = (!$esAdmin && $request->filled('rol_id'))
            ? (int) $request->input('rol_id')
            : null;


        // ===== SELECT DE AREA DE ESPECIALIDAD
        $areaIdSeleccionada = $request->filled('area_id')
            ? (int) $request->input('area_id')
            : null;


        // ===== SELECT DE ESTATUS por defecto INSCRITO
        $estatusInscrito = $estatusEscolares
            ->firstWhere('clave', 'inscrito');


        // ===== SELECT DE ESTATUS ESCOLAR
        $estatusIdSeleccionado = $request->has('estatus_id')
            ? ($request->input('estatus_id') !== ''
                ? (int) $request->input('estatus_id')
                : null)
            : ($estatusInscrito?->id ?? null);

        
        // ===== SELECT DE ESTUDIANTE
        $estudianteId = trim($request->input('estudiante_id', ''));
        $nombreEstudianteSeleccionado = '';
        


        /* ========== SIN PERIODOS ========== */
        if (!$hayPeriodos) {
            return [
                'personal' => $personal,
                'esAdmin' => $esAdmin,
                'periodo' => null,
                'periodos' => collect(),
                'hayPeriodos' => false,
                'periodoIdSeleccionado'=> null,
                'rolesConEstudiantes' => $rolesConEstudiantes,
                'rolIdSeleccionado' => null,
                'areasEspecialidad' => collect(),
                'estatusEscolares' => collect(),
                'areaIdSeleccionada' => null,
                'estatusIdSeleccionado' => null,
                'nombreEstudianteSeleccionado' => '',
                'estudiantesBusqueda' => [],
                'estudianteId' => null,
                'resumenGeneral' => [],
                'graficaResumenAsistencia' => [],
                'graficaAsistenciaDias' => [],
                'graficaFaltasDias' => [],
                'resumenAlertas' => [],
                'resumenJustificantes' => [],
                'topEstudiantesFaltas' => collect(),
                'ultimasAlertas' => collect(),
                'ultimosJustificantes' => collect(),
            ];
        }

        /* ========== RESOLUCION DE IDs DE ESTUDIANTES VISIBLES ========== */
        // Admin = null  (sin restriccion, ve todos)
        // No-admin + todos sus roles = IDs de todos los estudiantes asignados activos
        // No-admin + rol especofico  = IDs de estudiantes asignados bajo ese rol
        $estudiantesIdsVisibles = null;

        if (!$esAdmin) {
            $queryAsignacion = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('activo', true);

            if ($rolIdSeleccionado) {
                $queryAsignacion->where('role_id', $rolIdSeleccionado);
            }

            $estudiantesIdsVisibles = $queryAsignacion
                ->pluck('estudiante_id')
                ->unique()
                ->values();
        }


        /* ========== QUERY PRINCIPAL ========== */
        $queryEstudiantes = EstudiantesSaae::query()
            ->with('estudiantesConDatosEscolares')
            ->whereHas('estudiantesConDatosEscolares');
            
        if ($estudiantesIdsVisibles !== null) {
            $queryEstudiantes->whereIn('id', $estudiantesIdsVisibles);
        }


        // ========== filtros progresivos ========== */
        if ($areaIdSeleccionada || $estatusIdSeleccionado) {

            $queryEstudiantes->whereHas('estudiantesConDatosEscolares', function ($q) use (
                $areaIdSeleccionada,
                $estatusIdSeleccionado
            ) {

                if ($areaIdSeleccionada) {
                    $q->where('especialidad_id', $areaIdSeleccionada);
                }

                if ($estatusIdSeleccionado) {
                    $q->where('estatus_escolar_id', $estatusIdSeleccionado);
                }
            });
        }


        /* ========== busqueda de estudiantes ========== */
        $estudiantesBusqueda = (clone $queryEstudiantes)
            ->select('id','numero_control','nombre_completo','nombre','apellidos')
            ->orderBy('nombre_completo')
            ->limit(300)
            ->get();

        if (!empty($estudianteId)) {

            $queryEstudiantes->where('id', $estudianteId);

            $nombreEstudianteSeleccionado = $estudiantesBusqueda
                ->firstWhere('id', $estudianteId)?->nombre_completo ?? '';
        }


        /* ========== IDs filtrados ========== */
        $queryEstudiantesFiltrados = clone $queryEstudiantes;

        $estudiantesIdsFiltrados = $queryEstudiantesFiltrados
            ->pluck('id')
            ->unique()
            ->values();
        
        // no hay estudiantes con datos escolares
        $tieneEstudiantesConDatos = $estudiantesIdsFiltrados->isNotEmpty();


        /* ========== TARJETAS RESUMEN ========== */
        $totalEstudiantes = $estudiantesIdsFiltrados->count(); 


        $baseAsistencia = AsistenciaDiaria::query()
            ->where('esperado', true);

        if ($periodoIdSeleccionado) {
            $baseAsistencia->where('periodo_id', $periodoIdSeleccionado);
        }

        if ($estudiantesIdsFiltrados !== null) {
            $baseAsistencia->whereIn('estudiante_id', $estudiantesIdsFiltrados);
        }

        $totalDias = (clone $baseAsistencia)->count();

        $presentes = (clone $baseAsistencia)->where('estatus', 'PRESENTE')->count();

        $faltasJustificadas = (clone $baseAsistencia)
            ->where('estatus', 'FALTA')
            ->where('justificada', true)
            ->count();

        $faltas = (clone $baseAsistencia)
            ->where('estatus', 'FALTA')
            ->where(function ($q) {
                $q->where('justificada', false)->orWhereNull('justificada');
            })
            ->count();
    
        //---Faltaria RETARDO  para colocar si se quiere implementar---

        $noAplica  = (clone $baseAsistencia)->where('estatus', 'NO_APLICA')->count();

        // para el porcentaje de asistencia, donde los que NO cuentan son: FALTAS, NO_APLICA y  ... 
        $totalEvaluable = $totalDias - $noAplica;

        //cuenta PRESENTES + FALTAS JUSTIFICADAS
        $porcentaje = $totalEvaluable > 0
                ? round((($presentes + $faltasJustificadas) / $totalEvaluable) * 100, 2)
                : 0;

        $resumenGeneral = [
            'total_estudiantes'  => $totalEstudiantes,
            'total_dias' => $totalDias,
            'presentes' => $presentes,
            'faltas' => $faltas,
            'faltas_justificadas'=> $faltasJustificadas,
            'no_aplica' => $noAplica,
            'porcentaje' => $porcentaje,
        ];


        /* ========== GRAFICA RESUMEN ========== */
        $graficaResumenAsistencia = [
            'labels' => ['Presentes', 'Faltas', 'Justificadas', 'No aplica'],
            'data'   => [$presentes, $faltas, $faltasJustificadas, $noAplica],
        ];


        /* ========== GRAFICA ASISTENCIA POR DIA ========== */
        $asistenciasPorDiaQuery = AsistenciaDiaria::query()
            ->where('esperado', true)
            ->where(function ($q) {

                // PRESENTE
                $q->where('estatus', 'PRESENTE')

                // FALTA JUSTIFICADA
                ->orWhere(function ($sub) {

                    $sub->where('estatus', 'FALTA')
                        ->where('justificada', true);
                });
            });
        
        if ($periodoIdSeleccionado) {
            $asistenciasPorDiaQuery->where('periodo_id', $periodoIdSeleccionado);
        }

        if ($estudiantesIdsFiltrados !== null) {
            $asistenciasPorDiaQuery->whereIn(
                'estudiante_id',
                $estudiantesIdsFiltrados
            );
        }

        $asistenciasPorDia = $asistenciasPorDiaQuery
            ->orderBy('fecha')
            ->get([
                'fecha',
                'estatus',
                'justificada'
            ]);

        /* agrupar por fecha */
        $asistenciasAgrupadas = $asistenciasPorDia
            ->groupBy(fn ($item) =>
                optional($item->fecha)->format('d/m')
            );

        $labels = [];
        $data = [];
        $estados = [];

        foreach ($asistenciasAgrupadas as $fecha => $items) {

            $presentes = $items
                ->where('estatus', 'PRESENTE')
                ->count();

            $justificadas = $items
                ->where('estatus', 'FALTA')
                ->where('justificada', true)
                ->count();

            $labels[] = $fecha;

            // total de asistencias + justificadas
            $data[] = $presentes + $justificadas;

            $estados[] = [
                'presentes' => $presentes,
                'justificadas' => $justificadas,
            ];
        }

        $graficaAsistenciaDias = [
            'labels' => $labels,
            'data' => $data,
            'estados' => $estados,
        ];


        /* ========== GRAFICA FALTAS ASISTENCIA POR DIA ========== */
        $faltasPorDiaQuery = AsistenciaDiaria::query()
            ->where('esperado', true)
            ->where('estatus', 'FALTA')
            ->where(function ($q) {
                $q->where('justificada', false)->orWhereNull('justificada');
            });

        if ($periodoIdSeleccionado) {
            $faltasPorDiaQuery->where('periodo_id', $periodoIdSeleccionado);
        }

        if ($estudiantesIdsFiltrados !== null) {
            $faltasPorDiaQuery->whereIn('estudiante_id', $estudiantesIdsFiltrados);
        }

        $faltasPorDia = $faltasPorDiaQuery
            ->select('fecha', DB::raw('COUNT(*) as total'))
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $graficaFaltasDias = [
            'labels' => $faltasPorDia->map(fn ($item) => optional($item->fecha)->format('d/m'))->values(),
            'data' => $faltasPorDia->pluck('total')->values(),
        ];


        /* ========== ALERTAS ========== */
        $alertasQuery = AlertaAsistenciaEstudiante::query();

        if ($periodoIdSeleccionado) {
            $alertasQuery->where('periodo_id', $periodoIdSeleccionado);
        }

        if ($estudiantesIdsFiltrados !== null) {
            $alertasQuery->whereIn('estudiante_id', $estudiantesIdsFiltrados);
        }

        $alertasPorEstado = (clone $alertasQuery)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $resumenAlertas = [
            'pendientes' => $alertasPorEstado['PENDIENTE'] ?? 0,
            'atendidas'  => $alertasPorEstado['ATENDIDA']  ?? 0,
            'cerradas'   => $alertasPorEstado['CERRADA']   ?? 0,
        ];


        /* alertas agrupadas */
        $ultimasAlertas = (clone $alertasQuery)
            ->with('estudiante:id,numero_control,nombre_completo')
            ->orderByDesc('fecha_referencia')
            ->get()

            // agrupar por estudiante + tipo de alerta
            ->groupBy(function ($alerta) {

                return $alerta->estudiante_id . '_' . $alerta->tipo_alerta;
            })

            ->map(function ($alertasGrupo) {

                $primera = $alertasGrupo->first();

                // estados agrupados 
                $estados = $alertasGrupo
                    ->groupBy('estado')
                    ->map(function ($items, $estado) {

                        return [
                            'estado' => $estado,
                            'total'  => $items->count(),
                        ];
                    })
                    ->values();


                // fechas ordenadas
                $fechas = $alertasGrupo
                    ->pluck('fecha_referencia')
                    ->filter()
                    ->sort()
                    ->map(fn ($fecha) =>
                        \Carbon\Carbon::parse($fecha)->format('d/m/Y')
                    )
                    ->implode(', ');


                return [
                    'estudiante' => $primera->estudiante,
                    'tipo_alerta' => $primera->tipo_alerta,
                    'tipo_alerta_texto' => $primera->tipo_alerta_texto,
                    'estados' => $estados,
                    'fechas' => $fechas,
                    'total' => $alertasGrupo->count(),
                ];
            })

            ->values()
            ->take(50);


        /* ========== JUSTIFICANTES ========== */
        $justificantesQuery = JustificanteEstudiante::query();

        if ($periodoIdSeleccionado) {
            $justificantesQuery->where('periodo_id', $periodoIdSeleccionado);
        }

        if ($estudiantesIdsFiltrados !== null) {
            $justificantesQuery->whereIn('estudiante_id', $estudiantesIdsFiltrados);
        }

        $justificantesPorEstado = (clone $justificantesQuery)
            ->select('estado', DB::raw('COUNT(*) as total'))
            ->groupBy('estado')
            ->pluck('total', 'estado');

        $resumenJustificantes = [
            'pendientes' => $justificantesPorEstado['PENDIENTE'] ?? 0,
            'aprobados'  => $justificantesPorEstado['APROBADO']  ?? 0,
            'rechazados' => $justificantesPorEstado['RECHAZADO'] ?? 0,
        ];

        $ultimosJustificantes = (clone $justificantesQuery)
            ->with('estudiante:id,numero_control,nombre_completo')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();


        /* ========== TOP ESTUDIANTES CON MAS FALTAS ========== */
        $topEstudiantesFaltasQuery = AsistenciaDiaria::query()
            ->with([
                'estudiante:id,numero_control,nombre_completo',
                'estudiante.estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre'
            ])
            ->where('esperado', true)
            ->where('estatus', 'FALTA')
            ->where(function ($q) {
                $q->where('justificada', false)->orWhereNull('justificada');
            })
            ->select('estudiante_id', DB::raw('COUNT(*) as total_faltas'))
            ->groupBy('estudiante_id')
            ->orderByDesc('total_faltas')
            ->limit(5);
        
        if ($periodoIdSeleccionado) {
            $topEstudiantesFaltasQuery->where('periodo_id', $periodoIdSeleccionado);
        }

        if ($estudiantesIdsFiltrados !== null) {
            $topEstudiantesFaltasQuery->whereIn('estudiante_id', $estudiantesIdsFiltrados);
        }

        $topEstudiantesFaltas = $topEstudiantesFaltasQuery->get();


        /* ========== RETORNO A LA VISTA ========== */
        return compact(
            'personal',
            'esAdmin',
            'periodo',
            'periodos',
            'hayPeriodos',
            'tieneEstudiantesConDatos',
            'periodoIdSeleccionado',
            'rolesConEstudiantes',
            'rolIdSeleccionado',
            'areasEspecialidad',
            'estatusEscolares',
            'areaIdSeleccionada',
            'estatusIdSeleccionado',
            'nombreEstudianteSeleccionado',
            'estudiantesBusqueda',
            'estudianteId',
            'resumenGeneral',
            'graficaResumenAsistencia',
            'graficaAsistenciaDias',
            'graficaFaltasDias',
            'resumenAlertas',
            'resumenJustificantes',
            'topEstudiantesFaltas',
            'ultimasAlertas',
            'ultimosJustificantes'
        );

    }


    public function exportar_excel_dashboard(Request $request)
    {
        $data = $this->obtenerDatosDashboard($request);

        if (!$data['hayPeriodos']) {
            return response()->json([
                'success' => false,
                'message' => 'No hay periodos disponibles para exportar.'
            ], 422);
        }

        if (!$data['tieneEstudiantesConDatos']) {
            return response()->json([
                'success' => false,
                'message' => 'No hay estudiantes con datos escolares para permitir exportar.'
            ], 422);
        }

        $resumenGeneral       = $data['resumenGeneral'];
        $graficaAsistenciaDias = $data['graficaAsistenciaDias'];
        $graficaFaltasDias    = $data['graficaFaltasDias'];
        $ultimasAlertas       = $data['ultimasAlertas'];
        $ultimosJustificantes = $data['ultimosJustificantes'];
        $topEstudiantesFaltas = $data['topEstudiantesFaltas'];
        $periodo              = $data['periodo'];
        $personal             = $data['personal'];


        // ===================== Textos de filtros activos =====================
        $txtPeriodo = $periodo
            ? $periodo->nombre
            : (
                $data['hayPeriodos']
                    ? 'Todos los periodos'
                    : 'Sin periodos'
            );
        $txtRol        = '—';
        $txtEspecialidad = '—';
        $txtEstatus    = '—';
        $txtEstudiante = $data['nombreEstudianteSeleccionado'] ?: 'Todos';

        if (!$data['esAdmin'] && $data['rolIdSeleccionado']) {
            $rol = $data['rolesConEstudiantes']
                ->firstWhere('id', $data['rolIdSeleccionado']);
            $txtRol = $rol?->nombre ?? '—';
        } elseif ($data['esAdmin']) {
            $txtRol = 'Administrador (todos)';
        } else {
            $txtRol = 'Todos mis estudiantes';
        }

        if ($data['areaIdSeleccionada']) {
            $area = $data['areasEspecialidad']
                ->firstWhere('id', $data['areaIdSeleccionada']);
            $txtEspecialidad = $area?->nombre ?? '—';
        } else {
            $txtEspecialidad = 'Todas';
        }

        if ($data['estatusIdSeleccionado']) {
            $estatus = $data['estatusEscolares']
                ->firstWhere('id', $data['estatusIdSeleccionado']);
            $txtEstatus = $estatus?->nombre ?? '—';
        } else {
            $txtEstatus = 'Todos';
        }


        // ===================== Colores =====================
        $colorPrincipal      = '1B396A';   // azul oscuro
        $colorPrincipalTexto = 'FFFFFF';   // blanco
        $colorSubencabezado  = 'D6E4F0';   // azul muy suave
        $colorFila1          = 'FFFFFF';
        $colorFila2          = 'F4F7FB';   // zebra suave
        $colorBorde          = 'C9D4E5';
        $colorAlertaPend     = 'FFF3CD';
        $colorAlertaAtend    = 'D4EDDA';
        $colorAlertaCerr     = 'F8D7DA';


        // ===================== Helper: estilo encabezado de sección =====================
        $aplicarEstiloEncabezadoPrincipal = function ($sheet, string $rango) use ($colorPrincipal, $colorPrincipalTexto) {
            $sheet->getStyle($rango)->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FF' . $colorPrincipalTexto],
                    'size'  => 11,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF' . $colorPrincipal],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_LEFT,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                ],
            ]);
        };

        $aplicarEstiloEncabezadoColumna = function ($sheet, string $rango) use ($colorSubencabezado, $colorPrincipal, $colorBorde) {
            $sheet->getStyle($rango)->applyFromArray([
                'font' => [
                    'bold'  => true,
                    'color' => ['argb' => 'FF' . $colorPrincipal],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF' . $colorSubencabezado],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF' . $colorBorde],
                    ],
                ],
                'alignment' => [
                    'horizontal' => Alignment::HORIZONTAL_CENTER,
                    'vertical'   => Alignment::VERTICAL_CENTER,
                    'wrapText'   => true,
                ],
            ]);
        };

        $aplicarEstiloFila = function ($sheet, string $rango, bool $par) use ($colorFila1, $colorFila2, $colorBorde) {
            $sheet->getStyle($rango)->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF' . ($par ? $colorFila2 : $colorFila1)],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF' . $colorBorde],
                    ],
                ],
                'alignment' => [
                    'vertical' => Alignment::VERTICAL_CENTER,
                ],
            ]);
        };

        $autosize = function ($sheet, array $columnas) {
            foreach ($columnas as $col) {
                $sheet->getColumnDimension($col)->setAutoSize(true);
            }
        };


        $escribirFiltros = function (
            $sheet, int $filaInicio, string $periodo,
            string $rol, string $especialidad,
            string $estatus, string $estudiante,
            string $generadoPor, string $fechaExport
        ) use ($aplicarEstiloEncabezadoPrincipal, $colorSubencabezado, $colorBorde) {

            $sheet->setCellValue("A{$filaInicio}", 'FILTROS APLICADOS');
            $sheet->mergeCells("A{$filaInicio}:B{$filaInicio}");
            $aplicarEstiloEncabezadoPrincipal($sheet, "A{$filaInicio}:B{$filaInicio}");
            $sheet->getRowDimension($filaInicio)->setRowHeight(20);

            $items = [
                ['Periodo',      $periodo],
                ['Rol',          $rol],
                ['Especialidad', $especialidad],
                ['Estatus',      $estatus],
                ['Estudiante',   $estudiante],
                ['Generado por', $generadoPor],
                ['Fecha export', $fechaExport],
            ];

            $f = $filaInicio + 1;
            foreach ($items as [$etiqueta, $valor]) {
                $sheet->setCellValue("A{$f}", $etiqueta);
                $sheet->setCellValue("B{$f}", $valor);
                $sheet->getStyle("A{$f}")->applyFromArray([
                    'font' => ['bold' => true],
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF' . $colorSubencabezado],
                    ],
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF' . $colorBorde],
                        ],
                    ],
                ]);
                $sheet->getStyle("B{$f}")->applyFromArray([
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color'       => ['argb' => 'FF' . $colorBorde],
                        ],
                    ],
                ]);
                $f++;
            }

            return $f + 1; // fila después del bloque de filtros + 1 espacio
        };

        $generadoPor  = trim($personal->nombre . ' ' . $personal->apellidos);
        $fechaExport  = now('America/Mexico_City')->format('d/m/Y H:i:s');


        // ===================== CREAR LIBRO =====================
        $spreadsheet = new Spreadsheet();
        $spreadsheet->getProperties()
            ->setCreator($generadoPor)
            ->setTitle('Dashboard Personal SAAE')
            ->setSubject($txtPeriodo);


        // ===================== HOJA 1 — RESUMEN GENERAL =====================
        $sh = $spreadsheet->getActiveSheet()->setTitle('Resumen general');

        // Título principal
        $sh->setCellValue('A1', 'REPORTE DASHBOARD - SAAE');
        $sh->mergeCells('A1:B1');
        $aplicarEstiloEncabezadoPrincipal($sh, 'A1:B1');
        $sh->getRowDimension(1)->setRowHeight(24);

        // Bloque de filtros
        $filaPost = $escribirFiltros(
            $sh, 3,
            $txtPeriodo, $txtRol, $txtEspecialidad,
            $txtEstatus, $txtEstudiante,
            $generadoPor, $fechaExport
        );

        // Encabezado de métricas
        $sh->setCellValue("A{$filaPost}", 'MÉTRICAS');
        $sh->mergeCells("A{$filaPost}:B{$filaPost}");
        $aplicarEstiloEncabezadoPrincipal($sh, "A{$filaPost}:B{$filaPost}");
        $sh->getRowDimension($filaPost)->setRowHeight(20);

        $metricas = [
            ['Total estudiantes',   $resumenGeneral['total_estudiantes']],
            ['Días esperados',       $resumenGeneral['total_dias']],
            ['Presentes',           $resumenGeneral['presentes']],
            ['Faltas',              $resumenGeneral['faltas']],
            ['Faltas justificadas', $resumenGeneral['faltas_justificadas']],
            ['No aplica',           $resumenGeneral['no_aplica']],
            ['% Asistencia',        $resumenGeneral['porcentaje'] . '%'],
        ];

        $f = $filaPost + 1;
        foreach ($metricas as $i => [$etiqueta, $valor]) {
            $sh->setCellValue("A{$f}", $etiqueta);
            $sh->setCellValue("B{$f}", $valor);
            $sh->getStyle("A{$f}")->getFont()->setBold(true);
            $aplicarEstiloFila($sh, "A{$f}:B{$f}", $i % 2 === 0);
            $f++;
        }

        $autosize($sh, ['A', 'B']);


        // ===================== HOJA 2 — ASISTENCIA POR DIA =====================
        $sh2 = $spreadsheet->createSheet()->setTitle('Asistencia por día');

        $sh2->setCellValue('A1', 'ASISTENCIA POR DÍA');
        $sh2->mergeCells('A1:D1');
        $aplicarEstiloEncabezadoPrincipal($sh2, 'A1:D1');
        $sh2->getRowDimension(1)->setRowHeight(24);

        $filaPost2 = $escribirFiltros(
            $sh2, 3,
            $txtPeriodo, $txtRol, $txtEspecialidad,
            $txtEstatus, $txtEstudiante,
            $generadoPor, $fechaExport
        );

        $sh2->setCellValue("A{$filaPost2}", 'Fecha');
        $sh2->setCellValue("B{$filaPost2}", 'Presentes');
        $sh2->setCellValue("C{$filaPost2}", 'Justificadas');
        $sh2->setCellValue("D{$filaPost2}", 'Total');
        $aplicarEstiloEncabezadoColumna($sh2, "A{$filaPost2}:D{$filaPost2}");
        $sh2->getRowDimension($filaPost2)->setRowHeight(18);

        $f = $filaPost2 + 1;
        foreach ($graficaAsistenciaDias['labels'] as $idx => $fecha) {
            $estado = $graficaAsistenciaDias['estados'][$idx] ?? [];
            $sh2->setCellValue("A{$f}", $fecha);
            $sh2->setCellValue("B{$f}", $estado['presentes']   ?? 0);
            $sh2->setCellValue("C{$f}", $estado['justificadas'] ?? 0);
            $sh2->setCellValue("D{$f}", $graficaAsistenciaDias['data'][$idx] ?? 0);
            $aplicarEstiloFila($sh2, "A{$f}:D{$f}", ($f % 2 === 0));
            $f++;
        }

        $autosize($sh2, ['A', 'B', 'C', 'D']);


        // ===================== HOJA 3 — FALTAS POR DIA =====================
        $sh3 = $spreadsheet->createSheet()->setTitle('Faltas por día');

        $sh3->setCellValue('A1', 'FALTAS POR DÍA');
        $sh3->mergeCells('A1:B1');
        $aplicarEstiloEncabezadoPrincipal($sh3, 'A1:B1');
        $sh3->getRowDimension(1)->setRowHeight(24);

        $filaPost3 = $escribirFiltros(
            $sh3, 3,
            $txtPeriodo, $txtRol, $txtEspecialidad,
            $txtEstatus, $txtEstudiante,
            $generadoPor, $fechaExport
        );

        $sh3->setCellValue("A{$filaPost3}", 'Fecha');
        $sh3->setCellValue("B{$filaPost3}", 'Total faltas');
        $aplicarEstiloEncabezadoColumna($sh3, "A{$filaPost3}:B{$filaPost3}");
        $sh3->getRowDimension($filaPost3)->setRowHeight(18);

        $f = $filaPost3 + 1;
        foreach ($graficaFaltasDias['labels'] as $idx => $fecha) {
            $sh3->setCellValue("A{$f}", $fecha);
            $sh3->setCellValue("B{$f}", $graficaFaltasDias['data'][$idx] ?? 0);
            $aplicarEstiloFila($sh3, "A{$f}:B{$f}", ($f % 2 === 0));
            $f++;
        }

        $autosize($sh3, ['A', 'B']);


        // ===================== HOJA 4 — ALERTAS =====================
        $sh4 = $spreadsheet->createSheet()->setTitle('Alertas de asistencia');

        $sh4->setCellValue('A1', 'ALERTAS DE ASISTENCIA');
        $sh4->mergeCells('A1:E1');
        $aplicarEstiloEncabezadoPrincipal($sh4, 'A1:E1');
        $sh4->getRowDimension(1)->setRowHeight(24);

        $filaPost4 = $escribirFiltros(
            $sh4, 3,
            $txtPeriodo, $txtRol, $txtEspecialidad,
            $txtEstatus, $txtEstudiante,
            $generadoPor, $fechaExport
        );

        // Mini resumen
        $resumenAlertas = $data['resumenAlertas'];
        $sh4->setCellValue("A{$filaPost4}", 'Pendientes');
        $sh4->setCellValue("B{$filaPost4}", $resumenAlertas['pendientes']);
        $sh4->setCellValue("C{$filaPost4}", 'Atendidas');
        $sh4->setCellValue("D{$filaPost4}", $resumenAlertas['atendidas']);
        $sh4->setCellValue("E{$filaPost4}", 'Cerradas');
        $sh4->setCellValue("F{$filaPost4}", $resumenAlertas['cerradas']);
        $sh4->getStyle("A{$filaPost4}:F{$filaPost4}")->getFont()->setBold(true);
        $filaPost4++;
        $filaPost4++;

        $cols4 = ['A' => 'Estudiante', 'B' => 'No. Control',
                  'C' => 'Tipo alerta', 'D' => 'Estados',
                  'E' => 'Fechas', 'F' => 'Total'];
        foreach ($cols4 as $col => $titulo) {
            $sh4->setCellValue("{$col}{$filaPost4}", $titulo);
        }
        $aplicarEstiloEncabezadoColumna($sh4, "A{$filaPost4}:F{$filaPost4}");
        $sh4->getRowDimension($filaPost4)->setRowHeight(18);

        $f = $filaPost4 + 1;
        foreach ($ultimasAlertas as $alerta) {
            $estadosTexto = collect($alerta['estados'])
                ->map(fn($e) => $e['estado'] . ' (' . $e['total'] . ')')
                ->implode(', ');

            $sh4->setCellValue("A{$f}", $alerta['estudiante']?->nombre_completo ?? '—');
            $sh4->setCellValue("B{$f}", $alerta['estudiante']?->numero_control   ?? '—');
            $sh4->setCellValue("C{$f}", $alerta['tipo_alerta_texto']);
            $sh4->setCellValue("D{$f}", $estadosTexto);
            $sh4->setCellValue("E{$f}", $alerta['fechas']);
            $sh4->setCellValue("F{$f}", $alerta['total']);

            // color por estado predominante
            $hayPend = collect($alerta['estados'])
                ->contains(fn($e) => $e['estado'] === 'PENDIENTE');
            $hayAtend = collect($alerta['estados'])
                ->contains(fn($e) => $e['estado'] === 'ATENDIDA');

            $colorFondo = $hayPend
                ? $colorAlertaPend
                : ($hayAtend ? $colorAlertaAtend : $colorAlertaCerr);

            $sh4->getStyle("A{$f}:F{$f}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF' . $colorFondo],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF' . $colorBorde],
                    ],
                ],
            ]);
            $f++;
        }

        $autosize($sh4, ['A', 'B', 'C', 'D', 'E', 'F']);


        // ===================== HOJA 5 — JUSTIFICANTES =====================
        $sh5 = $spreadsheet->createSheet()->setTitle('Justificantes');

        $sh5->setCellValue('A1', 'JUSTIFICANTES');
        $sh5->mergeCells('A1:G1');
        $aplicarEstiloEncabezadoPrincipal($sh5, 'A1:G1');
        $sh5->getRowDimension(1)->setRowHeight(24);

        $filaPost5 = $escribirFiltros(
            $sh5, 3,
            $txtPeriodo, $txtRol, $txtEspecialidad,
            $txtEstatus, $txtEstudiante,
            $generadoPor, $fechaExport
        );

        // Mini resumen
        $resumenJust = $data['resumenJustificantes'];
        $sh5->setCellValue("A{$filaPost5}", 'Pendientes');
        $sh5->setCellValue("B{$filaPost5}", $resumenJust['pendientes']);
        $sh5->setCellValue("C{$filaPost5}", 'Aprobados');
        $sh5->setCellValue("D{$filaPost5}", $resumenJust['aprobados']);
        $sh5->setCellValue("E{$filaPost5}", 'Rechazados');
        $sh5->setCellValue("F{$filaPost5}", $resumenJust['rechazados']);
        $sh5->getStyle("A{$filaPost5}:F{$filaPost5}")->getFont()->setBold(true);
        $filaPost5++;
        $filaPost5++;

        $cols5 = ['A' => 'Estudiante', 'B' => 'No. Control', 'C' => 'Folio',
                  'D' => 'Motivo', 'E' => 'Estado',
                  'F' => 'Fecha creación', 'G' => 'Fecha actualización'];
        foreach ($cols5 as $col => $titulo) {
            $sh5->setCellValue("{$col}{$filaPost5}", $titulo);
        }
        $aplicarEstiloEncabezadoColumna($sh5, "A{$filaPost5}:G{$filaPost5}");
        $sh5->getRowDimension($filaPost5)->setRowHeight(18);

        $f = $filaPost5 + 1;
        foreach ($ultimosJustificantes as $j) {
            $estado    = strtolower($j->estado ?? '');
            $colorJust = match($estado) {
                'pendiente'  => $colorAlertaPend,
                'aprobado'   => $colorAlertaAtend,
                'rechazado'  => $colorAlertaCerr,
                default      => $colorFila1,
            };

            $sh5->setCellValue("A{$f}", $j->estudiante?->nombre_completo ?? '—');
            $sh5->setCellValue("B{$f}", $j->estudiante?->numero_control   ?? '—');
            $sh5->setCellValue("C{$f}", $j->folio    ?? 'Sin folio');
            $sh5->setCellValue("D{$f}", $j->motivo   ?? '—');
            $sh5->setCellValue("E{$f}", ucfirst($estado));
            $sh5->setCellValue("F{$f}", optional($j->created_at)
                ->timezone('America/Mexico_City')->format('d/m/Y H:i') ?? '—');
            $sh5->setCellValue("G{$f}", optional($j->updated_at)
                ->timezone('America/Mexico_City')->format('d/m/Y H:i') ?? '—');

            $sh5->getStyle("A{$f}:G{$f}")->applyFromArray([
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['argb' => 'FF' . $colorJust],
                ],
                'borders' => [
                    'allBorders' => [
                        'borderStyle' => Border::BORDER_THIN,
                        'color'       => ['argb' => 'FF' . $colorBorde],
                    ],
                ],
            ]);
            $f++;
        }

        $autosize($sh5, ['A', 'B', 'C', 'D', 'E', 'F', 'G']);


        // ===================== HOJA 6 — TOP ESTUDIANTES CON MAS FALTAS =====================
        $sh6 = $spreadsheet->createSheet()->setTitle('Top faltas estudiantes');

        $sh6->setCellValue('A1', 'ESTUDIANTES CON MÁS FALTAS');
        $sh6->mergeCells('A1:C1');
        $aplicarEstiloEncabezadoPrincipal($sh6, 'A1:C1');
        $sh6->getRowDimension(1)->setRowHeight(24);

        $filaPost6 = $escribirFiltros(
            $sh6, 3,
            $txtPeriodo, $txtRol, $txtEspecialidad,
            $txtEstatus, $txtEstudiante,
            $generadoPor, $fechaExport
        );

        $sh6->setCellValue("A{$filaPost6}", 'Estudiante');
        $sh6->setCellValue("B{$filaPost6}", 'Especialidad');
        $sh6->setCellValue("C{$filaPost6}", 'Total faltas');
        $aplicarEstiloEncabezadoColumna($sh6, "A{$filaPost6}:C{$filaPost6}");
        $sh6->getRowDimension($filaPost6)->setRowHeight(18);

        $f = $filaPost6 + 1;
        foreach ($topEstudiantesFaltas as $i => $item) {
            $sh6->setCellValue("A{$f}", $item->estudiante?->nombre_completo ?? '—');
            $sh6->setCellValue("B{$f}",
                optional(
                    $item->estudiante
                        ?->estudiantesConDatosEscolares
                        ?->datoEscolarDeAreaEspecialidad
                )->nombre ?? '—'
            );
            $sh6->setCellValue("C{$f}", $item->total_faltas);

            $aplicarEstiloFila($sh6, "A{$f}:C{$f}", ($i % 2 === 0));

            // Top 1: resaltar en rojo suave
            if ($i === 0) {
                $sh6->getStyle("A{$f}:C{$f}")->applyFromArray([
                    'fill' => [
                        'fillType'   => Fill::FILL_SOLID,
                        'startColor' => ['argb' => 'FF' . $colorAlertaCerr],
                    ],
                    'font' => ['bold' => true],
                ]);
            }

            $f++;
        }

        $autosize($sh6, ['A', 'B', 'C']);


        // ===================== Activar hoja 1 al abrir =====================
        $spreadsheet->setActiveSheetIndex(0);


        // ===================== Descarga =====================
        $nombreArchivo =
            'dashboard_asistencia_' .
            now('America/Mexico_City')->format('Ymd_His') .
            '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma'        => 'public',
        ]);
    }


}