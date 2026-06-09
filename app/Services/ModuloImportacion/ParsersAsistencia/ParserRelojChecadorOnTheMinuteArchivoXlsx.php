<?php

namespace App\Services\ModuloImportacion\ParsersAsistencia;

use App\Models\Periodo;
use Carbon\Carbon;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ParserRelojChecadorOnTheMinuteArchivoXlsx implements AsistenciaParserInterface
{

    public function parsear(
        string $rutaFisicaArchivo,
        string $tipoImportacion,
        ?Periodo $periodo = null
    ): array {

        $tipoImportacion = strtoupper(trim($tipoImportacion));

        $turnos = [];
        $marcacionesCrudas = [];
        $totalDiasEsperados = 0;
        $advertencias = [];

        // =========================
        // 1) ABRIR ARCHIVO
        // =========================
        $reader = IOFactory::createReaderForFile($rutaFisicaArchivo);
        $reader->setReadDataOnly(false);
        $spreadsheet = $reader->load($rutaFisicaArchivo);

        // =========================
        // 2) DETECTAR HOJAS
        // =========================
        $hojasDetectadas = [];
        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $hojasDetectadas[] = $sheet->getTitle();
        }

        // =========================
        // 3) LOCALIZAR HOJAS
        // =========================    
        $wsTurnos = $this->buscarHojaTurnos($spreadsheet);
        $wsAsistencia = $this->buscarHojaAsistencia($spreadsheet);

        // =========================
        // 4) PROCESAR SEGUN TIPO
        // =========================
        if ($tipoImportacion === 'COMPLETA') {
            if (!$wsTurnos) {
                throw new \RuntimeException('La importación COMPLETA requiere la hoja "Reporte de Turnos".');
            }

            if (!$wsAsistencia) {
                throw new \RuntimeException('La importación COMPLETA requiere la hoja "Reporte de Asistencia".');
            }

            [$inicio, $fin] = $this->leerPeriodoDesdeTurnos($wsTurnos);

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            $metaTurnos = $this->mapearColumnasTurnos($wsTurnos, $inicio, $fin);
            $metaAsis   = $this->mapearColumnasAsistencia($wsAsistencia, $inicio, $fin);

            $this->validarMapaDias('Reporte de Turnos', $metaTurnos, $diasPeriodo, $advertencias);
            $this->validarMapaDias('Reporte de Asistencia', $metaAsis, $diasPeriodo, $advertencias);

            [$turnos, $totalDiasEsperados, $filasTurnosSinNumeroControl] =
                $this->leerTurnos($wsTurnos, $metaTurnos['mapa']);

            if ($filasTurnosSinNumeroControl > 0) {
                $advertencias[] = "Se omitio {$filasTurnosSinNumeroControl} fila(s) en \"Reporte de Turnos\" porque no tenían número de control.";
            }

            $marcacionesCrudas = $this->leerAsistencia($wsAsistencia, $metaAsis['mapa']);

        } elseif ($tipoImportacion === 'SOLO_ASISTENCIA') {
            if (!$wsAsistencia) {
                throw new \RuntimeException('La importación SOLO_ASISTENCIA requiere la hoja "Reporte de Asistencia".');
            }

            if (!$periodo) {
                throw new \RuntimeException('Debes seleccionar un periodo para la importación SOLO_ASISTENCIA.');
            }

            $inicio = Carbon::parse($periodo->fecha_inicio)->startOfDay();
            $fin = Carbon::parse($periodo->fecha_fin)->startOfDay();

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            $metaAsis = $this->mapearColumnasAsistencia($wsAsistencia, $inicio, $fin);
            $this->validarMapaDias('Reporte de Asistencia', $metaAsis, $diasPeriodo, $advertencias);

            $marcacionesCrudas = $this->leerAsistencia($wsAsistencia, $metaAsis['mapa']);

        } elseif ($tipoImportacion === 'SOLO_TURNOS') {
            if (!$wsTurnos) {
                throw new \RuntimeException('La importación SOLO_TURNOS requiere la hoja "Reporte de Turnos".');
            }

            [$inicio, $fin] = $this->leerPeriodoDesdeTurnos($wsTurnos);

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            $metaTurnos = $this->mapearColumnasTurnos($wsTurnos, $inicio, $fin);
            $this->validarMapaDias('Reporte de Turnos', $metaTurnos, $diasPeriodo, $advertencias);

            [$turnos, $totalDiasEsperados, $filasTurnosSinNumeroControl] =
                $this->leerTurnos($wsTurnos, $metaTurnos['mapa']);

            if ($filasTurnosSinNumeroControl > 0) {
                $advertencias[] = "Se omitio {$filasTurnosSinNumeroControl} fila(s) en \"Reporte de Turnos\" porque no tenían número de control.";
            }

        } else {
            throw new \RuntimeException('Tipo de importación no válido para este parser.');
        }

        // =========================
        // 5) DEVOLVER ESTRUCTURA
        // =========================
        return [
            'hojas_detectadas'    => $hojasDetectadas,
            'advertencias'        => $advertencias,
            'inicio'              => $inicio,
            'fin'                 => $fin,
            'turnos'              => $turnos,
            'marcaciones_crudas'  => $marcacionesCrudas,
            'total_dias_esperados'=> $totalDiasEsperados,
        ];
    }


    // ============================================================
    // METODOS DEL RELOJ CHECADOR
    // ============================================================
    protected function buscarHojaTurnos($spreadsheet): ?Worksheet
    {
        return $this->buscarHojaOpcional($spreadsheet, 'Reporte de Turnos');
    }

    protected function buscarHojaAsistencia($spreadsheet): ?Worksheet
    {
        return $this->buscarHojaOpcional($spreadsheet, 'Reporte de Asistencia');
    }

    protected function leerPeriodoDesdeTurnos(Worksheet $wsTurnos): array
    {
        // EJEMPLO DEFAULT:
        // C2 contiene "2026-02-20 ~ 2026-02-26"
        $textoPeriodo = $this->celdaTextoFormateada($wsTurnos, 3, 2);

        return $this->parsearPeriodo($textoPeriodo);
    }


    // ========== MAPEO REAL DE DIAS ==========
    protected function mapearColumnasTurnos(Worksheet $wsTurnos, Carbon $inicio, Carbon $fin): array
    {
        // EJEMPLO DEFAULT:
        // fila 3 = numero de dia
        // dias desde columna D (4)
        return $this->construirMapaDias($wsTurnos, 3, 4, $inicio, $fin);
    }

    protected function mapearColumnasAsistencia(Worksheet $wsAsistencia, Carbon $inicio, Carbon $fin): array
    {   
        // EJEMPLO DEFAULT:
        // fila 4 = numero de dia
        // dias desde columna A (1)
        return $this->construirMapaDias($wsAsistencia, 4, 1, $inicio, $fin);
    }


    // ========== LECTURA DE DATOS ==========
    protected function leerTurnos(Worksheet $wsTurnos, array $mapaDias): array
    {   
        // EJEMPLO DEFAULT:
        // - datos desde fila 5
        //- A = reloj_usuario_id
        // - B = numero_control
        // - columnas dinamicas = dias con valor 1

        $turnos = [];
        $totalDiasEsperados = 0;
        $filasSinNumeroControl = 0;
        $maxRow = $wsTurnos->getHighestRow();

        for ($fila = 5; $fila <= $maxRow; $fila++) {
            $relojUsuarioId = $this->celdaId($wsTurnos, 1, $fila); // A
            $numeroControl  = $this->celdaId($wsTurnos, 2, $fila); // B

            if ($relojUsuarioId === '' && $numeroControl === '') {
                continue;
            }

            if ($numeroControl === '') {
                $filasSinNumeroControl++;
                continue;
            }

            $fechasEsperadas = [];

            foreach ($mapaDias as $item) {
                $col = (int) $item['col'];
                $fecha = (string) $item['fecha'];

                $valor = $wsTurnos->getCell([$col, $fila])->getValue();
                $valorNorm = trim((string) ($valor ?? ''));

                if ($valor === 1 || $valorNorm === '1') {
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

        return [$turnos, $totalDiasEsperados, $filasSinNumeroControl];
    }

    protected function leerAsistencia(Worksheet $wsAsistencia, array $mapaDias): array
    {  
        // EJEMPLO DEFAULT:
        // - detectar fila donde A = "ID:"
        // - E = reloj_usuario_id
        // - K = numero_control
        // - fila siguiente contiene horas por dia

        $maxRow = $wsAsistencia->getHighestRow();

        if ($maxRow > 10000) {
            throw new \RuntimeException(
                "El Excel trae demasiadas filas ({$maxRow}). Verifica que sea el formato correcto."
            );
        }

        $out = [];

        for ($r = 1; $r <= $maxRow; $r++) {
            $marca = mb_strtoupper($this->celdaTextoFormateada($wsAsistencia, 1, $r)); // A
            if ($marca !== 'ID:') {
                continue;
            }

            $relojUsuarioId = $this->celdaId($wsAsistencia, 5, $r); // E
            $numeroControl  = $this->celdaId($wsAsistencia, 11, $r); // K

            if ($relojUsuarioId === '' && $numeroControl === '') {
                continue;
            }

            $filaHoras = $r + 1;

            foreach ($mapaDias as $item) {
                $col = (int) $item['col'];
                $fecha = (string) $item['fecha'];

                $celdaCruda = $this->celdaTextoFormateada($wsAsistencia, $col, $filaHoras);
                if ($celdaCruda === '') {
                    continue;
                }

                $horas = $this->extraerHoras($celdaCruda);
                if (empty($horas)) {
                    continue;
                }

                $horas = array_values(array_unique($horas));

                foreach ($horas as $hhmm) {
                    $dt = Carbon::createFromFormat('Y-m-d G:i', $fecha . ' ' . $hhmm)->seconds(0);

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


    // ============================================================
    // BLOQUES GENERICOS REUTILIZABLES
    // ============================================================
    protected function validarDiasPeriodo(int $diasPeriodo): void
    {
        if ($diasPeriodo < 1 || $diasPeriodo > 31) {
            throw new \RuntimeException(
                "Periodo inválido: {$diasPeriodo} días. Verifica que el rango esté entre 1 y 31 días."
            );
        }
    }

    protected function construirMapaDias(
        Worksheet $ws,
        int $filaCabeceraDia,
        int $colInicio,
        Carbon $inicio,
        Carbon $fin
    ): array {
        $fechasEsperadas = $this->fechasDelPeriodo($inicio, $fin);
        $maxCol = Coordinate::columnIndexFromString($ws->getHighestColumn());

        $mapa = [];
        $faltantes = [];
        $inesperadas = [];

        $idxEsperado = 0;
        $totalEsperado = count($fechasEsperadas);

        for ($col = $colInicio; $col <= $maxCol; $col++) {
            $diaCabecera = $this->extraerDiaCabecera($ws, $col, $filaCabeceraDia);

            if ($diaCabecera === null) {
                continue;
            }

            $encontrado = null;

            for ($j = $idxEsperado; $j < $totalEsperado; $j++) {
                if ((int) $fechasEsperadas[$j]->format('j') === $diaCabecera) {
                    $encontrado = $j;
                    break;
                }
            }

            if ($encontrado === null) {
                $inesperadas[] = [
                    'col' => $col,
                    'dia' => $diaCabecera,
                ];
                continue;
            }

            // Las fechas esperadas que brincamos son faltantes reales
            for ($j = $idxEsperado; $j < $encontrado; $j++) {
                $faltantes[] = $fechasEsperadas[$j]->toDateString();
            }

            $mapa[] = [
                'col'   => $col,
                'fecha' => $fechasEsperadas[$encontrado]->toDateString(),
            ];

            $idxEsperado = $encontrado + 1;
        }

        // Si ya no aparecieron columnas para el resto del periodo
        for ($j = $idxEsperado; $j < $totalEsperado; $j++) {
            $faltantes[] = $fechasEsperadas[$j]->toDateString();
        }

        return [
            'mapa'        => $mapa,
            'faltantes'   => array_values(array_unique($faltantes)),
            'inesperadas' => $inesperadas,
        ];
    }

    protected function validarMapaDias(
        string $nombreHoja,
        array $meta,
        int $diasPeriodo,
        array &$advertencias
    ): void {
        $procesados = count($meta['mapa'] ?? []);

        if ($procesados < 1) {
            throw new \RuntimeException("La hoja \"{$nombreHoja}\" no contiene columnas de días válidas.");
        }

        $faltantes = $meta['faltantes'] ?? [];
        if (!empty($faltantes)) {
            $advertencias[] =
                "La hoja \"{$nombreHoja}\" no contiene columna(s) para la(s) fecha(s): " .
                implode(', ', $faltantes) .
                ". Solo se procesaron {$procesados} de {$diasPeriodo} días del periodo.";
        } elseif ($procesados < $diasPeriodo) {
            $advertencias[] =
                "La hoja \"{$nombreHoja}\" solo contiene {$procesados} día(s) válido(s), " .
                "pero el periodo tiene {$diasPeriodo} día(s).";
        }

        $inesperadas = $meta['inesperadas'] ?? [];
        if (!empty($inesperadas)) {
            $cols = array_map(function ($x) {
                $letra = Coordinate::stringFromColumnIndex((int) $x['col']);
                return "{$letra}({$x['dia']})";
            }, $inesperadas);

            $advertencias[] =
                "En la hoja \"{$nombreHoja}\" se ignoro la(s) columna(s) del día no reconocidas: " .
                implode(', ', $cols) . '.';
        }
    }

    protected function fechasDelPeriodo(Carbon $inicio, Carbon $fin): array
    {
        $out = [];
        $cursor = $inicio->copy()->startOfDay();
        $limite = $fin->copy()->startOfDay();

        while ($cursor->lte($limite)) {
            $out[] = $cursor->copy();
            $cursor->addDay();
        }

        return $out;
    }

    protected function extraerDiaCabecera(Worksheet $ws, int $col, int $row): ?int
    {
        $cell = $ws->getCell([$col, $row]);

        //intentar primero como Excel lo muestra
        $formatted = $this->normExcel((string) ($cell->getFormattedValue() ?? ''));

        if ($formatted !== '') {
            // "20"
            if (preg_match('/^\d{1,2}$/', $formatted)) {
                $n = (int) $formatted;
                return ($n >= 1 && $n <= 31) ? $n : null;
            }

            // "2026-02-20"
            if (preg_match('/^\d{4}-\d{2}-(\d{2})$/', $formatted, $m)) {
                $n = (int) $m[1];
                return ($n >= 1 && $n <= 31) ? $n : null;
            }

            // "20/02/2026" o "20-02-2026"
            if (preg_match('/^(\d{1,2})[\/\-]\d{1,2}[\/\-]\d{2,4}$/', $formatted, $m)) {
                $n = (int) $m[1];
                return ($n >= 1 && $n <= 31) ? $n : null;
            }
        }

        $raw = $cell->getValue();

        if ($raw instanceof \DateTimeInterface) {
            return (int) $raw->format('j');
        }

        if ($raw instanceof RichText) {
            $txt = $this->normExcel($raw->getPlainText());
            if (preg_match('/^\d{1,2}$/', $txt)) {
                $n = (int) $txt;
                return ($n >= 1 && $n <= 31) ? $n : null;
            }
        }

        if (is_numeric($raw)) {
            $n = (float) $raw;
            $int = (int) round($n);

            // caso simple: 20, 21, 22...
            if ($int >= 1 && $int <= 31 && abs($n - $int) < 0.00001) {
                return $int;
            }

            // si fuera serial real de fecha Excel
            try {
                $dt = ExcelDate::excelToDateTimeObject($n);
                $dia = (int) $dt->format('j');
                return ($dia >= 1 && $dia <= 31) ? $dia : null;
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }



    // ========== HELPERS ==========
    protected function buscarHojaOpcional($spreadsheet, string $busqueda)
    {
        $busquedaNorm = mb_strtolower(trim($busqueda));

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            if (mb_strtolower(trim($sheet->getTitle())) === $busquedaNorm) {
                return $sheet;
            }
        }

        foreach ($spreadsheet->getWorksheetIterator() as $sheet) {
            $titulo = mb_strtolower(trim($sheet->getTitle()));

            if ($titulo !== '' && mb_strpos($titulo, $busquedaNorm) !== false) {
                return $sheet;
            }
        }

        return null;
    }

    protected function parsearPeriodo(string $texto): array
    {
        if (!preg_match('/(\d{4}-\d{2}-\d{2})\s*~\s*(\d{4}-\d{2}-\d{2})/', $texto, $m)) {
            throw new \RuntimeException('No se pudo leer el periodo: ' . $texto);
        }

        return [
            Carbon::parse($m[1])->startOfDay(),
            Carbon::parse($m[2])->startOfDay(),
        ];
    }

    protected function celdaTextoFormateada(Worksheet $ws, int $col, int $row): string
    {
        $cell = $ws->getCell([$col, $row]);
        $v = $cell->getValue();

        if ($v instanceof RichText) {
            $v = $v->getPlainText();
        }

        if ($v instanceof \DateTimeInterface) {
            return $this->normExcel($v->format('H:i'));
        }

        if (is_numeric($v)) {
            $n = (float) $v;

            if ($n >= 0 && $n < 1) {
                try {
                    return $this->normExcel(
                        ExcelDate::excelToDateTimeObject($n)->format('H:i')
                    );
                } catch (\Throwable $e) {
                    // fallback abajo
                }
            }

            return $this->normExcel((string) $v);
        }

        $s = (string) ($v ?? '');
        if ($s === '') {
            $s = (string) ($cell->getFormattedValue() ?? '');
        }

        return $this->normExcel($s);
    }

    protected function extraerHoras(string $texto): array
    {
        preg_match_all('/(?:[01]?\d|2[0-3]):[0-5]\d/', $texto, $m);
        return $m[0] ?? [];
    }

    protected function normExcel(string $s): string
    {
        $s = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], ' ', $s);
        $s = preg_replace('/[[:space:]]+/u', ' ', $s);

        return trim($s);
    }

    protected function normId(string $s): string
    {
        $s = $this->normExcel($s);
        return preg_replace('/\s+/u', '', $s);
    }

    protected function celdaId(Worksheet $ws, int $col, int $row): string
    {
        $cell = $ws->getCell([$col, $row]);

        $formatted = $this->normId((string) ($cell->getFormattedValue() ?? ''));
        if ($formatted !== '') {
            return $formatted;
        }

        $raw = $cell->getValue();

        if ($raw instanceof RichText) {
            return $this->normId($raw->getPlainText());
        }

        return $this->normId((string) ($raw ?? ''));
    }
}