<?php

namespace App\Http\Controllers\Personal\AsistenciaEstudiantes;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PersonalSaae;
use App\Models\RolesPersonalSaae;
use App\Models\EstatusEscolaresEstudiantesSaae;
use App\Models\AreasEspecialidadEstudiantesSaae;
use App\Models\EstudiantesSaae;
use App\Models\EstudianteConPersonalSaae;

use App\Models\ImportacionAsistencia;
use App\Models\AsistenciaDiaria;
use App\Models\Periodo;
use App\Models\RelojChecador;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

//para importacion  
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;


class AsistenciaEstudiantesController extends Controller
{
    
    public function ver_asistencia_reciente()
    {
        $periodos = Periodo::query()
            ->orderByDesc('fecha_inicio')
            ->get(['id', 'nombre']);
        
        $estatusEscolar = EstatusEscolaresEstudiantesSaae::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        $areasEspecialidad = AreasEspecialidadEstudiantesSaae::query()
            ->where('activo', true)
            ->orderBy('nombre')
            ->get(['id', 'nombre']);

        return view('personal.asistencia_estudiantes.asistencia_reciente', compact('periodos','estatusEscolar','areasEspecialidad'));
    }

    public function ver_hitorial_asistencia_estudiante()
    {
        $periodos = Periodo::query()
            ->orderByDesc('fecha_inicio')
            ->get(['id', 'nombre']);

        return view('personal.asistencia_estudiantes.historial_consultas_asistencia', compact('periodos'));
    }



    // ================== METODO PRIVADOS DE APOYO A LOS DEMAS METODOS DE LAS TABLAS ==================
    private function resolverPeriodoId(?int $periodoId): ?int
    {
        if (!empty($periodoId) && $periodoId > 0) {
            return $periodoId;
        }

        return Periodo::query()
            ->where('activo', true)
            ->value('id')
            ?? Periodo::query()
                ->orderByDesc('fecha_inicio')
                ->value('id');
    }

    private function resolverFechaReferencia(?string $fecha = null): string
    {
        if (!empty($fecha)) {
            return Carbon::parse($fecha, 'America/Mexico_City')->toDateString();
        }

        return now('America/Mexico_City')->toDateString();
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


    private function resolverFechaMasRecienteConAsistencia(
        int $personalId,
        int $periodoId,
        bool $esAdmin = false,
        ?string $fecha = null
    ): string
    {
        if (!empty($fecha)) {
            return Carbon::parse($fecha, 'America/Mexico_City')->toDateString();
        }

        if ($esAdmin) {

            $estudiantesIds = EstudiantesSaae::query()
                ->pluck('id');

        } else {

            $estudiantesIds = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('activo', true)
                ->pluck('estudiante_id');
        }

        $fechaMasReciente = AsistenciaDiaria::query()
            ->where('periodo_id', $periodoId)
            ->whereIn('estudiante_id', $estudiantesIds)
            ->max('fecha');

        if ($fechaMasReciente) {
            return Carbon::parse($fechaMasReciente, 'America/Mexico_City')->toDateString();
        }

        return now('America/Mexico_City')->toDateString();
    }




    // ================== TABLA DE ASISTENCIA RECIENTE ==================
    public function tabla_asistencia_reciente(Request $request)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();


        $buscar = trim((string) $request->input('buscar', ''));
        $periodoId = $this->resolverPeriodoId(
            $request->filled('periodo_id') ? (int) $request->input('periodo_id') : null
        );
        $estatus = trim((string) $request->input('estatus', ''));
        $estatusEscolarId = $request->filled('estatus_escolar_id') ? (int) $request->input('estatus_escolar_id') : null;
        $areaId = $request->filled('area_id') ? (int) $request->input('area_id') : null;
        $perPage = (int) $request->input('per_page', 50);
        $page = max((int) $request->input('page', 1), 1);

        if (!in_array($perPage, [20, 50, 100, 200], true)) {
            $perPage = 50;
        }

        if (!$periodoId) {
            return response()->json([
                'data' => [],
                'meta' => [
                    'current_page' => 1,
                    'last_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'from' => 0,
                    'to' => 0,
                    'fecha_referencia' => null,
                    'periodo_id_usado' => null,
                ]
            ]);
        }

        $fechaReferencia = $this->resolverFechaMasRecienteConAsistencia(
            $personalId,
            $periodoId,
            $esAdmin,
            $request->input('fecha')
        );

        if ($esAdmin) {

            $estudiantes = EstudiantesSaae::query()
                ->with([
                    'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
                ])
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
                ->when($areaId, function ($query) use ($areaId) {

                    $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($areaId) {
                        $q->where('especialidad_id', $areaId);
                    });
                })
                ->when($estatusEscolarId, function ($query) use ($estatusEscolarId) {

                    $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($estatusEscolarId) {
                        $q->where('estatus_escolar_id', $estatusEscolarId);
                    });
                })
                ->orderByDesc('created_at')
                ->get();

        } else {

            $estudiantes = EstudianteConPersonalSaae::query()
                ->with([
                    'asignacionConEstudiante.estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
                    'asignacionConRol:id,nombre',
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
                ->when($areaId, function ($query) use ($areaId) {

                    $query->whereHas('asignacionConEstudiante.estudiantesConDatosEscolares', function ($q) use ($areaId) {
                        $q->where('especialidad_id', $areaId);
                    });
                })
                ->when($estatusEscolarId, function ($query) use ($estatusEscolarId) {

                    $query->whereHas('asignacionConEstudiante.estudiantesConDatosEscolares', function ($q) use ($estatusEscolarId) {
                        $q->where('estatus_escolar_id', $estatusEscolarId);
                    });
                })
                ->orderByDesc('created_at')
                ->get();
        }


        $estudiantesIds = $esAdmin
            ? $estudiantes->pluck('id')
            : $estudiantes->pluck('estudiante_id');

        $asistenciasDelDia = AsistenciaDiaria::query()
            ->where('periodo_id', $periodoId)
            ->whereDate('fecha', $fechaReferencia)
            ->whereIn('estudiante_id', $estudiantesIds)
            ->get()
            ->keyBy('estudiante_id');

        $items = $estudiantes->map(function ($item) use ($asistenciasDelDia, $esAdmin) {
            $estudiante = $esAdmin
                ? $item
                : $item->asignacionConEstudiante;

            $datos = $estudiante?->estudiantesConDatosEscolares;

            if (!$estudiante) {
                return null;
            }

            $asistencia = $asistenciasDelDia->get($estudiante->id);

            $estatusAsistencia = $this->resolverEstadoAsistencia($asistencia);

            return [
                'estudiante_id' => $estudiante->id,
                'numero_control' => $estudiante->numero_control,
                'nombre_estudiante' => $this->resolverNombreEstudiante($estudiante),

                // para filtros
                'estatus_bd' => $asistencia?->estatus,
                'justificada' => $asistencia?->justificada ?? false,

                'rol_asignado' => $esAdmin
                    ? 'SIN ASIGNACIÓN'
                    : ($item->asignacionConRol?->nombre ?? '—'),
                'especialidad' => $datos?->datoEscolarDeAreaEspecialidad?->nombre ?? 'Sin datos',

                // para mostrar en pantalla
                'fecha' => $asistencia->getRawOriginal('fecha'),
                'estatus_asistencia' => $estatusAsistencia,
                'fuente' => $asistencia?->fuente ?? '—',
                'primera_entrada' => $asistencia?->getRawOriginal('primera_entrada'),
                'ultima_salida' => $asistencia?->getRawOriginal('ultima_salida'),
                'conteo_marcaciones' => $asistencia?->conteo_marcaciones ?? 0,
            ];
        })
        ->filter()
        ->when($estatus !== '', function (Collection $collection) use ($estatus) {
            return $collection->filter(function ($item) use ($estatus) {

                if ($estatus === 'FALTA') {
                    return $item['estatus_bd'] === 'FALTA'
                        && !$item['justificada'];
                }

                if ($estatus === 'JUSTIFICADA') {
                    return $item['estatus_bd'] === 'FALTA'
                        && $item['justificada'];
                }

                if ($estatus === 'SIN_REGISTRO') {
                    return empty($item['estatus_bd']);
                }

                return $item['estatus_bd'] === $estatus;
            });
        })
        ->values();

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
                'fecha_referencia' => $fechaReferencia,
                'periodo_id_usado' => $periodoId,
            ]
        ]);
    }


    public function resumen_asistencia_reciente(Request $request)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $periodoId = $this->resolverPeriodoId(
            (int) $request->input('periodo_id')
        );

        if (!$periodoId) {
            return response()->json([
                'data' => [
                    'total_asignados' => 0,
                    'presentes' => 0,
                    'faltas_totales' => 0,
                    'faltas' => 0,
                    'faltas_justificadas' => 0,
                    'no_aplica' => 0,
                    'porcentaje_asistencia' => 0,
                ],
                'meta' => [
                    'periodo_id_usado' => null,
                ]
            ]);
        }

        if ($esAdmin) {

            $estudiantesIds = EstudiantesSaae::query()
                ->pluck('id');

        } else {

            $estudiantesIds = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('activo', true)
                ->pluck('estudiante_id')
                ->unique()
                ->values();
        }

        $asistenciasPeriodo = AsistenciaDiaria::query()
            ->where('periodo_id', $periodoId)
            ->whereIn('estudiante_id', $estudiantesIds)
            ->get();

        $presentes = $asistenciasPeriodo
            ->where('estatus', 'PRESENTE')
            ->count();

        $faltasTotales = $asistenciasPeriodo
            ->where('estatus', 'FALTA')
            ->count();

        $faltas = $asistenciasPeriodo
            ->where('estatus', 'FALTA')
            ->where('justificada', false)
            ->count();

        $faltasJustificadas = $asistenciasPeriodo
            ->where('estatus', 'FALTA')
            ->where('justificada', true)
            ->count();

        $noAplica = $asistenciasPeriodo
            ->where('estatus', 'NO_APLICA')
            ->count();

        $totalRegistros = $asistenciasPeriodo->count();

        // NO_APLICA no participa en el cálculo
        $totalEvaluable = $totalRegistros - $noAplica;

        $porcentajeAsistencia = $totalEvaluable > 0
            ? round((($presentes + $faltasJustificadas) / $totalEvaluable) * 100, 2)
            : 0;

        return response()->json([
            'data' => [
                'total_asignados' => $estudiantesIds->count(),

                'presentes' => $presentes,

                'faltas_totales' => $faltasTotales,

                'faltas' => $faltas,

                'faltas_justificadas' => $faltasJustificadas,

                'no_aplica' => $noAplica,

                'porcentaje_asistencia' => $porcentajeAsistencia,
            ],
            'meta' => [
                'periodo_id_usado' => $periodoId,
            ]
        ]);
    }


    public function detalle_asistencia_estudiante(Request $request, int $id)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $periodoId = (int) $request->input('periodo_id');

        if (!$esAdmin) {

            $asignado = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('estudiante_id', $id)
                ->where('activo', true)
                ->exists();

            if (!$asignado) {
                return response()->json([
                    'message' => 'No tienes acceso a este estudiante.'
                ], 403);
            }
        }

        $estudiante = EstudiantesSaae::query()
            ->select('id', 'numero_control', 'nombre', 'apellidos', 'nombre_completo')
            ->findOrFail($id);

        $nombreArmado = trim(($estudiante->nombre ?? '') . ' ' . ($estudiante->apellidos ?? ''));
        $nombreMostrable = $nombreArmado !== '' ? $nombreArmado : ($estudiante->nombre_completo ?? '—');

        $registros = AsistenciaDiaria::query()
            ->where('estudiante_id', $id)
            ->where('periodo_id', $periodoId)
            ->orderByDesc('fecha')
            ->get();

        $detalle = $registros->map(function ($a) {
            return [
                'fecha' => $a->getRawOriginal('fecha'),
                'estatus_asistencia' => $this->resolverEstadoAsistencia($a),
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
                'nombre_estudiante' => $nombreMostrable,
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
                ]
            ]
        ]);
    }


    // EXPORTACION DE ASISTENCIA RECIENTE ----------
    public function exportar_asistencia_reciente_excel(Request $request)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();


        $buscar = trim((string) $request->input('buscar', ''));

        $periodoId = $this->resolverPeriodoId(
            $request->filled('periodo_id')
                ? (int) $request->input('periodo_id') : null
        );

        $estatus = trim((string) $request->input('estatus', ''));

        $areaId = $request->filled('area_id') 
            ? (int) $request->input('area_id') : null;

        $estatusEscolarId = $request->filled('estatus_escolar_id') 
            ? (int) $request->input('estatus_escolar_id') : null;

        if (!$periodoId) {
            abort(422, 'Debes seleccionar un periodo para exportar.');
        }

        $fechaReferencia = $this->resolverFechaMasRecienteConAsistencia(
            $personalId,
            $periodoId,
            $esAdmin,
            $request->input('fecha')
        );

        if ($esAdmin) {

            $estudiantes = EstudiantesSaae::query()
                ->with([
                    'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
                ])
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
                ->when($areaId, function ($query) use ($areaId) {

                    $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($areaId) {
                        $q->where('especialidad_id', $areaId);
                    });
                })
                ->when($estatusEscolarId, function ($query) use ($estatusEscolarId) {

                    $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($estatusEscolarId) {
                        $q->where('estatus_escolar_id', $estatusEscolarId);
                    });
                })
                ->orderByDesc('created_at')
                ->get();

        } else {

            $estudiantes = EstudianteConPersonalSaae::query()
                ->with([
                    'asignacionConEstudiante.estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
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
                ->when($areaId, function ($query) use ($areaId) {

                    $query->whereHas('asignacionConEstudiante.estudiantesConDatosEscolares', function ($q) use ($areaId) {
                        $q->where('especialidad_id', $areaId);
                    });
                })
                ->when($estatusEscolarId, function ($query) use ($estatusEscolarId) {

                    $query->whereHas('asignacionConEstudiante.estudiantesConDatosEscolares', function ($q) use ($estatusEscolarId) {
                        $q->where('estatus_escolar_id', $estatusEscolarId);
                    });
                })
                ->orderByDesc('created_at')
                ->get();
        }

        $estudiantesIds = $esAdmin
            ? $estudiantes->pluck('id')
            : $estudiantes->pluck('estudiante_id');

        $asistenciasDelDia = AsistenciaDiaria::query()
            ->where('periodo_id', $periodoId)
            ->whereDate('fecha', $fechaReferencia)
            ->whereIn('estudiante_id', $estudiantesIds)
            ->get()
            ->keyBy('estudiante_id');

        $items = $estudiantes->map(function ($item) use ($asistenciasDelDia, $esAdmin) {
            $estudiante = $esAdmin
                ? $item
                : $item->asignacionConEstudiante;

            if (!$estudiante) {
                return null;
            }

            $datos = $estudiante?->estudiantesConDatosEscolares;

            $asistencia = $asistenciasDelDia->get($estudiante->id);

            return [
                'estudiante_id' => $estudiante->id,
                'numero_control' => $estudiante->numero_control,
                'nombre_estudiante' => $this->resolverNombreEstudiante($estudiante),
                'especialidad' => $datos?->datoEscolarDeAreaEspecialidad?->nombre ?? 'Sin datos',
                'estatus_reciente' => $this->resolverEstadoAsistencia($asistencia),
                'fuente' => $asistencia?->fuente ?? '—',
                'primera_entrada' => $asistencia?->getRawOriginal('primera_entrada') ?? '—',
                'ultima_salida' => $asistencia?->getRawOriginal('ultima_salida') ?? '—',
                'conteo_marcaciones' => $asistencia?->conteo_marcaciones ?? 0,
            ];
        })
        ->filter()
        ->when($estatus !== '', function ($collection) use ($estatus) {
            return $collection->filter(fn ($item) => $item['estatus_reciente'] === $estatus);
        })
        ->values();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Asistencia reciente');

        $sheet->fromArray([
            ['Fecha de referencia', $fechaReferencia],
            [],
            ['ID estudiante', 'Número de control', 'Nombre del estudiante', 'Especialidad', 'Estatus', 'Fuente', 'Primera entrada', 'Última salida', 'Marcaciones'],
        ], null, 'A1');

        $fila = 4;
        foreach ($items as $item) {
            $sheet->setCellValue("A{$fila}", $item['estudiante_id']);
            $sheet->setCellValue("B{$fila}", $item['numero_control']);
            $sheet->setCellValue("C{$fila}", $item['nombre_estudiante']);
            $sheet->setCellValue("D{$fila}", $item['especialidad']);
            $sheet->setCellValue("E{$fila}", $item['estatus_reciente']);
            $sheet->setCellValue("F{$fila}", $item['fuente']);
            $sheet->setCellValue("G{$fila}", $item['primera_entrada']);
            $sheet->setCellValue("H{$fila}", $item['ultima_salida']);
            $sheet->setCellValue("I{$fila}", $item['conteo_marcaciones']);
            $fila++;
        }

        foreach (range('A', 'I') as $columna) {
            $sheet->getColumnDimension($columna)->setAutoSize(true);
        }


        // ===================== ESTILOS =====================
        $colorPrincipal = '1B396A';
        $colorSecundarioSuave = 'EAF0F8';
        $colorBorde = 'C9D4E5';
        $colorTextoOscuro = '1F2937';

        // Bloque superior
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFont()->getColor()->setARGB($colorPrincipal);
        $sheet->getStyle('A1:B1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorSecundarioSuave);

        // Encabezados de tabla
        $sheet->getStyle('A3:I3')->getFont()->setBold(true);
        $sheet->getStyle('A3:I3')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle('A3:I3')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorPrincipal);

        $sheet->getStyle('A3:I3')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension(3)->setRowHeight(24);

        $ultimaFila = max($fila - 1, 3);

        // Bordes de toda la tabla
        $sheet->getStyle("A3:I{$ultimaFila}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        // Alineaciones de datos
        $sheet->getStyle("A4:A{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("E4:E{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("I4:I{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Texto general
        $sheet->getStyle("A4:I{$ultimaFila}")->getFont()->getColor()->setARGB($colorTextoOscuro);

        // Auto filtro
        $sheet->setAutoFilter("A3:I{$ultimaFila}");

        // Congelar encabezado
        $sheet->freezePane('A4');
        // ===================================================


        $nombreArchivo = 'asistencia_reciente_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }


    // EXPORTACION DEL HISTORIAL COMPLETO DE ASISTENCIA RECIENTE ----------
    public function exportar_historial_completo_asistencia_excel(Request $request)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();


        $buscar = trim((string) $request->input('buscar', ''));

        $periodoId = $this->resolverPeriodoId(
            $request->filled('periodo_id')
                ? (int) $request->input('periodo_id') : null
        );

        $areaId = $request->filled('area_id') 
            ? (int) $request->input('area_id') : null;

        $estatusEscolarId = $request->filled('estatus_escolar_id') 
            ? (int) $request->input('estatus_escolar_id') : null;

        if (!$periodoId) {
            abort(422, 'Debes seleccionar un periodo para exportar.');
        }

        if ($esAdmin) {

            $estudiantes = EstudiantesSaae::query()
                ->with([
                    'estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
                ])
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
                ->when($areaId, function ($query) use ($areaId) {

                    $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($areaId) {
                        $q->where('especialidad_id', $areaId);
                    });
                })
                ->when($estatusEscolarId, function ($query) use ($estatusEscolarId) {

                    $query->whereHas('estudiantesConDatosEscolares', function ($q) use ($estatusEscolarId) {
                        $q->where('estatus_escolar_id', $estatusEscolarId);
                    });
                })
                ->orderByDesc('created_at')
                ->get();

        } else {

            $estudiantes = EstudianteConPersonalSaae::query()
                ->with([
                    'asignacionConEstudiante.estudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad:id,nombre',
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
                ->when($areaId, function ($query) use ($areaId) {

                    $query->whereHas('asignacionConEstudiante.estudiantesConDatosEscolares', function ($q) use ($areaId) {
                        $q->where('especialidad_id', $areaId);
                    });
                })
                ->when($estatusEscolarId, function ($query) use ($estatusEscolarId) {

                    $query->whereHas('asignacionConEstudiante.estudiantesConDatosEscolares', function ($q) use ($estatusEscolarId) {
                        $q->where('estatus_escolar_id', $estatusEscolarId);
                    });
                })
                ->orderByDesc('created_at')
                ->get();
        }

        $estudiantesIds = $esAdmin
            ? $estudiantes->pluck('id')
            : $estudiantes->pluck('estudiante_id');

        $asistenciasPeriodo = AsistenciaDiaria::query()
            ->where('periodo_id', $periodoId)
            ->whereIn('estudiante_id', $estudiantesIds)
            ->get()
            ->groupBy('estudiante_id');

        $items = $estudiantes->map(function ($item) use ($asistenciasPeriodo, $esAdmin) {

            $estudiante = $esAdmin
                ? $item
                : $item->asignacionConEstudiante;

            if (!$estudiante) {
                return null;
            }

            $datos = $estudiante->estudiantesConDatosEscolares;

            $registros = $asistenciasPeriodo->get(
                $estudiante->id,
                collect()
            );

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

            $totalEvaluable = $total - $noAplica;

            $porcentajeAsistencia = $totalEvaluable > 0
                ? round(
                    (($presentes + $faltasJustificadas) / $totalEvaluable) * 100,
                    2
                )
                : 0;

            return [
                'estudiante_id' => $estudiante->id,
                'numero_control' => $estudiante->numero_control,
                'nombre_estudiante' => $this->resolverNombreEstudiante($estudiante),
                'especialidad' => $datos?->datoEscolarDeAreaEspecialidad?->nombre ?? 'Sin datos',

                'presentes' => $presentes,
                'faltas' => $faltas,
                'faltas_justificadas' => $faltasJustificadas,
                'no_aplica' => $noAplica,
                'porcentaje_asistencia' => $porcentajeAsistencia,
            ];
        })
        ->filter()
        ->values();

        $periodo = Periodo::query()
            ->select('id', 'nombre')
            ->findOrFail($periodoId);


        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Historial de asistencia');

        $sheet->fromArray([
            ['Periodo', $periodo->nombre],
            ['Fecha de exportación', now('America/Mexico_City')->format('Y-m-d H:i:s')],
            [],
            [
                'ID estudiante',
                'Número de control',
                'Nombre del estudiante',
                'Especialidad',
                'Presentes',
                'Faltas',
                'Faltas justificadas',
                'No aplica',
                '% Asistencia'
            ],
        ], null, 'A1');

        $fila = 5;
        foreach ($items as $item) {
            $sheet->setCellValue("A{$fila}", $item['estudiante_id']);
            $sheet->setCellValue("B{$fila}", $item['numero_control']);
            $sheet->setCellValue("C{$fila}", $item['nombre_estudiante']);
            $sheet->setCellValue("D{$fila}", $item['especialidad']);
            $sheet->setCellValue("E{$fila}", $item['presentes']);
            $sheet->setCellValue("F{$fila}", $item['faltas']);
            $sheet->setCellValue("G{$fila}", $item['faltas_justificadas']);
            $sheet->setCellValue("H{$fila}", $item['no_aplica']);
            $sheet->setCellValue("I{$fila}", $item['porcentaje_asistencia'] . '%');
            $fila++;
        }

        foreach (range('A', 'I') as $columna) {
            $sheet->getColumnDimension($columna)->setAutoSize(true);
        }


        // ===================== ESTILOS =====================
        $colorPrincipal = '1B396A';
        $colorSecundarioSuave = 'EAF0F8';
        $colorBorde = 'C9D4E5';
        $colorTextoOscuro = '1F2937';

        // Bloque superior
        $sheet->getStyle('A1:B1')->getFont()->setBold(true);
        $sheet->getStyle('A1:B1')->getFont()->getColor()->setARGB($colorPrincipal);
        $sheet->getStyle('A1:B1')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorSecundarioSuave);

        // Encabezados de tabla
        $sheet->getStyle('A4:I4')->getFont()->setBold(true);
        $sheet->getStyle('A4:I4')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle('A4:I4')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorPrincipal);

        $sheet->getStyle('A4:I4')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension(4)->setRowHeight(24);

        $ultimaFila = max($fila - 1, 3);

        // Bordes de toda la tabla
        $sheet->getStyle("A4:I{$ultimaFila}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        // Alineaciones de datos
        $sheet->getStyle("A4:A{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("E4:E{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("I4:I{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Texto general
        $sheet->getStyle("A5:I{$ultimaFila}")->getFont()->getColor()->setARGB($colorTextoOscuro);

        // Auto filtro
        $sheet->setAutoFilter("A4:I{$ultimaFila}");

        // Congelar encabezado
        $sheet->freezePane('A5');
        // ===================================================


        $nombreArchivo = 'historial_completo_asistencia_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);
    }




    // ================== TABLA DE HISTORIAL DE ASISTENCIA ==================
    public function listado_estudiantes_asignados_tabla_historial_asistencia()
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        if ($esAdmin) {

            $items = EstudiantesSaae::query()
                ->select('id', 'numero_control', 'nombre', 'apellidos', 'nombre_completo')
                ->orderBy('numero_control')
                ->get()
                ->map(function ($estudiante) {

                    return [
                        'id' => $estudiante->id,
                        'numero_control' => $estudiante->numero_control,
                        'nombre_estudiante' => $this->resolverNombreEstudiante($estudiante),
                    ];
                })
                ->filter(fn ($item) => !empty($item['id']))
                ->unique('id')
                ->values();

        } else {

            $items = EstudianteConPersonalSaae::query()
                ->with([
                    'asignacionConEstudiante:id,numero_control,nombre,apellidos,nombre_completo'
                ])
                ->where('personal_id', $personalId)
                ->where('activo', true)
                ->get()
                ->map(function ($asignacion) {

                    $estudiante = $asignacion->asignacionConEstudiante;

                    return [
                        'id' => $estudiante?->id,
                        'numero_control' => $estudiante?->numero_control,
                        'nombre_estudiante' => $this->resolverNombreEstudiante($estudiante),
                    ];
                })
                ->filter(fn ($item) => !empty($item['id']))
                ->unique('id')
                ->sortBy('numero_control')
                ->values();
        }

        return response()->json([
            'data' => $items
        ]);
    }


    public function detalle_tabla_historial_asistencia_estudiante(Request $request, int $id)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $periodoId = $this->resolverPeriodoId(
            (int) $request->input('periodo_id')
        );

        if (!$periodoId) {
            return response()->json([
                'message' => 'No se encontró un periodo válido para consultar.'
            ], 422);
        }

        if (!$esAdmin) {

            $asignado = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('estudiante_id', $id)
                ->where('activo', true)
                ->exists();

            if (!$asignado) {
                return response()->json([
                    'message' => 'No tienes acceso a este estudiante.'
                ], 403);
            }
        }

        $estudiante = EstudiantesSaae::with([
            'asistenciasDiarias' => function ($q) use ($periodoId) {
                $q->where('periodo_id', $periodoId)
                    ->orderBy('fecha');
            }
        ])->findOrFail($id);

        $detalle = $estudiante->asistenciasDiarias->map(function ($a) {

            return [
                'fecha' => $a->getRawOriginal('fecha'),
                'estatus_asistencia' => $this->resolverEstadoAsistencia($a),
                'fuente' => $a->fuente ?? '—',
                'primera_entrada' => $a->getRawOriginal('primera_entrada'),
                'ultima_salida' => $a->getRawOriginal('ultima_salida'),
                'conteo_marcaciones' => $a->conteo_marcaciones ?? 0,
            ];
        })->values();

        $total = $estudiante->asistenciasDiarias->count();

        $presentes = $estudiante->asistenciasDiarias
            ->where('estatus', 'PRESENTE')
            ->count();

        $faltas = $estudiante->asistenciasDiarias
            ->where('estatus', 'FALTA')
            ->where('justificada', false)
            ->count();
        
        $faltasJustificadas = $estudiante->asistenciasDiarias
            ->where('estatus', 'FALTA')
            ->where('justificada', true)
            ->count();
        
        $noAplica = $estudiante->asistenciasDiarias
            ->where('estatus', 'NO_APLICA')
            ->count();
        
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
                ]
            ]
        ]);
    }


    // EXPORTACION ----------
        public function exportar_historial_asistencia_excel(Request $request)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $estudianteId = (int) $request->input('estudiante_id');
        $periodoId = (int) $request->input('periodo_id');

        if (!$estudianteId || !$periodoId) {
            abort(422, 'Debes seleccionar un estudiante y un periodo para exportar.');
        }

        if (!$esAdmin) {

            $asignado = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('estudiante_id', $estudianteId)
                ->where('activo', true)
                ->exists();

            if (!$asignado) {
                abort(403, 'No tienes acceso a este estudiante.');
            }
        }

        $estudiante = EstudiantesSaae::with([
            'asistenciasDiarias' => function ($q) use ($periodoId) {
                $q->where('periodo_id', $periodoId)
                ->orderBy('fecha');
            }
        ])->findOrFail($estudianteId);

        $detalle = $estudiante->asistenciasDiarias->map(function ($item) {
            return [
                'fecha' => (string) $item->fecha,
                'estatus' => $this->resolverEstadoAsistencia($item),
                'fuente' => $item->fuente ?? '—',
                'primera_entrada' => $item->getRawOriginal('primera_entrada') ?? '—',
                'ultima_salida' => $item->getRawOriginal('ultima_salida') ?? '—',
                'conteo_marcaciones' => $item->conteo_marcaciones ?? 0,
            ];
        });

        $totalRegistros = $detalle->count();

        $presentes = $detalle->where('estatus', 'PRESENTE')->count();

        $faltas = $estudiante->asistenciasDiarias
            ->where('estatus', 'FALTA')
            ->where('justificada', false)
            ->count();

        $faltasJustificadas = $estudiante->asistenciasDiarias
            ->where('estatus', 'FALTA')
            ->where('justificada', true)
            ->count();

        $noAplica = $detalle->where('estatus', 'NO APLICA')->count();

        // para el porcentaje de asistencia, donde los que NO cuentan son: FALTAS, NO_APLICA y  ... 
        $totalEvaluable = $totalRegistros - $noAplica;

        //cuenta PRESENTES + FALTAS JUSTIFICADAS
        $porcentaje = $totalEvaluable > 0
            ? round((($presentes + $faltasJustificadas) / $totalEvaluable) * 100, 2)
            : 0;

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Historial asistencia');

        $sheet->fromArray([
            ['Número de control', $estudiante->numero_control],
            ['Nombre del estudiante', $this->resolverNombreEstudiante($estudiante)],
            ['Total registros', $totalRegistros],
            ['Presentes', $presentes],
            ['Faltas', $faltas],
            ['Faltas justificadas', $faltasJustificadas],
            ['No aplica', $noAplica],
            ['% asistencia', $porcentaje . '%'],
            [],
            ['Fecha', 'Estatus', 'Fuente', 'Primera entrada', 'Última salida', 'Marcaciones'],
        ], null, 'A1');

        $fila = 11;
        foreach ($detalle as $item) {
            $sheet->setCellValue("A{$fila}", $item['fecha']);
            $sheet->setCellValue("B{$fila}", $item['estatus']);
            $sheet->setCellValue("C{$fila}", $item['fuente']);
            $sheet->setCellValue("D{$fila}", $item['primera_entrada']);
            $sheet->setCellValue("E{$fila}", $item['ultima_salida']);
            $sheet->setCellValue("F{$fila}", $item['conteo_marcaciones']);
            $fila++;
        }

        foreach (range('A', 'F') as $columna) {
            $sheet->getColumnDimension($columna)->setAutoSize(true);
        }


        // ===================== ESTILOS =====================
        $colorPrincipal = '1B396A';
        $colorSecundarioSuave = 'EAF0F8';
        $colorBorde = 'C9D4E5';
        $colorTextoOscuro = '1F2937';

        // Bloque resumen superior
        $sheet->getStyle('A1:B8')->getFont()->setBold(true);
        $sheet->getStyle('A1:B8')->getFont()->getColor()->setARGB($colorPrincipal);
        $sheet->getStyle('A1:B8')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorSecundarioSuave);

        $sheet->getStyle('A1:B8')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        // Encabezados de tabla
        $sheet->getStyle('A10:F10')->getFont()->setBold(true);
        $sheet->getStyle('A10:F10')->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle('A10:F10')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorPrincipal);

        $sheet->getStyle('A10:F10')->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension(8)->setRowHeight(24);

        $ultimaFila = max($fila - 1, 11);

        // Bordes tabla
        $sheet->getStyle("A10:F{$ultimaFila}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        // Alineaciones
        $sheet->getStyle("A10:A{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("B10:B{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("F10:F{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Texto general
        $sheet->getStyle("A11:F{$ultimaFila}")->getFont()->getColor()->setARGB($colorTextoOscuro);

        // Auto filtro
        $sheet->setAutoFilter("A10:F{$ultimaFila}");

        // Congelar encabezado
        $sheet->freezePane('A11');
        // ===================================================


        $nombreArchivo = 'historial_asistencia_' . $estudiante->numero_control . '_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ]);

    }


}