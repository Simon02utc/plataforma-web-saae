<?php

namespace App\Services\ModuloImportacion\ParsersAsistencia;

use App\Models\Periodo;
use Carbon\Carbon;

class ParserRelojChecadorNuevoModeloArchivoCsvTipoMatriz implements AsistenciaParserInterface
{
    /**
     *  ============================================================
     *  PLANTILLA BASE PARA NUEVOS PARSERS CSV DE TIPO MATRIZ
     *  Una fila por persona, columnas por día.
     *  ============================================================
     *
     *  IMPORTANTE:
     *  - Un CSV normalmente no tiene hojas, solo una tabla.
     *  - Todo suele venir como texto.
     *  - Puede variar el delimitador: coma, punto y coma, tab.
     *  - Puede traer BOM o codificación rara.
     *
     *  ESTA PLANTILLA DEBE ADAPTARSE EN:
     *  1) detectarDelimitador()
     *  2) leerPeriodoDesdeFilas()
     *  3) ubicarColumnasTurnos()
     *  4) ubicarColumnasAsistencia()
     *  5) leerTurnosDesdeFilas()
     *  6) leerAsistenciaDesdeFilas()
     *
     *  NO CAMBIAR SI NO HACE FALTA:
     *  - parsear()
     *  - salida uniforme del return
     *  - validaciones generales del periodo
     */

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
        // 1) LEER CSV
        // =========================
        $delimitador = $this->detectarDelimitador($rutaFisicaArchivo);
        $filas = $this->leerFilasCsv($rutaFisicaArchivo, $delimitador);

        if (empty($filas)) {
            throw new \RuntimeException('El archivo CSV está vacío o no se pudo leer.');
        }

        // En CSV no hay hojas reales; dejamos una hoja lógica
        $hojasDetectadas = ['CSV'];

        // =========================
        // 2) PROCESAR SEGÚN TIPO
        // =========================
        if ($tipoImportacion === 'COMPLETA') {
            [$inicio, $fin] = $this->leerPeriodoDesdeFilas($filas);

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            $metaTurnos = $this->ubicarColumnasTurnos($filas, $inicio, $fin);
            $metaAsis   = $this->ubicarColumnasAsistencia($filas, $inicio, $fin);

            $this->validarMapaDias('CSV Turnos', $metaTurnos, $diasPeriodo, $advertencias);
            $this->validarMapaDias('CSV Asistencia', $metaAsis, $diasPeriodo, $advertencias);

            [$turnos, $totalDiasEsperados, $filasTurnosSinNumeroControl] =
                $this->leerTurnosDesdeFilas($filas, $metaTurnos['mapa']);

            if ($filasTurnosSinNumeroControl > 0) {
                $advertencias[] = "Se omitieron {$filasTurnosSinNumeroControl} fila(s) de turnos sin número de control.";
            }

            $marcacionesCrudas = $this->leerAsistenciaDesdeFilas($filas, $metaAsis['mapa']);

        } elseif ($tipoImportacion === 'SOLO_ASISTENCIA') {
            if (!$periodo) {
                throw new \RuntimeException('Debes seleccionar un periodo para la importación SOLO_ASISTENCIA.');
            }

            $inicio = Carbon::parse($periodo->fecha_inicio)->startOfDay();
            $fin    = Carbon::parse($periodo->fecha_fin)->startOfDay();

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            $metaAsis = $this->ubicarColumnasAsistencia($filas, $inicio, $fin);
            $this->validarMapaDias('CSV Asistencia', $metaAsis, $diasPeriodo, $advertencias);

            $marcacionesCrudas = $this->leerAsistenciaDesdeFilas($filas, $metaAsis['mapa']);

        } elseif ($tipoImportacion === 'SOLO_TURNOS') {
            [$inicio, $fin] = $this->leerPeriodoDesdeFilas($filas);

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            $metaTurnos = $this->ubicarColumnasTurnos($filas, $inicio, $fin);
            $this->validarMapaDias('CSV Turnos', $metaTurnos, $diasPeriodo, $advertencias);

            [$turnos, $totalDiasEsperados, $filasTurnosSinNumeroControl] =
                $this->leerTurnosDesdeFilas($filas, $metaTurnos['mapa']);

            if ($filasTurnosSinNumeroControl > 0) {
                $advertencias[] = "Se omitieron {$filasTurnosSinNumeroControl} fila(s) de turnos sin número de control.";
            }

        } else {
            throw new \RuntimeException('Tipo de importación no válido para este parser CSV.');
        }

        // =========================
        // 3) SALIDA UNIFICADA
        // =========================
        return [
            'hojas_detectadas'     => $hojasDetectadas,
            'advertencias'         => $advertencias,
            'inicio'               => $inicio,
            'fin'                  => $fin,
            'turnos'               => $turnos,
            'marcaciones_crudas'   => $marcacionesCrudas,
            'total_dias_esperados' => $totalDiasEsperados,
        ];
    }


    // ============================================================
    // MÉTODOS A ADAPTAR PARA EL NUEVO RELOJ CSV
    // ============================================================
    protected function leerPeriodoDesdeFilas(array $filas): array
    {
        /**
         * EJEMPLO DEFAULT:
         * Buscar una fila/columna que traiga algo como:
         * 2026-02-20 ~ 2026-02-26
         *
         * Este método debe adaptarse según el formato del CSV real.
         */

        foreach ($filas as $fila) {
            foreach ($fila as $celda) {
                $texto = $this->normTexto($celda);

                if (preg_match('/(\d{4}-\d{2}-\d{2})\s*~\s*(\d{4}-\d{2}-\d{2})/', $texto, $m)) {
                    return [
                        Carbon::parse($m[1])->startOfDay(),
                        Carbon::parse($m[2])->startOfDay(),
                    ];
                }
            }
        }

        throw new \RuntimeException('No se pudo leer el periodo dentro del CSV.');
    }

    protected function ubicarColumnasTurnos(array $filas, Carbon $inicio, Carbon $fin): array
    {
        /**
         *  EJEMPLO DEFAULT:
         *  - la primera fila útil contiene encabezados
         *  - existen columnas por día del periodo
         *  - debes convertir esos encabezados a un mapa:
         *
         *  [
         *   'mapa' => [
         *      ['col' => 4, 'fecha' => '2026-02-20'],
         *      ...
         *   ],
         *   'faltantes' => [...],
         *   'inesperadas' => [...]
         *  ]
         */

        return $this->construirMapaDiasDesdeEncabezado(
            encabezados: $filas[0] ?? [],
            inicio: $inicio,
            fin: $fin,
            colInicio: 0
        );
    }

    protected function ubicarColumnasAsistencia(array $filas, Carbon $inicio, Carbon $fin): array
    {
        return $this->construirMapaDiasDesdeEncabezado(
            encabezados: $filas[0] ?? [],
            inicio: $inicio,
            fin: $fin,
            colInicio: 0
        );
    }

    protected function leerTurnosDesdeFilas(array $filas, array $mapaDias): array
    {
        /**
         *  EJEMPLO DEFAULT:
         *  - fila 0 = encabezados
         *  - una columna contiene reloj_usuario_id
         *  - una columna contiene numero_control
         *  - columnas de días valen 1, X, SI, etc. según el reloj
         */

        $turnos = [];
        $totalDiasEsperados = 0;
        $filasSinNumeroControl = 0;

        $encabezados = $filas[0] ?? [];
        $colRelojUsuarioId = $this->buscarIndiceEncabezado($encabezados, ['reloj_usuario_id', 'id_reloj', 'usuario_id']);
        $colNumeroControl  = $this->buscarIndiceEncabezado($encabezados, ['numero_control', 'num_control', 'matricula']);

        for ($i = 1; $i < count($filas); $i++) {
            $fila = $filas[$i];

            $relojUsuarioId = $this->valorFila($fila, $colRelojUsuarioId);
            $numeroControl  = $this->valorFila($fila, $colNumeroControl);

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

                $valor = $this->valorFila($fila, $col);
                $valorNorm = mb_strtoupper($this->normTexto($valor));

                if (in_array($valorNorm, ['1', 'X', 'SI', 'SÍ', 'TRUE'], true)) {
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

    protected function leerAsistenciaDesdeFilas(array $filas, array $mapaDias): array
    {
        /**
         *  EJEMPLO DEFAULT:
         *  - cada fila representa a una persona
         *  - columnas por día traen horas dentro de la celda
         *  - las horas pueden venir separadas por espacios, coma, salto, etc.
         *
         *  Si el CSV real trae una marcación por fila, este método debe cambiarse.
         */

        $out = [];

        $encabezados = $filas[0] ?? [];
        $colRelojUsuarioId = $this->buscarIndiceEncabezado($encabezados, ['reloj_usuario_id', 'id_reloj', 'usuario_id']);
        $colNumeroControl  = $this->buscarIndiceEncabezado($encabezados, ['numero_control', 'num_control', 'matricula']);

        for ($i = 1; $i < count($filas); $i++) {
            $fila = $filas[$i];

            $relojUsuarioId = $this->valorFila($fila, $colRelojUsuarioId);
            $numeroControl  = $this->valorFila($fila, $colNumeroControl);

            if ($relojUsuarioId === '' && $numeroControl === '') {
                continue;
            }

            foreach ($mapaDias as $item) {
                $col = (int) $item['col'];
                $fecha = (string) $item['fecha'];

                $celdaCruda = $this->valorFila($fila, $col);
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
    // BLOQUES GENÉRICOS REUTILIZABLES
    // ============================================================
    protected function detectarDelimitador(string $rutaFisicaArchivo): string
    {
        $muestra = file_get_contents($rutaFisicaArchivo, false, null, 0, 4096) ?: '';

        $candidatos = [
            ','  => substr_count($muestra, ','),
            ';'  => substr_count($muestra, ';'),
            "\t" => substr_count($muestra, "\t"),
            '|'  => substr_count($muestra, '|'),
        ];

        arsort($candidatos);

        return array_key_first($candidatos) ?: ',';
    }

    protected function leerFilasCsv(string $rutaFisicaArchivo, string $delimitador): array
    {
        $filas = [];

        $handle = fopen($rutaFisicaArchivo, 'r');
        if (!$handle) {
            throw new \RuntimeException('No se pudo abrir el archivo CSV.');
        }

        try {
            while (($fila = fgetcsv($handle, 0, $delimitador)) !== false) {
                $fila = array_map(fn ($v) => $this->limpiarBom($this->normTexto((string) $v)), $fila);

                // omitir fila totalmente vacía
                $noVacia = array_filter($fila, fn ($v) => $v !== '');
                if (empty($noVacia)) {
                    continue;
                }

                $filas[] = array_values($fila);
            }
        } finally {
            fclose($handle);
        }

        return $filas;
    }

    protected function construirMapaDiasDesdeEncabezado(
        array $encabezados,
        Carbon $inicio,
        Carbon $fin,
        int $colInicio = 0
    ): array {
        $fechasEsperadas = $this->fechasDelPeriodo($inicio, $fin);

        $mapa = [];
        $faltantes = [];
        $inesperadas = [];

        $idxEsperado = 0;
        $totalEsperado = count($fechasEsperadas);

        for ($col = $colInicio; $col < count($encabezados); $col++) {
            $diaCabecera = $this->extraerDiaCabeceraTexto($encabezados[$col] ?? '');

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

            for ($j = $idxEsperado; $j < $encontrado; $j++) {
                $faltantes[] = $fechasEsperadas[$j]->toDateString();
            }

            $mapa[] = [
                'col'   => $col,
                'fecha' => $fechasEsperadas[$encontrado]->toDateString(),
            ];

            $idxEsperado = $encontrado + 1;
        }

        for ($j = $idxEsperado; $j < $totalEsperado; $j++) {
            $faltantes[] = $fechasEsperadas[$j]->toDateString();
        }

        return [
            'mapa'        => $mapa,
            'faltantes'   => array_values(array_unique($faltantes)),
            'inesperadas' => $inesperadas,
        ];
    }

    protected function validarDiasPeriodo(int $diasPeriodo): void
    {
        if ($diasPeriodo < 1 || $diasPeriodo > 31) {
            throw new \RuntimeException(
                "Periodo inválido: {$diasPeriodo} días. Verifica que el rango esté entre 1 y 31 días."
            );
        }
    }

    protected function validarMapaDias(
        string $nombreBloque,
        array $meta,
        int $diasPeriodo,
        array &$advertencias
    ): void {
        $procesados = count($meta['mapa'] ?? []);

        if ($procesados < 1) {
            throw new \RuntimeException("El bloque \"{$nombreBloque}\" no contiene columnas de días válidas.");
        }

        $faltantes = $meta['faltantes'] ?? [];
        if (!empty($faltantes)) {
            $advertencias[] =
                "El bloque \"{$nombreBloque}\" no contiene columna(s) para la(s) fecha(s): " .
                implode(', ', $faltantes) .
                ". Solo se procesaron {$procesados} de {$diasPeriodo} días del periodo.";
        } elseif ($procesados < $diasPeriodo) {
            $advertencias[] =
                "El bloque \"{$nombreBloque}\" solo contiene {$procesados} día(s) válido(s), " .
                "pero el periodo tiene {$diasPeriodo} día(s).";
        }

        $inesperadas = $meta['inesperadas'] ?? [];
        if (!empty($inesperadas)) {
            $cols = array_map(function ($x) {
                return 'columna ' . $x['col'] . "({$x['dia']})";
            }, $inesperadas);

            $advertencias[] =
                "En el bloque \"{$nombreBloque}\" se ignoró la(s) columna(s) del día no reconocidas: " .
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

    protected function extraerDiaCabeceraTexto(string $texto): ?int
    {
        $texto = $this->normTexto($texto);

        if ($texto === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}$/', $texto)) {
            $n = (int) $texto;
            return ($n >= 1 && $n <= 31) ? $n : null;
        }

        if (preg_match('/^\d{4}-\d{2}-(\d{2})$/', $texto, $m)) {
            $n = (int) $m[1];
            return ($n >= 1 && $n <= 31) ? $n : null;
        }

        if (preg_match('/^(\d{1,2})[\/\-]\d{1,2}[\/\-]\d{2,4}$/', $texto, $m)) {
            $n = (int) $m[1];
            return ($n >= 1 && $n <= 31) ? $n : null;
        }

        return null;
    }

    protected function buscarIndiceEncabezado(array $encabezados, array $alias): ?int
    {
        $normalizados = array_map(fn ($x) => $this->normalizarEncabezado((string) $x), $encabezados);
        $aliasNorm = array_map(fn ($x) => $this->normalizarEncabezado((string) $x), $alias);

        foreach ($normalizados as $idx => $enc) {
            if (in_array($enc, $aliasNorm, true)) {
                return $idx;
            }
        }

        return null;
    }

    protected function valorFila(array $fila, ?int $indice): string
    {
        if ($indice === null) {
            return '';
        }

        return $this->normTexto((string) ($fila[$indice] ?? ''));
    }

    protected function extraerHoras(string $texto): array
    {
        preg_match_all('/(?:[01]?\d|2[0-3]):[0-5]\d/', $texto, $m);
        return $m[0] ?? [];
    }

    protected function normalizarEncabezado(string $texto): string
    {
        $texto = mb_strtolower($this->normTexto($texto));
        $texto = str_replace(['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'], ['a', 'e', 'i', 'o', 'u', 'u', 'n'], $texto);
        $texto = preg_replace('/[^a-z0-9]+/u', '_', $texto);

        return trim($texto, '_');
    }

    protected function limpiarBom(string $texto): string
    {
        return preg_replace('/^\xEF\xBB\xBF/', '', $texto) ?? $texto;
    }

    protected function normTexto(string $texto): string
    {
        $texto = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], ' ', $texto);
        $texto = preg_replace('/[[:space:]]+/u', ' ', $texto);

        return trim($texto);
    }
}