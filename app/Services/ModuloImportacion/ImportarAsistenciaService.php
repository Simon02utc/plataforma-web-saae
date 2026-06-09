<?php

namespace App\Services\ModuloImportacion;

use App\Models\EstudiantesSaae;
use App\Models\ImportacionAsistencia;
use App\Models\Periodo;
use App\Models\RelojChecador;
use App\Services\ModuloImportacion\ParsersAsistencia\AsistenciaParserResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

//para las alertas
use App\Services\Alertas\GenerarAlertasCriticasAsistenciaService;

class ImportarAsistenciaService
{   

    public function __construct(
        private AsistenciaParserResolver $parserResolver
    ) {}

    //En vez de insertar 50,000 filas en un solo insert (pesado), las mete de 1000 en 1000, para mejorar el rendimeinto y evitar limites del motor
    private int $chunkSize = 1000;///chunkSize = tamaño de lote


    public function importar(
        string $rutaFisicaArchivo,
        string $rutaStorageArchivo,
        string $tipoImportacion,
        int $relojId,
        ?int $periodoId = null,
        ?int $importadoPor = null,
        ?string $notas = null
    ): array {

        $tipoImportacion = strtoupper(trim($tipoImportacion));

        $reloj = RelojChecador::with('parser')->findOrFail($relojId);
        $periodoSeleccionado = $periodoId ? Periodo::findOrFail($periodoId) : null;

        $parser = $this->parserResolver->resolver($reloj);

        $parsed = $parser->parsear(
            $rutaFisicaArchivo,
            $tipoImportacion,
            $periodoSeleccionado
        );

        $parserClave = $reloj->parser?->clave;
        if (!$parserClave) {
            throw new \RuntimeException("El reloj '{$reloj->nombre}' no tiene un parser asociado.");
        }

        $hojasDetectadas = $parsed['hojas_detectadas'] ?? [];
        $advertencias = $parsed['advertencias'] ?? []; //avisos que no cancelan la importacion
        $inicio = $parsed['inicio'] ?? null;
        $fin = $parsed['fin'] ?? null;
        $turnos = $parsed['turnos'] ?? [];
        $marcacionesCrudas = $parsed['marcaciones_crudas'] ?? [];
        $totalDiasEsperados = $parsed['total_dias_esperados'] ?? 0;

        if (!$inicio || !$fin) {
            throw new \RuntimeException('El parser no devolvió el rango de fechas esperado.');
        }


        $controles = [];

        foreach ($turnos as $t) {
            if (($t['numero_control'] ?? '') !== '') {
                $controles[$t['numero_control']] = true;
            }
        }

        foreach ($marcacionesCrudas as $m) {
            if (($m['numero_control'] ?? '') !== '') {
                $controles[$m['numero_control']] = true;
            }
        }

        $controles = array_keys($controles);

        return DB::transaction(function () use (
            $rutaFisicaArchivo,
            $rutaStorageArchivo,
            $tipoImportacion,
            $importadoPor,
            $notas,
            $reloj,
            $periodoSeleccionado,
            $parserClave,
            $hojasDetectadas,
            $advertencias,
            $inicio,
            $fin,
            $controles,
            $turnos,
            $marcacionesCrudas,
            $totalDiasEsperados
        ) {
            $now = now();   

            if (in_array($tipoImportacion, ['COMPLETA', 'SOLO_TURNOS'], true)) {
                $periodo = Periodo::firstOrCreate(
                    [
                        'fecha_inicio' => $inicio->toDateString(),
                        'fecha_fin'    => $fin->toDateString(),
                    ],
                    [
                        'nombre' => $inicio->toDateString() . ' a ' . $fin->toDateString(),
                        'activo' => false,
                    ]
                );
            } elseif ($tipoImportacion === 'SOLO_ASISTENCIA') {
                if (!$periodoSeleccionado) {
                    throw new \RuntimeException('Debes seleccionar un periodo para este tipo de importación.');
                }

                $periodo = $periodoSeleccionado;
            } else {
                throw new \RuntimeException('Tipo de importación no soportado.');
            }

            $hash = hash_file('sha256', $rutaFisicaArchivo);

            try {
                $importacion = ImportacionAsistencia::create([
                    'reloj_checador_id'      => $reloj->id,
                    'periodo_id'             => $periodo->id,
                    'archivo_nombre'         => basename($rutaFisicaArchivo),
                    'archivo_ruta'           => $rutaStorageArchivo,
                    'archivo_hash'           => $hash,
                    'tipo_importacion'       => $tipoImportacion,
                    'parser_clave'           => $parserClave,
                    'hojas_detectadas'       => $hojasDetectadas,
                    'importado_por'          => $importadoPor,
                    'importado_en'           => $now,
                    'estado'                 => 'EXITOSA',
                    'advertencias'           => empty($advertencias) ? null : $advertencias,
                    'resultados_importacion' => null,
                    'notas'                  => $notas ? trim($notas) : null,
                ]);
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) === 1062) {
                    return [
                        'ok'      => false,
                        'mensaje' => "Este archivo ya fue importado para ese periodo, reloj y tipo ({$tipoImportacion}).",
                    ];
                }

                throw $e;
            }


            // ==================== A) ESTUDIANTES EXISTENTES ====================
            $mapControlToId = empty($controles)
                ? []
                : EstudiantesSaae::whereIn('numero_control', $controles)
                    ->pluck('id', 'numero_control')
                    ->all();

            if (empty($mapControlToId)) {
                throw new \RuntimeException(
                    'Los estudiantes con número de control encontrados en el archivo, no está registrados en SAAE.'
                );
            }

            $controlesNoEncontrados = array_values(
                array_diff(
                    $controles,
                    array_keys($mapControlToId)
                )
            );

            if (!empty($controlesNoEncontrados)) {

                sort($controlesNoEncontrados); // ordenar los numeros de control

                $totalControlesNoEncontrados = count($controlesNoEncontrados);

                $ejemplos = array_slice($controlesNoEncontrados, 0, 50); //mostrar maximo 50 elementos

                $restantes = $totalControlesNoEncontrados - count($ejemplos);

                $advertencias[] =
                    "Se ignoraron {$totalControlesNoEncontrados} estudiante(s) que no se encuentran registrado(s). "
                    . "Números de control: " . implode(', ', $ejemplos)
                    . ($restantes > 0 ? " y {$restantes} más." : '');
            }


            // ==================== B) PERIODO_ESTUDIANTES y PERIODO_FECHAS (solo COMPLETA Y SOLO_TURNOS) ====================
            if (in_array($tipoImportacion, ['COMPLETA', 'SOLO_TURNOS'], true)) {
                $periodoEstRows = [];

                foreach ($turnos as $t) {
                    $nc = $t['numero_control'];
                    $estId = $mapControlToId[$nc] ?? null;

                    if (!$estId) {
                        continue;
                    }

                    $periodoEstRows[] = [
                        'periodo_id'    => $periodo->id,
                        'estudiante_id' => $estId,
                        'activo'        => true,
                        'created_at'    => $now,
                        'updated_at'    => $now,
                    ];
                }

                if (!empty($periodoEstRows)) {
                    $this->upsertChunk(
                        'periodo_estudiantes',
                        $periodoEstRows,
                        ['periodo_id', 'estudiante_id'],
                        ['activo', 'updated_at']
                    );
                }

                $fechasClase = [];
                foreach ($turnos as $t) {
                    foreach (($t['fechas_esperadas'] ?? []) as $fecha) {
                        $fechasClase[$fecha] = true;
                    }
                }

                $periodoFechaRows = [];
                foreach (array_keys($fechasClase) as $fecha) {
                    $periodoFechaRows[] = [
                        'periodo_id'     => $periodo->id,
                        'fecha'          => $fecha,
                        'es_clase'       => true,
                        'tipo_dia'       => 'CLASE',
                        'origen'         => 'IMPORTADO',
                        'observaciones'  => null,
                        'created_at'     => $now,
                        'updated_at'     => $now,
                    ];
                }

                if (!empty($periodoFechaRows)) {
                    $this->upsertChunk(
                        'periodo_fechas',
                        $periodoFechaRows,
                        ['periodo_id', 'fecha'],
                        ['es_clase', 'tipo_dia', 'origen', 'observaciones', 'updated_at']
                    );
                }
            }


            // ==================== C) RELOJ_INSCRIPCIONES ====================
            $mapRelojUsuarioToEstId = [];

            if (in_array($tipoImportacion, ['COMPLETA', 'SOLO_TURNOS'], true)) {
                $insRows = [];

                foreach ($turnos as $t) {
                    $nc  = $t['numero_control'];
                    $rid = $t['reloj_usuario_id'];

                    if ($nc === '') {
                        continue;
                    }

                    $estId = $mapControlToId[$nc] ?? null;
                    if (!$estId) {
                        continue;
                    }

                    $insRows[] = [
                        'reloj_checador_id' => $reloj->id,
                        'reloj_usuario_id'  => $rid !== '' ? $rid : null,
                        'estudiante_id'     => $estId,
                        'activo'            => true,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];

                    // Solo construir el mapa si el reloj si tiene su propio ID
                    if ($rid !== '') {
                        $mapRelojUsuarioToEstId[$rid] = $estId;
                    }
                }

                if (!empty($insRows)) {
                    $this->upsertChunk(
                        'reloj_inscripciones',
                        $insRows,
                        ['reloj_checador_id', 'estudiante_id'],
                        ['reloj_usuario_id', 'activo', 'updated_at']
                    );
                }
            } else {
                $mapRelojUsuarioToEstId = DB::table('reloj_inscripciones')
                    ->where('reloj_checador_id', $reloj->id)
                    ->where('activo', true)
                    ->whereNotNull('reloj_usuario_id')
                    ->pluck('estudiante_id', 'reloj_usuario_id')
                    ->all();
            }


            // ==================== D) ASISTENCIA_DIARIA (sembrar base) ====================
            $adRows = [];

            if (in_array($tipoImportacion, ['COMPLETA', 'SOLO_TURNOS'], true)) {
                foreach ($turnos as $t) {
                    $nc = $t['numero_control'];
                    $estId = $mapControlToId[$nc] ?? null;

                    if (!$estId) {
                        continue;
                    }

                    foreach (($t['fechas_esperadas'] ?? []) as $fecha) {
                        $adRows[] = [
                            'estudiante_id'      => $estId,
                            'periodo_id'         => $periodo->id,
                            'reloj_checador_id'  => $reloj->id,
                            'fecha'              => $fecha,
                            'esperado'           => true,
                            'estatus'            => 'FALTA',
                            'fuente'             => 'RELOJ',
                            'importacion_id'     => $importacion->id,
                            'conteo_marcaciones' => 0,
                            'primera_entrada'    => null,
                            'ultima_salida'      => null,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ];
                    }
                }
            } else {
                $estudiantesRelojPeriodoIds = DB::table('reloj_inscripciones as ri')
                    ->join('periodo_estudiantes as pe', function ($join) use ($periodo) {
                        $join->on('pe.estudiante_id', '=', 'ri.estudiante_id')
                            ->where('pe.periodo_id', '=', $periodo->id)
                            ->where('pe.activo', '=', true);
                    })
                    ->where('ri.reloj_checador_id', $reloj->id)
                    ->where('ri.activo', true)
                    ->distinct()
                    ->pluck('ri.estudiante_id')
                    ->all();

                $fechasClase = DB::table('periodo_fechas')
                    ->where('periodo_id', $periodo->id)
                    ->where('es_clase', true)
                    ->orderBy('fecha')
                    ->pluck('fecha')
                    ->all();

                if (empty($estudiantesRelojPeriodoIds)) {
                    throw new \RuntimeException(
                        'El reloj seleccionado no tiene estudiantes activos inscritos para ese periodo. ' .
                        'Primero importa SOLO TURNOS o COMPLETA de ese reloj o registra sus inscripciones.'
                    );
                }

                if (empty($fechasClase)) {
                    throw new \RuntimeException(
                        'El periodo seleccionado no tiene días de clase registrados en la tabla de periodo_fechas.'
                    );
                }

                foreach ($estudiantesRelojPeriodoIds as $estId) {
                    foreach ($fechasClase as $fecha) {
                        $adRows[] = [
                            'estudiante_id'      => $estId,
                            'periodo_id'         => $periodo->id,
                            'reloj_checador_id'  => $reloj->id,
                            'fecha'              => (string) $fecha,
                            'esperado'           => true,
                            'estatus'            => 'FALTA',
                            'fuente'             => 'RELOJ',
                            'importacion_id'     => $importacion->id,
                            'conteo_marcaciones' => 0,
                            'primera_entrada'    => null,
                            'ultima_salida'      => null,
                            'created_at'         => $now,
                            'updated_at'         => $now,
                        ];
                    }
                }

                $totalDiasEsperados = count($adRows);
            }

            if (!empty($adRows)) {
                $this->upsertChunk(
                    'asistencia_diaria',
                    $adRows,
                    ['estudiante_id', 'periodo_id', 'reloj_checador_id', 'fecha'],
                    [
                        'esperado',
                        'estatus',
                        'fuente',
                        'importacion_id',
                        'conteo_marcaciones',
                        'primera_entrada',
                        'ultima_salida',
                        'updated_at'
                    ]
                );
            }


            // ==================== E) MARCACIONES_ASISTENCIA ====================
            $marRows = [];

            foreach ($marcacionesCrudas as $m) {
                $estId = null;

                if (
                    ($m['reloj_usuario_id'] ?? '') !== '' &&
                    isset($mapRelojUsuarioToEstId[$m['reloj_usuario_id']])
                ) {
                    $estId = $mapRelojUsuarioToEstId[$m['reloj_usuario_id']];
                } else {
                    $nc = $m['numero_control'] ?? '';
                    $estId = $mapControlToId[$nc] ?? null;
                }

                if (!$estId) {
                    continue;
                }

                $marRows[] = [
                    'estudiante_id'     => $estId,
                    'reloj_checador_id' => $reloj->id,
                    'importacion_id'    => $importacion->id,
                    'ocurrio_en'        => $m['ocurrio_en'],
                    'celda_cruda'       => $m['celda_cruda'],
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];
            }

            $marcacionesInsertadas = $this->insertOrIgnoreChunk('marcaciones_asistencia', $marRows);


            // E.1) ADVERTIR MARCACIONES SIN DIA SEMBRADO EN asistencia_diaria
            $resumenMarcacionesSinSembrado = DB::table('marcaciones_asistencia as m')
                ->leftJoin('asistencia_diaria as a', function ($join) use ($periodo) {
                    $join->on('a.estudiante_id', '=', 'm.estudiante_id')
                        ->on('a.reloj_checador_id', '=', 'm.reloj_checador_id')
                        ->where('a.periodo_id', '=', $periodo->id)
                        ->whereRaw('a.fecha = DATE(m.ocurrio_en)');
                })
                ->where('m.importacion_id', $importacion->id)
                ->where(function ($q) {
                    $q->whereNull('a.estudiante_id')
                        ->orWhere('a.esperado', false);
                })
                ->selectRaw('COUNT(*) AS total_marcaciones')
                ->selectRaw('COUNT(DISTINCT m.estudiante_id) AS total_estudiantes')
                ->selectRaw('COUNT(DISTINCT CONCAT(m.estudiante_id, "|", DATE(m.ocurrio_en))) AS total_dias')
                ->first();

            $totalMarcacionesSinSembrado = (int) ($resumenMarcacionesSinSembrado->total_marcaciones ?? 0);
            $totalEstudiantesSinSembrado = (int) ($resumenMarcacionesSinSembrado->total_estudiantes ?? 0);
            $totalDiasSinSembrado = (int) ($resumenMarcacionesSinSembrado->total_dias ?? 0);

            if ($totalMarcacionesSinSembrado > 0) {
                $advertencias[] =
                    "Se detectaron {$totalMarcacionesSinSembrado} marcaciones de " .
                    "{$totalEstudiantesSinSembrado} estudiante(s) en {$totalDiasSinSembrado} día(s) " .
                    "que no encontraron registro esperado en asistencia_diaria para el periodo. " .
                    "Revisa si faltan turnos, si hubo filas omitidas en \"Reporte de Turnos\" " .
                    "o si existen marcaciones en días no sembrados.";
            }

            $advertencias = array_values(array_unique($advertencias));
            // =========================


            // ==================== F) COCINAR asistencia_diaria ====================
            $inicioDT = $inicio->copy()->startOfDay()->toDateTimeString();
            $finDT    = $fin->copy()->endOfDay()->toDateTimeString();

            $sql = "
                UPDATE asistencia_diaria a
                LEFT JOIN (
                    SELECT
                        estudiante_id,
                        reloj_checador_id,
                        DATE(ocurrio_en) AS fecha,
                        COUNT(*) AS conteo,
                        MIN(ocurrio_en) AS primera,
                        MAX(ocurrio_en) AS ultima
                    FROM marcaciones_asistencia
                    WHERE reloj_checador_id = ?
                        AND ocurrio_en BETWEEN ? AND ?
                    GROUP BY estudiante_id, reloj_checador_id, DATE(ocurrio_en)
                ) m 
                    ON m.estudiante_id = a.estudiante_id 
                    AND m.reloj_checador_id = a.reloj_checador_id
                    AND m.fecha = a.fecha

                    LEFT JOIN estudiantes_con_datos_escolares de
                        ON de.estudiante_id = a.estudiante_id

                    LEFT JOIN estatus_escolares_estudiantes_saae ee
                        ON ee.id = de.estatus_escolar_id

                SET
                    a.conteo_marcaciones = IFNULL(m.conteo, 0),
                    a.primera_entrada    = m.primera,
                    a.ultima_salida      = m.ultima,
                    
                    a.estatus = CASE

                    -- Sin datos escolares se pone NO_APLICA
                        WHEN de.id IS NULL
                            THEN 'NO_APLICA'

                    -- Con datos pero estatus inactivo se pone NO_APLICA
                        WHEN ee.clave IN (
                            'suspendido',
                            'no_inscrito',
                            'titulado',
                            'extemporaneo',
                            'baja_temporal',
                            'baja',
                            'no_asistio_a_clases'
                        )
                            THEN 'NO_APLICA'

                    -- Tiene datos, estatus valido y marcaciones
                        WHEN IFNULL(m.conteo, 0) > 0
                            THEN 'PRESENTE'

                    -- Tiene datos, estatus valido, sin marcaciones
                        ELSE 'FALTA'

                    END,

                    a.fuente             = 'RELOJ',
                    a.importacion_id     = ?,
                    a.updated_at         = NOW()
                WHERE a.periodo_id = ?
                    AND a.reloj_checador_id = ?
                    AND a.esperado = 1
            ";

            $diasActualizados = DB::affectingStatement($sql, [
                $reloj->id,
                $inicioDT,
                $finDT,
                $importacion->id,
                $periodo->id,
                $reloj->id,
            ]);


            // F.1) ADVERTENCIA DE QUE HAY ESTUDIANTES REGISTRADOS CON MARCACIONES DE ASISTENCIA, PERO SU ESTATUS NO ES "INSCRITO"
            $controlesConBajaMarcando = DB::table('marcaciones_asistencia as m')
                ->join(
                    'estudiantes_con_datos_escolares as de',
                    'de.estudiante_id',
                    '=',
                    'm.estudiante_id'
                )
                ->join(
                    'estatus_escolares_estudiantes_saae as ee',
                    'ee.id',
                    '=',
                    'de.estatus_escolar_id'
                )
                ->join(
                    'estudiantes_saae as e',
                    'e.id',
                    '=',
                    'm.estudiante_id'
                )
                ->where('m.importacion_id', $importacion->id)
                ->whereIn('ee.clave', [
                    'no_inscrito',
                    'titulado',
                    'extemporaneo',
                    'baja_temporal',
                    'baja',
                    'no_asistio_a_clases',
                ])
                ->distinct()
                ->pluck('e.numero_control')
                ->toArray();

            if (!empty($controlesConBajaMarcando)) {

                sort($controlesConBajaMarcando); // ordenar los numeros de control

                $totalConBajaMarcando = count($controlesConBajaMarcando);

                $ejemplos = array_slice($controlesConBajaMarcando, 0, 50); //mostrar maximo 50 elementos

                $restantes = $totalConBajaMarcando - count($ejemplos);

                $advertencias[] =
                    "Se detectaron {$totalConBajaMarcando} estudiante(s) con estatus escolar no válido para el registro de asistencia. "
                    . "Su asistencia fue marcada como NO_APLICA. "
                    . "Números de control: " . implode(', ', $ejemplos)
                    . ($restantes > 0 ? " y {$restantes} más." : '');
            }
            // =========================


            // F.2) ADVERTENCIA DE ESTUDIANTES SIN DATOS ESCOLARES
            $controlesSinDatosEscolares = DB::table('asistencia_diaria as a')
                ->join(
                    'estudiantes_saae as e',
                    'e.id',
                    '=',
                    'a.estudiante_id'
                )
                ->leftJoin(
                    'estudiantes_con_datos_escolares as de',
                    'de.estudiante_id',
                    '=',
                    'a.estudiante_id'
                )
                ->where('a.importacion_id', $importacion->id)
                ->where('a.esperado', true)
                ->whereNull('de.id')
                ->distinct()
                ->pluck('e.numero_control')
                ->toArray();
            
            sort($controlesSinDatosEscolares); // ordenar los numeros de control

            $estudiantesSinDatosEscolares = count($controlesSinDatosEscolares);

            if ($estudiantesSinDatosEscolares > 0) {

                $ejemplos = array_slice($controlesSinDatosEscolares, 0, 50); //mostrar maximo 50 elementos

                $restantes = $estudiantesSinDatosEscolares - count($ejemplos);

                $advertencias[] =
                    "Se detectaron {$estudiantesSinDatosEscolares} estudiante(s) sin datos escolares registrados. "
                    . "Su asistencia fue marcada como NO_APLICA. "
                    . "Números de control: " . implode(', ', $ejemplos)
                    . ($restantes > 0 ? " y {$restantes} más." : '');
            }
            // =========================


            // ==================== G) GENERAR ALERTAS  ====================
            $resultadoAlertas = [
                'estudiantes_revisados' => 0,
                'alertas_normales_creadas' => 0,
                'alertas_especiales_creadas' => 0,
            ];

            if (in_array($tipoImportacion, ['COMPLETA', 'SOLO_ASISTENCIA'], true)) {
                try {
                    $resultadoAlertas = app(GenerarAlertasCriticasAsistenciaService::class)
                        ->procesarPeriodo($periodo->id, $tipoImportacion);
                } catch (\Throwable $e) {
                    report($e);

                    $advertencias[] = 'La importación terminó, pero ocurrió un problema al generar las alertas automáticas de asistencia.';
                }
            }


            $advertencias = array_values(array_unique($advertencias));


            $resultadosImportacion = [
                'ok' => true,
                'mensaje' => 'Importación completada.',

                // DATOS DE LA IMPORTACION
                'periodo' => $periodo->nombre,
                'dias_periodo' => $inicio->diffInDays($fin) + 1,
                
                // ESTUDIANTES Y SU ASISTENCIA
                'estudiantes_seleccionados' => count($mapControlToId),
                'marcaciones_insertadas' => $marcacionesInsertadas,
                'dias_esperados_detectados' => $totalDiasEsperados,
                'dias_actualizados' => $diasActualizados,

                // EXCEPCIONES DE LA IMPORTACION
                'estudiantes_sin_datos_escolares' => $estudiantesSinDatosEscolares,
                'estudiantes_no_encontrados' => count($controlesNoEncontrados),


                // SOBRE LAS ALERTAS
                'alertas_estudiantes_revisados' => $resultadoAlertas['estudiantes_revisados'] ?? 0,
                'alertas_normales_creadas' => $resultadoAlertas['alertas_normales_creadas'] ?? 0,
                'alertas_especiales_creadas' => $resultadoAlertas['alertas_especiales_creadas'] ?? 0,
                'total_correos_despachados_jobs' => $resultadoAlertas['jobs_correos_alertas_despachados'] ?? 0,

                // ADVETENCIAS DE LA IMPORTACION
                'advertencias' => $advertencias,
            ];

            $importacion->update([
                'advertencias' => empty($advertencias) ? null : $advertencias,
                'resultados_importacion' => $resultadosImportacion,
            ]);

            return $resultadosImportacion;
        });
    }


    // ==================== DB HELPER ====================
    private function insertOrIgnoreChunk(string $table, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $inserted = 0;

        foreach (array_chunk($rows, $this->chunkSize) as $chunk) {
            $inserted += DB::table($table)->insertOrIgnore($chunk);
        }

        return $inserted;
    }


    private function upsertChunk(
        string $table,
        array $rows,
        array $uniqueBy,
        array $updateColumns
    ): void {
        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, $this->chunkSize) as $chunk) {
            DB::table($table)->upsert(
                $chunk,
                $uniqueBy,
                $updateColumns
            );
        }
    }
}