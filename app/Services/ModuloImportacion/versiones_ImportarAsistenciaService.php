// =========================================================================================================================
    VERSION 5 - NO PASO PRUEBAS DE IMPORTACION, BUENA ESTRUCTURA Y EXCELENTE ESCALABILIDAD - ✅ ACTUALMENTE IMPLEMENTADO
// =========================================================================================================================










// =========================================================================================================================
    VERSION 4 - NO PASO TODAS LAS PRUEBAS DE IMPORTACION, ESTRUCTURA REGULAR Y ESCALABILIDAD REGULAR
// =========================================================================================================================
<?php

namespace App\Services\ModuloImportacionAsistencia;

use App\Models\EstudiantesSaae; //Modelo de la tabla estudiantes, para obtener: IDs con pluck('id','numero_control')
use App\Models\ImportacionAsistencia; //Modelo para: crear el registro de auditoría de esta importacion
use App\Models\Periodo; //Para: firstOrCreate del periodo
use App\Models\RelojChecador; //Para: obtener/crear el reloj
use Carbon\Carbon; //Librería de fechas,se usa para: parsear el periodo, sumar dias, construir fechas con hora
use Illuminate\Database\QueryException; //Para atrapar el error de BD cuando se intenta insertar un duplicado (error 1062)
use Illuminate\Support\Facades\DB; //Para transacciones y para usar: Query Builder (DB::table(...)) y SQL (DB::affectingStatement(...)).
use PhpOffice\PhpSpreadsheet\Cell\Coordinate; //Para convertir columna “A / B / O / AA” a numero de columna (1, 2, 15, 27). Ademas senecesita para detectar cuantas columnas reales trae el Excel (y asi quitar el hardcode de 15 dias) lo cual limitaba
use PhpOffice\PhpSpreadsheet\IOFactory; //Para cargar el Excel: IOFactory::load($rutaFisicaArchivo).
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class ImportarAsistenciaExcelService
{
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
    ): array
    {
        $tipoImportacion = strtoupper(trim($tipoImportacion));
        $parserClave = 'modulo_importacion_asistencia_v5';
        $advertencias = [];

        $turnos = [];
        $marcacionesCrudas = [];
        $controles = [];
        $totalDiasEsperados = 0;

        // =========================
        // 1) LEER EXCEL (SIN DB)
        // =========================
        $reader = IOFactory::createReaderForFile($rutaFisicaArchivo);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($rutaFisicaArchivo);

        $hojasDetectadas = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $hojasDetectadas[] = $sheet->getTitle();
        }

        $wsTurnos = $this->buscarHojaOpcional($spreadsheet, 'Reporte de Turnos');
        $wsAsistencia = $this->buscarHojaOpcional($spreadsheet, 'Reporte de Asistencia');

        if ($tipoImportacion === 'COMPLETA') {
            if (!$wsTurnos) {
                throw new \RuntimeException('La importación COMPLETA requiere la hoja "Reporte de Turnos".');
            }

            if (!$wsAsistencia) {
                throw new \RuntimeException('La importación COMPLETA requiere la hoja "Reporte de Asistencia".');
            }

            $textoPeriodo = $this->celdaTextoFormateada($wsTurnos, 3, 2);
            [$inicio, $fin] = $this->parsearPeriodo($textoPeriodo);

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            if ($diasPeriodo < 1 || $diasPeriodo > 31) {
                throw new \RuntimeException(
                    "Periodo inválido: {$diasPeriodo} días de lo permitido. " .
                    "Verifica que el rango del periodo del Excel no sea menor a 1 día o mayor a 31 días (un mes)."
                );
            }

            $diasTurnos = $this->diasDisponiblesEnTurnos($wsTurnos, $diasPeriodo);

            if ($diasTurnos < 1) {
                throw new \RuntimeException('La hoja "Reporte de Turnos" no contiene columnas de días válidas.');
            }

            $diasAsis   = $this->diasDisponiblesEnAsistencia($wsAsistencia, $diasPeriodo);

            if ($diasAsis < 1) {
                throw new \RuntimeException('La hoja "Reporte de Asistencia" no contiene columnas de días válidas.');
            }

            [$turnos, $totalDiasEsperados] = $this->leerTurnos($wsTurnos, $inicio, $diasTurnos);
            $marcacionesCrudas = $this->leerAsistencia($wsAsistencia, $inicio, $diasAsis);

        } elseif ($tipoImportacion === 'SOLO_ASISTENCIA') {
            if (!$wsAsistencia) {
                throw new \RuntimeException('La importación SOLO_ASISTENCIA requiere la hoja "Reporte de Asistencia".');
            }

            if (!$periodoId) {
                throw new \RuntimeException('Debes seleccionar un periodo para la importación SOLO_ASISTENCIA.');
            }

            $periodoBD = Periodo::findOrFail($periodoId);
            $inicio = Carbon::parse($periodoBD->fecha_inicio)->startOfDay();
            $fin    = Carbon::parse($periodoBD->fecha_fin)->startOfDay();

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            if ($diasPeriodo < 1 || $diasPeriodo > 31) {
                throw new \RuntimeException("Periodo inválido: {$diasPeriodo} días.");
            }

            $diasAsis = $this->diasDisponiblesEnAsistencia($wsAsistencia, $diasPeriodo);

            if ($diasAsis < 1) {
                throw new \RuntimeException('La hoja "Reporte de Asistencia" no contiene columnas de días válidas.');
            }

            $marcacionesCrudas = $this->leerAsistencia($wsAsistencia, $inicio, $diasAsis);

        } elseif ($tipoImportacion === 'SOLO_TURNOS') {
            throw new \RuntimeException('Por ahora SOLO_TURNOS aún no está implementado.');
        } else {
            throw new \RuntimeException('Tipo de importación no válido.');
        }

        // Lista única de números de control
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

        // =========================
        // 2) DB (TODO EN TRANSACCION)
        // =========================
        return DB::transaction(function () use (
            $rutaFisicaArchivo,
            $rutaStorageArchivo,
            $tipoImportacion,
            $relojId,
            $periodoId,
            $importadoPor,
            $notas,
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

            // Periodo
            if ($tipoImportacion === 'COMPLETA') {
                $periodo = Periodo::firstOrCreate(
                    [
                        'fecha_inicio' => $inicio->toDateString(),
                        'fecha_fin'    => $fin->toDateString(),
                    ],
                    [
                        'nombre'  => $inicio->toDateString() . ' a ' . $fin->toDateString(),
                        'activo'  => false,
                    ]
                );
            } else {
                $periodo = Periodo::findOrFail($periodoId);
            }

            // Reloj
            $reloj = RelojChecador::findOrFail($relojId);

            // Crear importación
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
                    'estado'                 => 'OK',
                    'advertencias'           => empty($advertencias) ? null : $advertencias,
                    'resultados_importacion' => null,
                    'notas'                  => $notas ? trim($notas) : null,
                ]);
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) === 1062) {
                    return [
                        'ok'      => false,
                        'mensaje' => 'Este archivo ya fue importado para ese periodo y reloj.',
                    ];
                }
                throw $e;
            }

            // =========================
            // A) ESTUDIANTES
            // =========================
            $estRows = [];
            foreach ($controles as $nc) {
                $estRows[] = [
                    'numero_control'  => $nc,
                    'nombre_completo' => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }

            $estudiantesInsertados = $this->insertOrIgnoreChunk('estudiantes_saae', $estRows);

            $mapControlToId = empty($controles)
                ? []
                : EstudiantesSaae::whereIn('numero_control', $controles)
                    ->pluck('id', 'numero_control')
                    ->all();

            // =========================
            // B) PERIODO_ESTUDIANTES y PERIODO_FECHAS (solo COMPLETA)
            // =========================
            if ($tipoImportacion === 'COMPLETA') {
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
                    DB::table('periodo_estudiantes')->upsert(
                        $periodoEstRows,
                        ['periodo_id', 'estudiante_id'],
                        ['activo', 'updated_at']
                    );
                }

                // Guardar solo los dias que realmente cuentan segun Turnos
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
                    DB::table('periodo_fechas')->upsert(
                        $periodoFechaRows,
                        ['periodo_id', 'fecha'],
                        ['es_clase', 'tipo_dia', 'origen', 'observaciones', 'updated_at']
                    );
                }
            }

            // =========================
            // C) INSCRIPCIONES RELOJ
            // =========================
            $mapRelojUsuarioToEstId = [];

            if ($tipoImportacion === 'COMPLETA') {
                $insRows = [];

                foreach ($turnos as $t) {
                    $nc  = $t['numero_control'];
                    $rid = $t['reloj_usuario_id'];

                    if ($nc === '' || $rid === '') {
                        continue;
                    }

                    $estId = $mapControlToId[$nc] ?? null;
                    if (!$estId) {
                        continue;
                    }

                    $insRows[] = [
                        'reloj_checador_id' => $reloj->id,
                        'reloj_usuario_id'  => $rid,
                        'estudiante_id'     => $estId,
                        'activo'            => true,
                        'created_at'        => $now,
                        'updated_at'        => $now,
                    ];

                    $mapRelojUsuarioToEstId[$rid] = $estId;
                }

                if (!empty($insRows)) {
                    DB::table('reloj_inscripciones')->upsert(
                        $insRows,
                        ['reloj_checador_id', 'reloj_usuario_id'],
                        ['estudiante_id', 'activo', 'updated_at']
                    );
                }
            } else {
                $mapRelojUsuarioToEstId = DB::table('reloj_inscripciones')
                    ->where('reloj_checador_id', $reloj->id)
                    ->where('activo', true)
                    ->pluck('estudiante_id', 'reloj_usuario_id')
                    ->all();
            }

            // =========================
            // D) ASISTENCIA_DIARIA (sembrar base)
            // =========================
            $adRows = [];

            if ($tipoImportacion === 'COMPLETA') {
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
                $estudiantesPeriodoIds = DB::table('periodo_estudiantes')
                    ->where('periodo_id', $periodo->id)
                    ->where('activo', true)
                    ->pluck('estudiante_id')
                    ->all();

                $fechasClase = DB::table('periodo_fechas')
                    ->where('periodo_id', $periodo->id)
                    ->where('es_clase', true)
                    ->orderBy('fecha')
                    ->pluck('fecha')
                    ->all();

                if (empty($estudiantesPeriodoIds)) {
                    throw new \RuntimeException(
                        'El periodo seleccionado no tiene estudiantes activos en periodo_estudiantes.'
                    );
                }

                if (empty($fechasClase)) {
                    throw new \RuntimeException(
                        'El periodo seleccionado no tiene días de clase registrados en periodo_fechas.'
                    );
                }

                foreach ($estudiantesPeriodoIds as $estId) {
                    foreach ($fechasClase as $fecha) {
                        $adRows[] = [
                            'estudiante_id'      => $estId,
                            'periodo_id'         => $periodo->id,
                            'fecha'              => Carbon::parse($fecha)->toDateString(),
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
                DB::table('asistencia_diaria')->upsert(
                    $adRows,
                    ['estudiante_id', 'periodo_id', 'fecha'],
                    ['esperado', 'estatus', 'fuente', 'importacion_id', 'updated_at']
                );
            }

            // =========================
            // E) MARCACIONES
            // =========================
            $marRows = [];

            foreach ($marcacionesCrudas as $m) {
                $estId = null;

                if (
                    $m['reloj_usuario_id'] !== '' &&
                    isset($mapRelojUsuarioToEstId[$m['reloj_usuario_id']])
                ) {
                    $estId = $mapRelojUsuarioToEstId[$m['reloj_usuario_id']];
                } else {
                    $nc = $m['numero_control'];
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

            // =========================
            // F) COCINAR asistencia_diaria
            // =========================
            $inicioDT = $inicio->copy()->startOfDay()->toDateTimeString();
            $finDT    = $fin->copy()->endOfDay()->toDateTimeString();

            $sql = "
                UPDATE asistencia_diaria a
                LEFT JOIN (
                    SELECT
                        estudiante_id,
                        DATE(ocurrio_en) AS fecha,
                        COUNT(*) AS conteo,
                        MIN(ocurrio_en) AS primera,
                        MAX(ocurrio_en) AS ultima
                    FROM marcaciones_asistencia
                    WHERE reloj_checador_id = ?
                    AND importacion_id = ?
                    AND ocurrio_en BETWEEN ? AND ?
                    GROUP BY estudiante_id, DATE(ocurrio_en)
                ) m ON m.estudiante_id = a.estudiante_id AND m.fecha = a.fecha
                SET
                    a.conteo_marcaciones = IFNULL(m.conteo, 0),
                    a.primera_entrada    = m.primera,
                    a.ultima_salida      = m.ultima,
                    a.estatus            = CASE WHEN IFNULL(m.conteo, 0) > 0 THEN 'PRESENTE' ELSE 'FALTA' END,
                    a.fuente             = 'RELOJ',
                    a.importacion_id     = ?,
                    a.updated_at         = NOW()
                WHERE a.periodo_id = ?
                AND a.esperado = 1
            ";

            $diasActualizados = DB::affectingStatement($sql, [
                $reloj->id,
                $importacion->id,
                $inicioDT,
                $finDT,
                $importacion->id,
                $periodo->id,
            ]);

            $resultadosImportacion = [
                'ok'                        => true,
                'mensaje'                   => 'Importación completada.',
                'periodo'                   => $periodo->nombre,
                'dias_periodo'              => $inicio->diffInDays($fin) + 1,
                'estudiantes_insertados'    => $estudiantesInsertados,
                'marcaciones_insertadas'    => $marcacionesInsertadas,
                'dias_esperados_detectados' => $totalDiasEsperados,
                'dias_actualizados'         => $diasActualizados,
            ];

            $importacion->update([
                'resultados_importacion' => $resultadosImportacion,
            ]);

            return $resultadosImportacion;
        });
    }


    // =========================
    // LECTURA EXCEL (DINAMICA)
    // =========================
    private function diasDisponiblesEnTurnos($ws, int $diasPeriodo): int
    {
        // Turnos: dias empiezan en columna D,4
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestColumn());
        $diasEnExcel = max(0, $highestCol - 3);
        return min($diasPeriodo, $diasEnExcel);
    }

    private function diasDisponiblesEnAsistencia($ws, int $diasPeriodo): int
    {
        // Asistencia: dias empiezan en columna A,1
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestColumn());
        $diasEnExcel = max(0, $highestCol);
        return min($diasPeriodo, $diasEnExcel);
    }

    private function leerTurnos($wsTurnos, Carbon $inicio, int $dias): array
    {
        $turnos = [];
        $totalDiasEsperados = 0;
        $maxRow = $wsTurnos->getHighestRow();

        for ($fila = 5; $fila <= $maxRow; $fila++) {
            // IMPORTANTE: formatted para no perder ceros
            $relojUsuarioId = $this->celdaId($wsTurnos, 1, $fila);
            $numeroControl  = $this->celdaId($wsTurnos, 2, $fila);

            if ($relojUsuarioId === '' && $numeroControl === '') {
                continue;
            }

            if ($numeroControl === '') {
                continue;
            }

            $fechasEsperadas = [];

            for ($dia = 1; $dia <= $dias; $dia++) {
                $colDia = 3 + $dia;
                $valor = $wsTurnos->getCell([$colDia, $fila])->getValue();

                if ((string)$valor === '1' || $valor === 1) {
                    $fecha = (clone $inicio)->addDays($dia - 1)->toDateString();
                    $fechasEsperadas[] = $fecha;
                    $totalDiasEsperados++;
                }
            }

            $turnos[] = [
                'numero_control'   => $numeroControl,
                'reloj_usuario_id' => $relojUsuarioId,
                'fechas_esperadas' => $fechasEsperadas,
            ];
        }

        return [$turnos, $totalDiasEsperados];
    }

    private function leerAsistencia($wsAsistencia, Carbon $inicio, int $dias): array
    {
        $maxRow = $wsAsistencia->getHighestRow();
        //li el numero de filas a procesar (anti Excel “inflado”)
        if ($maxRow > 10000) {
            throw new \RuntimeException("El Excel trae demasiadas filas ({$maxRow}). Verifica que sea el formato correcto.");
        }

        $out = [];

        for ($r = 1; $r <= $maxRow; $r++) {
            $marca = $this->celdaTextoFormateada($wsAsistencia, 1, $r); // col A
            if ($marca !== 'ID:') continue;

            $relojUsuarioId = $this->celdaId($wsAsistencia, 5, $r);// E
            $numeroControl  = $this->celdaId($wsAsistencia, 11, $r);// K

            $filaHoras = $r + 1;

            for ($dia = 1; $dia <= $dias; $dia++) {
                $celdaCruda = $this->celdaTextoFormateada($wsAsistencia, $dia, $filaHoras);
                if ($celdaCruda === '') continue;

                $horas = $this->extraerHoras($celdaCruda);
                if (empty($horas)) continue;

                $horas = array_values(array_unique($horas)); // evita repetidas dentro de misma celda
                $fecha = (clone $inicio)->addDays($dia - 1)->toDateString();

                foreach ($horas as $hhmm) {
                    $dt = Carbon::createFromFormat('Y-m-d G:i', $fecha.' '.$hhmm)->seconds(0);

                    $out[] = [
                        'reloj_usuario_id' => $relojUsuarioId,
                        'numero_control'   => $numeroControl,
                        'ocurrio_en'       => $dt->toDateTimeString(),
                        'celda_cruda'      => mb_substr($celdaCruda, 0, 255),
                    ];
                }
            }
        }

        return $out;
    }


    // =========================
    // DB HELPERS (BULK)
    // =========================
    private function insertOrIgnoreChunk(string $table, array $rows): int
    {
        if (empty($rows)) return 0;

        $inserted = 0;
        foreach (array_chunk($rows, $this->chunkSize) as $chunk) {
            $inserted += DB::table($table)->insertOrIgnore($chunk);
        }
        return $inserted;
    }


    // =========================
    // UTILIDADES
    // =========================
    private function buscarHojaOpcional($spreadsheet, string $busqueda)
    {
        $busquedaNorm = mb_strtolower(trim($busqueda));

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower(trim($sheet->getTitle())) === $busquedaNorm) {
                return $sheet;
            }
        }

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower($sheet->getTitle()) !== '' &&
                mb_strpos(mb_strtolower($sheet->getTitle()), $busquedaNorm) !== false) {
                return $sheet;
            }
        }

        //$nombres = [];
        //foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
        //    $nombres[] = $sheet->getTitle();
        //}

        return null;
    }

    private function parsearPeriodo(string $texto): array
    {
        if (!preg_match('/(\d{4}-\d{2}-\d{2})\s*~\s*(\d{4}-\d{2}-\d{2})/', $texto, $m)) {
            throw new \RuntimeException('No se pudo leer el periodo: '.$texto);
        }
        return [Carbon::parse($m[1])->startOfDay(), Carbon::parse($m[2])->startOfDay()];
    }

    private function celdaTextoFormateada($ws, int $col, int $row): string
    {
        $cell = $ws->getCell([$col, $row]);
        $v = $cell->getValue();

        // RichText -> string
        if ($v instanceof RichText) {
            $v = $v->getPlainText();
        }

        // Si Excel lo devuelve como DateTime
        if ($v instanceof \DateTimeInterface) {
            return $this->normExcel($v->format('H:i'));
        }

        // Si viene como numero (serial de Excel)
        if (is_numeric($v)) {
            $n = (float)$v;

            // Caso tipico: hora sola (0..1)
            // Ej: 0.3333 = 08:00
            if ($n >= 0 && $n < 1) {
                try {
                    return $this->normExcel(ExcelDate::excelToDateTimeObject($n)->format('H:i'));
                } catch (\Throwable $e) {
                    // fallback abajo
                }
            }

            // Si no es hora sola, regresa el numero
            return $this->normExcel((string)$v);
        }

        // Texto normal
        $s = (string)($v ?? '');
        if ($s === '') {
            // fallback
            $s = (string)($cell->getFormattedValue() ?? '');
        }

        return $this->normExcel($s);
    }

    private function extraerHoras(string $texto): array
    {
        preg_match_all('/(?:[01]?\d|2[0-3]):[0-5]\d/', $texto, $m);
        return $m[0] ?? [];
    }

    private function normExcel(string $s): string
    {
        // NBSP (espacio “duro”) + zero-width space
        $s = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], ' ', $s);

        // normaliza espacios
        $s = preg_replace('/[[:space:]]+/u', ' ', $s);

        return trim($s);
    }

    private function normId(string $s): string
    {
        $s = $this->normExcel($s);
        // en IDs quitamos espacios completamente
        return preg_replace('/\s+/u', '', $s);
    }

    private function celdaId($ws, int $col, int $row): string
    {
        $cell = $ws->getCell([$col, $row]);

        // Primero intenta respetar cómo Excel lo muestra
        $formatted = $this->normId((string) ($cell->getFormattedValue() ?? ''));
        if ($formatted !== '') {
            return $formatted;
        }

        // Fallback al valor crudo
        $raw = $cell->getValue();

        if ($raw instanceof RichText) {
            return $this->normId($raw->getPlainText());
        }

        return $this->normId((string) ($raw ?? ''));
    }
}










// =========================================================================================================================
    VERSION 3 - NO PASO PRUEBAS DE IMPORTACION, MALA ESTRUCTURA Y MALA ESCALABILIDAD  
// =========================================================================================================================
<?php

namespace App\Services\ModuloImportacionAsistencia;

use App\Models\EstudiantesSaae; //Modelo de la tabla estudiantes, para obtener: IDs con pluck('id','numero_control')
use App\Models\ImportacionAsistencia; //Modelo para: crear el registro de auditoría de esta importacion
use App\Models\Periodo; //Para: firstOrCreate del periodo
use App\Models\RelojChecador; //Para: obtener/crear el reloj
use Carbon\Carbon; //Librería de fechas,se usa para: parsear el periodo, sumar dias, construir fechas con hora
use Illuminate\Database\QueryException; //Para atrapar el error de BD cuando se intenta insertar un duplicado (error 1062)
use Illuminate\Support\Facades\DB; //Para transacciones y para usar: Query Builder (DB::table(...)) y SQL (DB::affectingStatement(...)).
use PhpOffice\PhpSpreadsheet\Cell\Coordinate; //Para convertir columna “A / B / O / AA” a numero de columna (1, 2, 15, 27). Ademas senecesita para detectar cuantas columnas reales trae el Excel (y asi quitar el hardcode de 15 dias) lo cual limitaba
use PhpOffice\PhpSpreadsheet\IOFactory; //Para cargar el Excel: IOFactory::load($rutaFisicaArchivo).
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\RichText\RichText;

class ImportarAsistenciaExcelService
{
    //En vez de insertar 50,000 filas en un solo insert (pesado), las mete de 1000 en 1000, para mejorar el rendimeinto y evitar limites del motor
    private int $chunkSize = 1000;///chunkSize = tamaño de lote


    public function importar(string $rutaFisicaArchivo, string $rutaStorageArchivo, ?int $relojId = null, ?int $importadoPor = null, ?string $notas = null): array
    {
        // =========================
        // 1) LEER EXCEL (SIN DB)
        // =========================
        $reader = IOFactory::createReaderForFile($rutaFisicaArchivo);
        $reader->setReadDataOnly(true); // no estilos, menos RAM
        $reader->setLoadSheetsOnly(['Reporte de Turnos', 'Reporte de Asistencia']); // solo las 2 hojas
        $spreadsheet = $reader->load($rutaFisicaArchivo);

        //Entontrar las hojas a utilizar en el Excel
        $wsTurnos     = $this->buscarHoja($spreadsheet, 'Reporte de Turnos');
        $wsAsistencia = $this->buscarHoja($spreadsheet, 'Reporte de Asistencia');


        //LEER PERIODO Y CALCULAR DIAS. Turnos C2: "2025-08-01 ~ 2025-08-15"
        $textoPeriodo = $this->celdaTextoFormateada($wsTurnos, 3, 2);
        [$inicio, $fin] = $this->parsearPeriodo($textoPeriodo);

        //lee C,2 y saca fechas
        $diasPeriodo = $inicio->diffInDays($fin) + 1;
        //protege de errores de Excel o intentos de sobrecargar el modulo por periodos exagerados (no menor a 1 dia o mayor a 31 dias, que es un maximo de un mes)
        if ($diasPeriodo < 1 || $diasPeriodo > 31) {
            throw new \RuntimeException("Periodo inválido: {$diasPeriodo} días de lo permitido. Verifica que el rango del periodo del Excel no sea menor a 1 día o mayor a 31 días (un mes).");
        }


        //DETECTAR CUANTOS DIAS VIENEN EN EL EXCEL
        $diasTurnos = $this->diasDisponiblesEnTurnos($wsTurnos, $diasPeriodo);
        $diasAsis   = $this->diasDisponiblesEnAsistencia($wsAsistencia, $diasPeriodo);


        //LEER LOS TURNOS (alumnos + fechas esperadas)
        // turnos[] = ['numero_control', 'reloj_usuario_id', 'fechas_esperadas'=>[Y-m-d...]]
        [$turnos, $totalDiasEsperados] = $this->leerTurnos($wsTurnos, $inicio, $diasTurnos);


        //LEER ASISTENCIA (marcaciones crudas)
        // marcacionesCrudas[] = ['reloj_usuario_id','numero_control','ocurrio_en','celda_cruda']
        $marcacionesCrudas = $this->leerAsistencia($wsAsistencia, $inicio, $diasAsis);

        // Lista unica de numeros de control (por si hay en asistencia pero no en turnos)
        $controles = [];
        foreach ($turnos as $t) {
            if ($t['numero_control'] !== '') $controles[$t['numero_control']] = true;
        }
        foreach ($marcacionesCrudas as $m) {
            if ($m['numero_control'] !== '') $controles[$m['numero_control']] = true;
        }
        $controles = array_keys($controles);


        // ========================
        // 2) DB (TODO EN TRANSACCION)
        // =========================
        // $resultadosImportacion no se incluye por que aun no existe
        return DB::transaction(function () use (
            $rutaFisicaArchivo,
            $rutaStorageArchivo,
            $relojId,
            $importadoPor,
            $notas,
            $inicio,
            $fin,
            $controles,
            $turnos,
            $marcacionesCrudas,
            $totalDiasEsperados
        ) {
            $now = now();

            // Periodo
            $periodo = Periodo::firstOrCreate(
                ['fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $fin->toDateString()],
                ['nombre' => $inicio->toDateString().' a '.$fin->toDateString(), 'activo' => false]
            );

            // Reloj
            $reloj = $relojId
                ? RelojChecador::findOrFail($relojId)
                : RelojChecador::firstOrCreate(['nombre' => 'Reloj Principal'], ['activo' => true]);

            // Crear importacion (anti-duplicado real por UNIQUE; sin race condition)
            $hash = hash_file('sha256', $rutaFisicaArchivo);
            try {
                $importacion = ImportacionAsistencia::create([
                    'reloj_checador_id' => $reloj->id,
                    'periodo_id'        => $periodo->id,
                    'archivo_nombre'    => basename($rutaFisicaArchivo),
                    'archivo_ruta'      => $rutaStorageArchivo,
                    'archivo_hash'      => $hash,
                    'importado_por'     => $importadoPor,
                    'importado_en'      => $now,
                    'estado'            => 'OK',
                    'resultados_importacion' => null, //aun no hay resultados, por eso null
                    'notas'             => $notas ? trim($notas) : null,
                ]);
            } catch (QueryException $e) {
                // MySQL duplicate key => 1062
                if (($e->errorInfo[1] ?? null) === 1062) {
                    return [
                        'ok' => false,
                        'mensaje' => 'Este archivo ya fue importado, para ese periodo y reloj.',
                    ];
                }
                throw $e;
            }


            // =========================
            // A) ESTUDIANTES (BULK)
            // =========================
            $estRows = [];
            foreach ($controles as $nc) {
                $estRows[] = [
                    'numero_control'  => $nc,
                    'nombre_completo' => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            $estudiantesInsertados = $this->insertOrIgnoreChunk('estudiantes_saae', $estRows);

            // Mapa numero_control => estudiante_id
            $mapControlToId = EstudiantesSaae::whereIn('numero_control', $controles)
                ->pluck('id', 'numero_control')
                ->all();


            // =========================
            // B) INSCRIPCIONES RELOJ (UPSERT)
            // =========================
            $insRows = [];
            $mapRelojUsuarioToEstId = []; // para resolver marcaciones rapido

            foreach ($turnos as $t) {
                $nc  = $t['numero_control'];
                $rid = $t['reloj_usuario_id'];

                if ($nc === '' || $rid === '') continue;

                $estId = $mapControlToId[$nc] ?? null;
                if (!$estId) continue;

                $insRows[] = [
                    'reloj_checador_id' => $reloj->id,
                    'reloj_usuario_id'  => $rid,
                    'estudiante_id'     => $estId,
                    'activo'            => true,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                $mapRelojUsuarioToEstId[$rid] = $estId;
            }

            if (!empty($insRows)) {
                DB::table('reloj_inscripciones')->upsert(
                    $insRows,
                    ['reloj_checador_id', 'reloj_usuario_id'],
                    ['estudiante_id', 'activo', 'updated_at']
                );
            }

            // =========================
            // C) ASISTENCIA_DIARIA (UPSERT) SOLO DIAS ESPERADOS
            // =========================
            $adRows = [];
            foreach ($turnos as $t) {
                $nc = $t['numero_control'];
                $estId = $mapControlToId[$nc] ?? null;
                if (!$estId) continue;

                foreach ($t['fechas_esperadas'] as $fecha) {
                    $adRows[] = [
                        'estudiante_id'      => $estId,
                        'periodo_id'         => $periodo->id,
                        'fecha'              => $fecha,
                        'esperado'           => true,
                        'estatus'            => 'FALTA',   // base: luego se “cocina”
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

            if (!empty($adRows)) {
                // upsert por uq_asistencia_dia
                DB::table('asistencia_diaria')->upsert(
                    $adRows,
                    ['estudiante_id', 'periodo_id', 'fecha'],
                    ['esperado', 'estatus', 'fuente', 'importacion_id', 'updated_at']
                );
            }

            // =========================
            // D) MARCACIONES (INSERT IGNORE) + CHUNKS
            // =========================
            $marRows = [];
            foreach ($marcacionesCrudas as $m) {
                $estId = null;

                // Prioridad: mapeo por reloj_usuario_id si existe
                if ($m['reloj_usuario_id'] !== '' && isset($mapRelojUsuarioToEstId[$m['reloj_usuario_id']])) {
                    $estId = $mapRelojUsuarioToEstId[$m['reloj_usuario_id']];
                } else {
                    // fallback: numero_control
                    $nc = $m['numero_control'];
                    $estId = $mapControlToId[$nc] ?? null;
                }

                if (!$estId) continue;

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

            // =========================
            // E) “COCINAR” asistencia_diaria EN SQL (UN SOLO GOLPE)
            // =========================
            $inicioDT = $inicio->copy()->startOfDay()->toDateTimeString();
            $finDT    = $fin->copy()->endOfDay()->toDateTimeString();

            // Nota: aqui NO filtramos por importacion_id, porque si ya existan marcaciones previas
            // (mismo periodo/rango), deben contar para que el cocinado sea correcto.
            $sql = "
                UPDATE asistencia_diaria a
                LEFT JOIN (
                    SELECT
                        estudiante_id,
                        DATE(ocurrio_en) AS fecha,
                        COUNT(*) AS conteo,
                        MIN(ocurrio_en) AS primera,
                        MAX(ocurrio_en) AS ultima
                    FROM marcaciones_asistencia
                    WHERE reloj_checador_id = ?
                        AND ocurrio_en BETWEEN ? AND ?
                    GROUP BY estudiante_id, DATE(ocurrio_en)
                ) m ON m.estudiante_id = a.estudiante_id AND m.fecha = a.fecha
                SET
                    a.conteo_marcaciones = IFNULL(m.conteo, 0),
                    a.primera_entrada    = m.primera,
                    a.ultima_salida      = m.ultima,
                    a.estatus            = CASE WHEN IFNULL(m.conteo, 0) > 0 THEN 'PRESENTE' ELSE 'FALTA' END,
                    a.fuente             = 'RELOJ',
                    a.importacion_id     = ?,
                    a.updated_at         = NOW()
                WHERE a.periodo_id = ?
                    AND a.esperado = 1
            ";

            $diasActualizados = DB::affectingStatement($sql, [
                $reloj->id,
                $inicioDT,
                $finDT,
                $importacion->id,
                $periodo->id,
            ]);

            $resultadosImportacion = [
                'ok' => true,
                'mensaje' => 'Importación completada.',
                'periodo' => $periodo->nombre,
                'dias_periodo' => $inicio->diffInDays($fin) + 1,
                'estudiantes_insertados' => $estudiantesInsertados,
                'marcaciones_insertadas' => $marcacionesInsertadas,
                'dias_esperados_detectados' => $totalDiasEsperados,
                'dias_actualizados' => $diasActualizados,
            ];

            //Este guarda EL RESUMEN DE LA IMPORTACION en la columna importaciones_asistencia, de la tabla importaciones_asistencia
            $importacion->update([
                'resultados_importacion' => $resultadosImportacion,
            ]);

            return $resultadosImportacion;
        });


    }


    // =========================
    // LECTURA EXCEL (DINAMICA)
    // =========================

    private function diasDisponiblesEnTurnos($ws, int $diasPeriodo): int
    {
        // Turnos: dias empiezan en columna D,4. Entonces diasDisponibles = highestColIndex - 3
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestColumn());
        $diasEnExcel = max(0, $highestCol - 3);
        return max(1, min($diasPeriodo, $diasEnExcel));
    }

    private function diasDisponiblesEnAsistencia($ws, int $diasPeriodo): int
    {
        // Asistencia: dias empiezan en columna A,1. Entonces diasDisponibles = highestColIndex
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestColumn());
        $diasEnExcel = max(0, $highestCol);
        return max(1, min($diasPeriodo, $diasEnExcel));
    }

    private function leerTurnos($wsTurnos, Carbon $inicio, int $dias): array
    {
        $turnos = [];
        $totalDiasEsperados = 0;

        $fila = 5; // donde empiezan alumnos

        while (true) {
            // IMPORTANTE: formatted para no perder ceros
            $relojUsuarioId = $this->normId($this->celdaTextoFormateada($wsTurnos, 1, $fila)); // A
            $numeroControl  = $this->normId($this->celdaTextoFormateada($wsTurnos, 2, $fila)); // B

            if ($relojUsuarioId === '' && $numeroControl === '') {
                break;
            }

            if ($numeroControl === '') {
                $fila++;
                continue;
            }

            $fechasEsperadas = [];

            // Dias: D..(D + dias - 1)
            for ($dia = 1; $dia <= $dias; $dia++) {
                $colDia = 3 + $dia; // 4..(3+dias)
                $valor = $wsTurnos->getCell([$colDia, $fila])->getValue();

                if ((string)$valor === '1' || $valor === 1) {
                    $fecha = (clone $inicio)->addDays($dia - 1)->toDateString();
                    $fechasEsperadas[] = $fecha;
                    $totalDiasEsperados++;
                }
            }

            $turnos[] = [
                'numero_control'   => $numeroControl,
                'reloj_usuario_id' => $relojUsuarioId,
                'fechas_esperadas' => $fechasEsperadas,
            ];

            $fila++;
        }

        return [$turnos, $totalDiasEsperados];
    }

    private function leerAsistencia($wsAsistencia, Carbon $inicio, int $dias): array
    {
        $maxRow = $wsAsistencia->getHighestRow();
        //li el numero de filas a procesar (anti Excel “inflado”)
        if ($maxRow > 10000) {
            throw new \RuntimeException("El Excel trae demasiadas filas ({$maxRow}). Verifica que sea el formato correcto.");
        }

        $out = [];

        for ($r = 1; $r <= $maxRow; $r++) {
            $marca = $this->celdaTextoFormateada($wsAsistencia, 1, $r); // col A
            if ($marca !== 'ID:') continue;

            $relojUsuarioId = $this->normId($this->celdaTextoFormateada($wsAsistencia, 5, $r));// E
            $numeroControl  = $this->normId($this->celdaTextoFormateada($wsAsistencia, 11, $r));// K

            $filaHoras = $r + 1;

            for ($dia = 1; $dia <= $dias; $dia++) {
                $celdaCruda = $this->celdaTextoFormateada($wsAsistencia, $dia, $filaHoras);
                if ($celdaCruda === '') continue;

                $horas = $this->extraerHoras($celdaCruda);
                if (empty($horas)) continue;

                $horas = array_values(array_unique($horas)); // evita repetidas dentro de misma celda
                $fecha = (clone $inicio)->addDays($dia - 1)->toDateString();

                foreach ($horas as $hhmm) {
                    $dt = Carbon::createFromFormat('Y-m-d G:i', $fecha.' '.$hhmm)->seconds(0);

                    $out[] = [
                        'reloj_usuario_id' => $relojUsuarioId,
                        'numero_control'   => $numeroControl,
                        'ocurrio_en'       => $dt->toDateTimeString(),
                        'celda_cruda'      => mb_substr($celdaCruda, 0, 255),
                    ];
                }
            }
        }

        return $out;
    }


    // =========================
    // DB HELPERS (BULK)
    // =========================

    private function insertOrIgnoreChunk(string $table, array $rows): int
    {
        if (empty($rows)) return 0;

        $inserted = 0;
        foreach (array_chunk($rows, $this->chunkSize) as $chunk) {
            $inserted += DB::table($table)->insertOrIgnore($chunk);
        }
        return $inserted;
    }


    // =========================
    // UTILIDADES
    // =========================

    private function buscarHoja($spreadsheet, string $busqueda)
    {
        $busquedaNorm = mb_strtolower(trim($busqueda));

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower(trim($sheet->getTitle())) === $busquedaNorm) {
                return $sheet;
            }
        }

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower($sheet->getTitle()) !== '' &&
                mb_strpos(mb_strtolower($sheet->getTitle()), $busquedaNorm) !== false) {
                return $sheet;
            }
        }

        $nombres = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $nombres[] = $sheet->getTitle();
        }

        throw new \RuntimeException(
            "No se encontró la hoja: {$busqueda}. Hojas disponibles: ".implode(' | ', $nombres)
        );
    }

    private function parsearPeriodo(string $texto): array
    {
        if (!preg_match('/(\d{4}-\d{2}-\d{2})\s*~\s*(\d{4}-\d{2}-\d{2})/', $texto, $m)) {
            throw new \RuntimeException('No se pudo leer el periodo: '.$texto);
        }
        return [Carbon::parse($m[1])->startOfDay(), Carbon::parse($m[2])->startOfDay()];
    }

    private function celdaTextoFormateada($ws, int $col, int $row): string
    {
        $cell = $ws->getCell([$col, $row]);
        $v = $cell->getValue();

        // RichText -> string
        if ($v instanceof RichText) {
            $v = $v->getPlainText();
        }

        // Si Excel lo devuelve como DateTime
        if ($v instanceof \DateTimeInterface) {
            return $this->normExcel($v->format('H:i'));
        }

        // Si viene como número (serial de Excel)
        if (is_numeric($v)) {
            $n = (float)$v;

            // Caso típico: hora sola (0..1)
            // Ej: 0.3333 = 08:00
            if ($n >= 0 && $n < 1) {
                try {
                    return $this->normExcel(ExcelDate::excelToDateTimeObject($n)->format('H:i'));
                } catch (\Throwable $e) {
                    // fallback abajo
                }
            }

            // Si no es hora sola, regresa el número (por si lo ocupas)
            return $this->normExcel((string)$v);
        }

        // Texto normal
        $s = (string)($v ?? '');
        if ($s === '') {
            // fallback
            $s = (string)($cell->getFormattedValue() ?? '');
        }

        return $this->normExcel($s);
    }

    private function extraerHoras(string $texto): array
    {
        preg_match_all('/(?:[01]?\d|2[0-3]):[0-5]\d/', $texto, $m);
        return $m[0] ?? [];
    }

    private function normExcel(string $s): string
    {
        // NBSP (espacio “duro”) + zero-width space
        $s = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], ' ', $s);

        // normaliza espacios
        $s = preg_replace('/[[:space:]]+/u', ' ', $s);

        return trim($s);
    }

    private function normId(string $s): string
    {
        $s = $this->normExcel($s);
        // en IDs quitamos espacios completamente
        return preg_replace('/\s+/u', '', $s);
    }
}









// =========================================================================================================================
    VERSION 2 - NO PASO PRUEBAS DE IMPORTACION, MALA ESTRUCTURA Y MALA ESCALABILIDAD 
// =========================================================================================================================
<?php

namespace App\Services;

use App\Models\Estudiante; //Modelo de la tabla estudiantes, para obtener: IDs con pluck('id','numero_control')
use App\Models\ImportacionAsistencia; //Modelo para: crear el registro de auditoría de esta importacion
use App\Models\Periodo; //Para: firstOrCreate del periodo
use App\Models\RelojChecador; //Para: obtener/crear el reloj
use Carbon\Carbon; //Librería de fechas,se usa para: parsear el periodo, sumar dias, construir fechas con hora
use Illuminate\Database\QueryException; //Para atrapar el error de BD cuando se intenta insertar un duplicado (error 1062)
use Illuminate\Support\Facades\DB; //Para transacciones y para usar: Query Builder (DB::table(...)) y SQL (DB::affectingStatement(...)).
use PhpOffice\PhpSpreadsheet\Cell\Coordinate; //Para convertir columna “A / B / O / AA” a numero de columna (1, 2, 15, 27). Ademas senecesita para detectar cuantas columnas reales trae el Excel (y asi quitar el hardcode de 15 dias) lo cual limitaba
use PhpOffice\PhpSpreadsheet\IOFactory; //Para cargar el Excel: IOFactory::load($rutaArchivo).

class ImportarAsistenciaExcelService
{
    //En vez de insertar 50,000 filas en un solo insert (pesado), las mete de 1000 en 1000, para mejorar el rendimeinto y evitar limites del motor
    private int $chunkSize = 1000;///chunkSize = tamaño de lote


    public function importar(string $rutaArchivo, ?int $relojId = null, ?int $importadoPor = null): array
    {
        // =========================
        // 1) LEER EXCEL (SIN DB)
        // =========================
        $reader = IOFactory::createReaderForFile($rutaArchivo);
        //$reader->setReadDataOnly(true); // no estilos, menos RAM //ESTE PROVOCA QUE LAS MARCACIONES DE ASISTENCIA NO SE REGISTREN, sin el funciona
        $reader->setLoadSheetsOnly(['Reporte de Turnos', 'Reporte de Asistencia']); // solo las 2 hojas
        $spreadsheet = $reader->load($rutaArchivo);

        //Entontrar las hojas a utilizar en el Excel
        $wsTurnos     = $this->buscarHoja($spreadsheet, 'Reporte de Turnos');
        $wsAsistencia = $this->buscarHoja($spreadsheet, 'Reporte de Asistencia');


        //LEER PERIODO Y CALCULAR DIAS. Turnos C2: "2025-08-01 ~ 2025-08-15"
        $textoPeriodo = $this->celdaTextoFormateada($wsTurnos, 3, 2);
        [$inicio, $fin] = $this->parsearPeriodo($textoPeriodo);

        //lee C,2 y saca fechas
        $diasPeriodo = $inicio->diffInDays($fin) + 1;
        //protege de errores de Excel o intentos de sobrecargar el modulo por periodos exagerados (no menor a 1 dia o mayor a 31 dias, que es un maximo de un mes)
        if ($diasPeriodo < 1 || $diasPeriodo > 31) {
            throw new \RuntimeException("Periodo inválido: {$diasPeriodo} días de lo permitido. Verifica que el rango del periodo del Excel no sea menor a 1 día o mayor a 31 días (un mes).");
        }


        //DETECTAR CUANTOS DIAS VIENEN EN EL EXCEL
        $diasTurnos = $this->diasDisponiblesEnTurnos($wsTurnos, $diasPeriodo);
        $diasAsis   = $this->diasDisponiblesEnAsistencia($wsAsistencia, $diasPeriodo);


        //LEER LOS TURNOS (alumnos + fechas esperadas)
        // turnos[] = ['numero_control', 'reloj_usuario_id', 'fechas_esperadas'=>[Y-m-d...]]
        [$turnos, $totalDiasEsperados] = $this->leerTurnos($wsTurnos, $inicio, $diasTurnos);


        //LEER ASISTENCIA (marcaciones crudas)
        // marcacionesCrudas[] = ['reloj_usuario_id','numero_control','ocurrio_en','celda_cruda']
        $marcacionesCrudas = $this->leerAsistencia($wsAsistencia, $inicio, $diasAsis);

        // Lista unica de numeros de control (por si hay en asistencia pero no en turnos)
        $controles = [];
        foreach ($turnos as $t) {
            if ($t['numero_control'] !== '') $controles[$t['numero_control']] = true;
        }
        foreach ($marcacionesCrudas as $m) {
            if ($m['numero_control'] !== '') $controles[$m['numero_control']] = true;
        }
        $controles = array_keys($controles);


        // ========================
        // 2) DB (TODO EN TRANSACCION)
        // =========================
        return DB::transaction(function () use (
            $rutaArchivo,
            $relojId,
            $importadoPor,
            $inicio,
            $fin,
            $controles,
            $turnos,
            $marcacionesCrudas,
            $totalDiasEsperados
        ) {
            $now = now();

            // Periodo
            $periodo = Periodo::firstOrCreate(
                ['fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $fin->toDateString()],
                ['nombre' => $inicio->toDateString().' a '.$fin->toDateString(), 'activo' => false]
            );

            // Reloj
            $reloj = $relojId
                ? RelojChecador::findOrFail($relojId)
                : RelojChecador::firstOrCreate(['nombre' => 'Reloj Principal'], ['activo' => true]);

            // Crear importacion (anti-duplicado real por UNIQUE; sin race condition)
            $hash = hash_file('sha256', $rutaArchivo);
            try {
                $importacion = ImportacionAsistencia::create([
                    'reloj_checador_id' => $reloj->id,
                    'periodo_id'        => $periodo->id,
                    'archivo_nombre'    => basename($rutaArchivo),
                    'archivo_hash'      => $hash,
                    'importado_por'     => $importadoPor,
                    'importado_en'      => $now,
                    'estado'            => 'OK',
                    'notas'             => null,
                ]);
            } catch (QueryException $e) {
                // MySQL duplicate key => 1062
                if (($e->errorInfo[1] ?? null) === 1062) {
                    return [
                        'ok' => false,
                        'mensaje' => 'Este archivo ya fue importado antes para ese periodo y reloj.',
                    ];
                }
                throw $e;
            }


            // =========================
            // A) ESTUDIANTES (BULK)
            // =========================
            $estRows = [];
            foreach ($controles as $nc) {
                $estRows[] = [
                    'numero_control'  => $nc,
                    'nombre_completo' => null,
                    'created_at'      => $now,
                    'updated_at'      => $now,
                ];
            }
            $estudiantesInsertados = $this->insertOrIgnoreChunk('estudiantes', $estRows);

            // Mapa numero_control => estudiante_id
            $mapControlToId = Estudiante::whereIn('numero_control', $controles)
                ->pluck('id', 'numero_control')
                ->all();


            // =========================
            // B) INSCRIPCIONES RELOJ (UPSERT)
            // =========================
            $insRows = [];
            $mapRelojUsuarioToEstId = []; // para resolver marcaciones rapido

            foreach ($turnos as $t) {
                $nc  = $t['numero_control'];
                $rid = $t['reloj_usuario_id'];

                if ($nc === '' || $rid === '') continue;

                $estId = $mapControlToId[$nc] ?? null;
                if (!$estId) continue;

                $insRows[] = [
                    'reloj_checador_id' => $reloj->id,
                    'reloj_usuario_id'  => $rid,
                    'estudiante_id'     => $estId,
                    'activo'            => true,
                    'created_at'        => $now,
                    'updated_at'        => $now,
                ];

                $mapRelojUsuarioToEstId[$rid] = $estId;
            }

            if (!empty($insRows)) {
                DB::table('reloj_inscripciones')->upsert(
                    $insRows,
                    ['reloj_checador_id', 'reloj_usuario_id'],
                    ['estudiante_id', 'activo', 'updated_at']
                );
            }

            // =========================
            // C) ASISTENCIA_DIARIA (UPSERT) SOLO DIAS ESPERADOS
            // =========================
            $adRows = [];
            foreach ($turnos as $t) {
                $nc = $t['numero_control'];
                $estId = $mapControlToId[$nc] ?? null;
                if (!$estId) continue;

                foreach ($t['fechas_esperadas'] as $fecha) {
                    $adRows[] = [
                        'estudiante_id'      => $estId,
                        'periodo_id'         => $periodo->id,
                        'fecha'              => $fecha,
                        'esperado'           => true,
                        'estatus'            => 'FALTA',   // base: luego se “cocina”
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

            if (!empty($adRows)) {
                // upsert por uq_asistencia_dia
                DB::table('asistencia_diaria')->upsert(
                    $adRows,
                    ['estudiante_id', 'periodo_id', 'fecha'],
                    ['esperado', 'estatus', 'fuente', 'importacion_id', 'updated_at']
                );
            }

            // =========================
            // D) MARCACIONES (INSERT IGNORE) + CHUNKS
            // =========================
            $marRows = [];
            foreach ($marcacionesCrudas as $m) {
                $estId = null;

                // Prioridad: mapeo por reloj_usuario_id si existe
                if ($m['reloj_usuario_id'] !== '' && isset($mapRelojUsuarioToEstId[$m['reloj_usuario_id']])) {
                    $estId = $mapRelojUsuarioToEstId[$m['reloj_usuario_id']];
                } else {
                    // fallback: numero_control
                    $nc = $m['numero_control'];
                    $estId = $mapControlToId[$nc] ?? null;
                }

                if (!$estId) continue;

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

            // =========================
            // E) “COCINAR” asistencia_diaria EN SQL (UN SOLO GOLPE)
            // =========================
            $inicioDT = $inicio->copy()->startOfDay()->toDateTimeString();
            $finDT    = $fin->copy()->endOfDay()->toDateTimeString();

            // Nota: aqui NO filtramos por importacion_id, porque si ya existan marcaciones previas
            // (mismo periodo/rango), deben contar para que el cocinado sea correcto.
            $sql = "
                UPDATE asistencia_diaria a
                LEFT JOIN (
                    SELECT
                        estudiante_id,
                        DATE(ocurrio_en) AS fecha,
                        COUNT(*) AS conteo,
                        MIN(ocurrio_en) AS primera,
                        MAX(ocurrio_en) AS ultima
                    FROM marcaciones_asistencia
                    WHERE reloj_checador_id = ?
                        AND ocurrio_en BETWEEN ? AND ?
                    GROUP BY estudiante_id, DATE(ocurrio_en)
                ) m ON m.estudiante_id = a.estudiante_id AND m.fecha = a.fecha
                SET
                    a.conteo_marcaciones = IFNULL(m.conteo, 0),
                    a.primera_entrada    = m.primera,
                    a.ultima_salida      = m.ultima,
                    a.estatus            = CASE WHEN IFNULL(m.conteo, 0) > 0 THEN 'PRESENTE' ELSE 'FALTA' END,
                    a.fuente             = 'RELOJ',
                    a.importacion_id     = ?,
                    a.updated_at         = NOW()
                WHERE a.periodo_id = ?
                    AND a.esperado = 1
            ";

            $diasActualizados = DB::affectingStatement($sql, [
                $reloj->id,
                $inicioDT,
                $finDT,
                $importacion->id,
                $periodo->id,
            ]);

            return [
                'ok' => true,
                'mensaje' => 'Importación completada.',
                'periodo' => $periodo->nombre,
                'dias_periodo' => $inicio->diffInDays($fin) + 1,
                'estudiantes_insertados' => $estudiantesInsertados,
                'marcaciones_insertadas' => $marcacionesInsertadas,
                'dias_esperados_detectados' => $totalDiasEsperados,
                'dias_actualizados' => $diasActualizados,
            ];
        });


    }


    // =========================
    // LECTURA EXCEL (DINAMICA)
    // =========================

    private function diasDisponiblesEnTurnos($ws, int $diasPeriodo): int
    {
        // Turnos: dias empiezan en columna D,4. Entonces diasDisponibles = highestColIndex - 3
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestColumn());
        $diasEnExcel = max(0, $highestCol - 3);
        return max(1, min($diasPeriodo, $diasEnExcel));
    }

    private function diasDisponiblesEnAsistencia($ws, int $diasPeriodo): int
    {
        // Asistencia: dias empiezan en columna A,1. Entonces diasDisponibles = highestColIndex
        $highestCol = Coordinate::columnIndexFromString($ws->getHighestColumn());
        $diasEnExcel = max(0, $highestCol);
        return max(1, min($diasPeriodo, $diasEnExcel));
    }

    private function leerTurnos($wsTurnos, Carbon $inicio, int $dias): array
    {
        $turnos = [];
        $totalDiasEsperados = 0;

        $fila = 5; // donde empiezan alumnos

        while (true) {
            // IMPORTANTE: formatted para no perder ceros
            $relojUsuarioId = $this->celdaTextoFormateada($wsTurnos, 1, $fila); // A
            $numeroControl  = $this->celdaTextoFormateada($wsTurnos, 2, $fila); // B

            if ($relojUsuarioId === '' && $numeroControl === '') {
                break;
            }

            if ($numeroControl === '') {
                $fila++;
                continue;
            }

            $fechasEsperadas = [];

            // Dias: D..(D + dias - 1)
            for ($dia = 1; $dia <= $dias; $dia++) {
                $colDia = 3 + $dia; // 4..(3+dias)
                $valor = $wsTurnos->getCell([$colDia, $fila])->getValue();

                if ((string)$valor === '1' || $valor === 1) {
                    $fecha = (clone $inicio)->addDays($dia - 1)->toDateString();
                    $fechasEsperadas[] = $fecha;
                    $totalDiasEsperados++;
                }
            }

            $turnos[] = [
                'numero_control'   => $numeroControl,
                'reloj_usuario_id' => $relojUsuarioId,
                'fechas_esperadas' => $fechasEsperadas,
            ];

            $fila++;
        }

        return [$turnos, $totalDiasEsperados];
    }

    private function leerAsistencia($wsAsistencia, Carbon $inicio, int $dias): array
    {
        $maxRow = $wsAsistencia->getHighestRow();
        //li el numero de filas a procesar (anti Excel “inflado”)
        if ($maxRow > 10000) {
            throw new \RuntimeException("El Excel trae demasiadas filas ({$maxRow}). Verifica que sea el formato correcto.");
        }

        $out = [];

        for ($r = 1; $r <= $maxRow; $r++) {
            $marca = $this->celdaTextoFormateada($wsAsistencia, 1, $r); // col A
            if ($marca !== 'ID:') continue;

            $relojUsuarioId = $this->celdaTextoFormateada($wsAsistencia, 5, $r);   // E
            $numeroControl  = $this->celdaTextoFormateada($wsAsistencia, 11, $r);  // K

            $filaHoras = $r + 1;

            for ($dia = 1; $dia <= $dias; $dia++) {
                $celdaCruda = $this->celdaTextoFormateada($wsAsistencia, $dia, $filaHoras);
                if ($celdaCruda === '') continue;

                $horas = $this->extraerHoras($celdaCruda);
                if (empty($horas)) continue;

                $horas = array_values(array_unique($horas)); // evita repetidas dentro de misma celda
                $fecha = (clone $inicio)->addDays($dia - 1)->toDateString();

                foreach ($horas as $hhmm) {
                    $dt = Carbon::createFromFormat('Y-m-d H:i', $fecha.' '.$hhmm)->seconds(0);

                    $out[] = [
                        'reloj_usuario_id' => $relojUsuarioId,
                        'numero_control'   => $numeroControl,
                        'ocurrio_en'       => $dt->toDateTimeString(),
                        'celda_cruda'      => mb_substr($celdaCruda, 0, 255),
                    ];
                }
            }
        }

        return $out;
    }


    // =========================
    // DB HELPERS (BULK)
    // =========================

    private function insertOrIgnoreChunk(string $table, array $rows): int
    {
        if (empty($rows)) return 0;

        $inserted = 0;
        foreach (array_chunk($rows, $this->chunkSize) as $chunk) {
            $inserted += DB::table($table)->insertOrIgnore($chunk);
        }
        return $inserted;
    }


    // =========================
    // UTILIDADES
    // =========================

    private function buscarHoja($spreadsheet, string $busqueda)
    {
        $busquedaNorm = mb_strtolower(trim($busqueda));

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower(trim($sheet->getTitle())) === $busquedaNorm) {
                return $sheet;
            }
        }

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower($sheet->getTitle()) !== '' &&
                mb_strpos(mb_strtolower($sheet->getTitle()), $busquedaNorm) !== false) {
                return $sheet;
            }
        }

        $nombres = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $nombres[] = $sheet->getTitle();
        }

        throw new \RuntimeException(
            "No se encontró la hoja: {$busqueda}. Hojas disponibles: ".implode(' | ', $nombres)
        );
    }

    private function parsearPeriodo(string $texto): array
    {
        if (!preg_match('/(\d{4}-\d{2}-\d{2})\s*~\s*(\d{4}-\d{2}-\d{2})/', $texto, $m)) {
            throw new \RuntimeException('No se pudo leer el periodo: '.$texto);
        }
        return [Carbon::parse($m[1])->startOfDay(), Carbon::parse($m[2])->startOfDay()];
    }

    private function celdaTextoFormateada($ws, int $col, int $row): string
    {
        $cell = $ws->getCell([$col, $row]);
        $v = $cell->getFormattedValue();
        return trim((string)($v ?? ''));
    }

    private function extraerHoras(string $texto): array
    {
        preg_match_all('/(?:[01]?\d|2[0-3]):[0-5]\d/', $texto, $m);
        return $m[0] ?? [];
    }
}










// =========================================================================================================================
    VERSION 1 - NO PASO PRUEBAS DE IMPORTACION, MALA ESTRUCTURA Y MALA ESCALABILIDAD  
// =========================================================================================================================
<?php

namespace App\Services;

use App\Models\AsistenciaDiaria;
use App\Models\Estudiante;
use App\Models\ImportacionAsistencia;
use App\Models\MarcacionAsistencia;
use App\Models\Periodo;
use App\Models\RelojChecador;
use App\Models\RelojInscripcion;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ImportarAsistenciaExcelService
{
    public function importar(string $rutaArchivo, ?int $relojId = null, ?int $importadoPor = null): array
    {
        return DB::transaction(function () use ($rutaArchivo, $relojId, $importadoPor) {

            $spreadsheet = IOFactory::load($rutaArchivo);

            $wsTurnos = $this->buscarHoja($spreadsheet, 'Reporte de Turnos');
            $wsAsistencia = $this->buscarHoja($spreadsheet, 'Reporte de Asistencia');

            // 1) Periodo desde Turnos!C2: "2025-08-01 ~ 2025-08-15"
            $textoPeriodo = $this->celdaTexto($wsTurnos, 3, 2); // C2
            [$inicio, $fin] = $this->parsearPeriodo($textoPeriodo);

            $periodo = Periodo::firstOrCreate(
                ['fecha_inicio' => $inicio->toDateString(), 'fecha_fin' => $fin->toDateString()],
                ['nombre' => $inicio->toDateString().' a '.$fin->toDateString(), 'activo' => false]
            );


            // 2) Reloj (si no existe, crea uno)
            $reloj = $relojId
                ? RelojChecador::findOrFail($relojId)
                : RelojChecador::firstOrCreate(['nombre' => 'Reloj Principal'], ['activo' => true]);


            // 3) Crear Importacion (evitar duplicados)
            $hash = hash_file('sha256', $rutaArchivo);

            $yaExiste = ImportacionAsistencia::where('reloj_checador_id', $reloj->id)
                ->where('periodo_id', $periodo->id)
                ->where('archivo_hash', $hash)
                ->exists();

            if ($yaExiste) {
                return [
                    'ok' => false,
                    'mensaje' => 'Este archivo ya fue importado antes para ese periodo y reloj.',
                ];
            }

            $importacion = ImportacionAsistencia::create([
                'reloj_checador_id' => $reloj->id,
                'periodo_id' => $periodo->id,
                'archivo_nombre' => basename($rutaArchivo),
                'archivo_hash' => $hash,
                'importado_por' => $importadoPor,
                'importado_en' => now(),
                'estado' => 'OK',
                'notas' => null,
            ]);

            // === Paso A: Leer Turnos (crear estudiantes + mapeo + dias esperados) ===
            $mapRelojAEstudiante = [];         // reloj_usuario_id => estudiante_id
            $diasEsperados = [];               // [estudiante_id][Y-m-d] = true

            $fila = 5; // en tu Excel, aquí empiezan los alumnos
            $creadosEstudiantes = 0;

            while (true) {
                $relojUsuarioId = $this->celdaTexto($wsTurnos, 1, $fila); // A
                $numeroControl  = $this->celdaTexto($wsTurnos, 2, $fila); // B

                if ($relojUsuarioId === '' && $numeroControl === '') {
                    break;
                }

                if ($numeroControl === '') {
                    $fila++;
                    continue;
                }

                $estudiante = Estudiante::firstOrCreate(
                    ['numero_control' => $numeroControl],
                    ['nombre_completo' => null]
                );

                if ($estudiante->wasRecentlyCreated) $creadosEstudiantes++;

                if ($relojUsuarioId !== '') {
                    RelojInscripcion::updateOrCreate(
                        ['reloj_checador_id' => $reloj->id, 'reloj_usuario_id' => $relojUsuarioId],
                        ['estudiante_id' => $estudiante->id, 'activo' => true]
                    );
                    $mapRelojAEstudiante[$relojUsuarioId] = $estudiante->id;
                }

                // Columnas de dias en Turnos: dia 1 está en columna 4 (D)
                for ($dia = 1; $dia <= 15; $dia++) {
                    $colDia = 3 + $dia; // 4..18
                    $valor = $this->celdaValor($wsTurnos, $colDia, $fila);

                    if ((string)$valor === '1' || $valor === 1) {
                        $fecha = (clone $inicio)->addDays($dia - 1)->toDateString();
                        $diasEsperados[$estudiante->id][$fecha] = true;

                        // Creamos provisionalmente el dia como FALTA (se vuelve PRESENTE si hay marcaciones)
                        AsistenciaDiaria::updateOrCreate(
                            ['estudiante_id' => $estudiante->id, 'periodo_id' => $periodo->id, 'fecha' => $fecha],
                            [
                                'esperado' => true, 
                                'estatus' => 'FALTA', 
                                'fuente' => 'RELOJ', 
                                'importacion_id' => $importacion->id
                            ]
                        );
                    }
                }

                $fila++;
            }

            // === Paso B: Leer Asistencia (crear marcaciones) ===
            $maxRow = $wsAsistencia->getHighestRow();

            $marcacionesInsertadas = 0;
            $stats = []; // [estudiante_id][Y-m-d] => ['count'=>, 'min'=>Carbon, 'max'=>Carbon]
            $vistos = []; // [estudianteId][fecha][datetime] = true (para no contar repetidos)

            // Escanear todas las filas y detectar donde dice "ID:"
            for ($r = 1; $r <= $maxRow; $r++) {
                $marca = $this->celdaTextoFormateada($wsAsistencia, 1, $r); // col A
                if ($marca !== 'ID:') continue;

                $relojUsuarioId = $this->celdaTextoFormateada($wsAsistencia, 5, $r);   // col E
                $numeroControl  = $this->celdaTextoFormateada($wsAsistencia, 11, $r);  // col K

                $estudianteId = $mapRelojAEstudiante[$relojUsuarioId] ?? null;

                if (!$estudianteId && $numeroControl !== '') {
                    $estudianteId = Estudiante::where('numero_control', $numeroControl)->value('id');
                }
                if (!$estudianteId) continue;

                $filaHoras = $r + 1;

                // Días 1..15 = columnas 1..15 (A..O)
                for ($dia = 1; $dia <= 15; $dia++) {
                    $celdaCruda = $this->celdaTextoFormateada($wsAsistencia, $dia, $filaHoras);
                    if ($celdaCruda === '') continue;

                    $horas = $this->extraerHoras($celdaCruda);
                    if (empty($horas)) continue;

                    $horas = array_values(array_unique($horas)); //evita horas repetidas en la misma celda

                    $fecha = (clone $inicio)->addDays($dia - 1)->toDateString();

                    foreach ($horas as $hhmm) {
                        $dt = Carbon::createFromFormat('Y-m-d H:i', $fecha.' '.$hhmm);
                        $key = $dt->toDateTimeString();

                        if (isset($vistos[$estudianteId][$fecha][$key])) {
                            continue; // ya se conto esta misma hora para ese estudiante y ese dia
                        }
                        
                        $vistos[$estudianteId][$fecha][$key] = true;

                        $m = MarcacionAsistencia::updateOrCreate(
                            [
                                'estudiante_id' => $estudianteId,
                                'reloj_checador_id' => $reloj->id,
                                'ocurrio_en' => $key,
                            ],
                            [
                                'importacion_id' => $importacion->id,
                                'celda_cruda' => mb_substr($celdaCruda, 0, 255),
                            ]
                        );

                        if ($m->wasRecentlyCreated) $marcacionesInsertadas++;

                        $stats[$estudianteId][$fecha]['count'] = ($stats[$estudianteId][$fecha]['count'] ?? 0) + 1;

                        if (!isset($stats[$estudianteId][$fecha]['min']) || $dt->lt($stats[$estudianteId][$fecha]['min'])) {
                            $stats[$estudianteId][$fecha]['min'] = $dt;
                        }
                        if (!isset($stats[$estudianteId][$fecha]['max']) || $dt->gt($stats[$estudianteId][$fecha]['max'])) {
                            $stats[$estudianteId][$fecha]['max'] = $dt;
                        }
                    }
                }
            }

            // === Paso C: “Cocinar” asistencia_diaria (solo días esperados) ===
            $diasActualizados = 0;

            foreach ($diasEsperados as $estudianteId => $fechas) {
                foreach (array_keys($fechas) as $fecha) {
                    $info = $stats[$estudianteId][$fecha] ?? null;

                    $conteo = $info['count'] ?? 0;
                    $estatus = $conteo > 0 ? 'PRESENTE' : 'FALTA';

                    AsistenciaDiaria::where('estudiante_id', $estudianteId)
                        ->where('periodo_id', $periodo->id)
                        ->where('fecha', $fecha)
                        ->update([
                            'estatus' => $estatus,
                            'fuente' => 'RELOJ',
                            'importacion_id' => $importacion->id,
                            'conteo_marcaciones' => $conteo,
                            'primera_entrada' => $conteo > 0 ? $info['min']->toDateTimeString() : null,
                            'ultima_salida' => $conteo > 0 ? $info['max']->toDateTimeString() : null,
                        ]);

                    $diasActualizados++;
                }
            }

            return [
                'ok' => true,
                'mensaje' => 'Importación completada.',
                'periodo' => $periodo->nombre,
                'estudiantes_creados' => $creadosEstudiantes,
                'marcaciones_insertadas' => $marcacionesInsertadas,
                'dias_actualizados' => $diasActualizados,
            ];
        });
    }

    //Se encarga de buscar las hojas que utilizamos "Reporte de Turnos" y "Reporte de Asistencia", pero en este no busca el nombre exacto, si no que busca el titulo que contiene una palabra que coincida
    //"Reporte de Turnos" --> coincide con "Reporte de Turnos"
    //"Reporte de Asistencia" --> coincide con "Reporte de Asistencia" (tener cuidado en indicar bien el nombre)
    private function buscarHoja($spreadsheet, string $busqueda)
    {
        $busquedaNorm = mb_strtolower(trim($busqueda));

        // 1) Intento exacto (ignorando mayusculas)
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower(trim($sheet->getTitle())) === $busquedaNorm) {
                return $sheet;
            }
        }

        // 2) Respaldo: contiene
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower($sheet->getTitle()) !== '' &&
                mb_strpos(mb_strtolower($sheet->getTitle()), $busquedaNorm) !== false) {
                return $sheet;
            }
        }

        // 3) Si no encuentra, muestra las hojas disponibles (para depurar rapido)
        $nombres = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $nombres[] = $sheet->getTitle();
        }

        throw new \RuntimeException(
            "No se encontró la hoja con dicho nombre. Verifica si no se han hecho cambios en su nombre, y si en dado caso SI, ajustalo (ya sea en el codigo o archivo excel): {$busqueda}. Hojas disponibles: ".implode(' | ', $nombres)
        );
    }



    private function parsearPeriodo(string $texto): array
    {
        // "2025-08-01 ~ 2025-08-15"
        if (!preg_match('/(\d{4}-\d{2}-\d{2})\s*~\s*(\d{4}-\d{2}-\d{2})/', $texto, $m)) {
            throw new \RuntimeException('No se pudo leer el periodo: '.$texto);
        }
        return [Carbon::parse($m[1])->startOfDay(), Carbon::parse($m[2])->startOfDay()];
    }


    private function celdaTextoFormateada($ws, int $col, int $row): string
    {
        $cell = $ws->getCell([$col, $row]);
        $v = $cell->getFormattedValue(); // <- lo que se ve en Excel
        return trim((string)($v ?? ''));
    }


    private function extraerHoras(string $texto): array
    {
        // Detecta HH:MM aunque haya saltos de linea o espacios raros
        preg_match_all('/(?:[01]?\d|2[0-3]):[0-5]\d/', $texto, $m);
        return $m[0] ?? [];
    }


    // Devuelve texto con trim
    private function celdaTexto($ws, int $col, int $row): string
    {
        $v = $ws->getCell([$col, $row])->getValue();
        return trim((string)($v ?? ''));
    }


    // Devuelve valor crudo (puede ser 1, null, etc.)
    private function celdaValor($ws, int $col, int $row)
    {
        return $ws->getCell([$col, $row])->getValue();
    }
}