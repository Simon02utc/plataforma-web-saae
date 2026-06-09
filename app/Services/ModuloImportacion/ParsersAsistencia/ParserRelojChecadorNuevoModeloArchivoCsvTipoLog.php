<?php

namespace App\Services\ModuloImportacion\ParsersAsistencia;

use App\Models\Periodo;
use Carbon\Carbon;

class ParserRelojChecadorNuevoModeloArchivoCsvTipoLog implements AsistenciaParserInterface
{
    /**
     * ============================================================
     * PLANTILLA BASE PARA PARSERS CSV TIPO LOG DE MARCACIONES
     * ============================================================
     *
     * ESTE TIPO DE CSV NORMALMENTE TRAE:
     * - una fila por marcación
     * - identificador del usuario del reloj
     * - número de control o matrícula (a veces sí, a veces no)
     * - fecha y hora separadas, o una sola columna datetime
     *
     * EJEMPLOS COMUNES:
     *
     * reloj_usuario_id,numero_control,fecha,hora
     * 1001,22100045,2026-02-20,07:58
     * 1001,22100045,2026-02-20,14:03
     *
     * o:
     *
     * reloj_usuario_id,numero_control,ocurrio_en
     * 1001,22100045,2026-02-20 07:58:00
     *
     * ============================================================
     * ¿CUÁNDO USAR ESTA PLANTILLA?
     * ============================================================
     *
     * Úsala cuando el reloj exporte una lista plana de eventos,
     * no una matriz por días.
     *
     * Esta plantilla está pensada principalmente para:
     * - SOLO_ASISTENCIA
     *
     * Puede adaptarse a:
     * - COMPLETA
     * - SOLO_TURNOS
     *
     * pero SOLO si ese CSV trae además información de turnos
     * o días esperados, lo cual NO es lo más común.
     *
     * ============================================================
     * ¿QUÉ DEBES ADAPTAR PARA UN RELOJ NUEVO?
     * ============================================================
     *
     * 1) leerPeriodoDesdeFilas()
     * 2) detectarFilaEncabezados()
     * 3) ubicarColumnasLog()
     * 4) leerAsistenciaDesdeLog()
     * 5) leerTurnosDesdeLog()   (solo si el CSV también trae turnos)
     *
     * ============================================================
     * ¿QUÉ NO DEBERÍAS CAMBIAR CASI NUNCA?
     * ============================================================
     *
     * - parsear()
     * - estructura uniforme del return
     * - helpers de delimitador, limpieza y normalización
     * - parseo genérico de fecha/hora
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

        $hojasDetectadas = ['CSV LOG'];

        // =========================
        // 2) DETECTAR ENCABEZADOS
        // =========================
        $filaEncabezados = $this->detectarFilaEncabezados($filas);
        if ($filaEncabezados === null) {
            throw new \RuntimeException(
                'No se pudo detectar la fila de encabezados del CSV tipo log.'
            );
        }

        $encabezados = $filas[$filaEncabezados];
        $metaColumnas = $this->ubicarColumnasLog($encabezados);

        // =========================
        // 3) PROCESAR SEGÚN TIPO
        // =========================
        if ($tipoImportacion === 'SOLO_ASISTENCIA') {
            if (!$periodo) {
                throw new \RuntimeException(
                    'Debes seleccionar un periodo para la importación SOLO_ASISTENCIA.'
                );
            }

            $inicio = Carbon::parse($periodo->fecha_inicio)->startOfDay();
            $fin    = Carbon::parse($periodo->fecha_fin)->startOfDay();

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            $marcacionesCrudas = $this->leerAsistenciaDesdeLog(
                $filas,
                $filaEncabezados,
                $metaColumnas,
                $inicio,
                $fin,
                $advertencias
            );

        } elseif ($tipoImportacion === 'COMPLETA') {
            /**
             * ADVERTENCIA IMPORTANTE:
             * Un CSV tipo log normalmente NO trae turnos.
             * Si un proveedor sí mete turnos en el mismo CSV,
             * entonces este método puede adaptarse.
             */
            [$inicio, $fin] = $this->leerPeriodoDesdeFilas($filas, $periodo);

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            [$turnos, $totalDiasEsperados] = $this->leerTurnosDesdeLog(
                $filas,
                $filaEncabezados,
                $metaColumnas,
                $inicio,
                $fin,
                $advertencias
            );

            $marcacionesCrudas = $this->leerAsistenciaDesdeLog(
                $filas,
                $filaEncabezados,
                $metaColumnas,
                $inicio,
                $fin,
                $advertencias
            );

        } elseif ($tipoImportacion === 'SOLO_TURNOS') {
            [$inicio, $fin] = $this->leerPeriodoDesdeFilas($filas, $periodo);

            $diasPeriodo = $inicio->diffInDays($fin) + 1;
            $this->validarDiasPeriodo($diasPeriodo);

            [$turnos, $totalDiasEsperados] = $this->leerTurnosDesdeLog(
                $filas,
                $filaEncabezados,
                $metaColumnas,
                $inicio,
                $fin,
                $advertencias
            );

        } else {
            throw new \RuntimeException('Tipo de importación no válido para este parser CSV tipo log.');
        }

        // =========================
        // 4) SALIDA UNIFICADA
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
    // MÉTODOS A ADAPTAR PARA CADA RELOJ CSV TIPO LOG
    // ============================================================

    protected function leerPeriodoDesdeFilas(array $filas, ?Periodo $periodo = null): array
    {
        /**
         * CASO MÁS COMÚN EN CSV LOG:
         * el archivo NO trae periodo explícito.
         *
         * Entonces:
         * - si el usuario seleccionó periodo => usar ese
         * - si no => intentar inferirlo de la fecha mínima y máxima del log
         */

        if ($periodo) {
            return [
                Carbon::parse($periodo->fecha_inicio)->startOfDay(),
                Carbon::parse($periodo->fecha_fin)->startOfDay(),
            ];
        }

        $fechas = [];

        foreach ($filas as $fila) {
            foreach ($fila as $celda) {
                $fecha = $this->intentarParsearSoloFecha($celda);
                if ($fecha) {
                    $fechas[] = $fecha->toDateString();
                }
            }
        }

        $fechas = array_values(array_unique($fechas));

        if (empty($fechas)) {
            throw new \RuntimeException(
                'No se pudo inferir el periodo desde el CSV log. Selecciona un periodo manualmente.'
            );
        }

        sort($fechas);

        return [
            Carbon::parse($fechas[0])->startOfDay(),
            Carbon::parse(end($fechas))->startOfDay(),
        ];
    }

    protected function detectarFilaEncabezados(array $filas): ?int
    {
        /**
         * Busca una fila que parezca encabezado real del log.
         * Se apoya en alias frecuentes.
         */

        foreach ($filas as $idx => $fila) {
            $norm = array_map(fn ($x) => $this->normalizarEncabezado((string) $x), $fila);

            $tieneId = $this->contieneUnoDe($norm, [
                'reloj_usuario_id', 'id_reloj', 'usuario_id', 'user_id', 'employee_id'
            ]);

            $tieneFechaOHora = $this->contieneUnoDe($norm, [
                'fecha', 'hora', 'ocurrio_en', 'datetime', 'fecha_hora', 'timestamp'
            ]);

            if ($tieneId && $tieneFechaOHora) {
                return $idx;
            }
        }

        return null;
    }

    protected function ubicarColumnasLog(array $encabezados): array
    {
        /**
         * Regresa los índices de columnas relevantes.
         *
         * Puede existir:
         * - fecha + hora separadas
         * - o una sola columna datetime
         */

        return [
            'reloj_usuario_id' => $this->buscarIndiceEncabezado($encabezados, [
                'reloj_usuario_id', 'id_reloj', 'usuario_id', 'user_id', 'employee_id'
            ]),
            'numero_control' => $this->buscarIndiceEncabezado($encabezados, [
                'numero_control', 'num_control', 'matricula', 'codigo_alumno', 'student_id'
            ]),
            'fecha' => $this->buscarIndiceEncabezado($encabezados, [
                'fecha', 'date'
            ]),
            'hora' => $this->buscarIndiceEncabezado($encabezados, [
                'hora', 'time'
            ]),
            'ocurrio_en' => $this->buscarIndiceEncabezado($encabezados, [
                'ocurrio_en', 'datetime', 'fecha_hora', 'timestamp', 'check_time'
            ]),
        ];
    }

    protected function leerAsistenciaDesdeLog(
        array $filas,
        int $filaEncabezados,
        array $metaColumnas,
        Carbon $inicio,
        Carbon $fin,
        array &$advertencias
    ): array {
        /**
         * Este es el corazón real del parser CSV tipo log.
         *
         * Cada fila representa una marcación.
         * Se intenta construir:
         * [
         *   'reloj_usuario_id' => ...,
         *   'numero_control'   => ...,
         *   'ocurrio_en'       => 'Y-m-d H:i:s',
         *   'celda_cruda'      => ...
         * ]
         */

        $out = [];

        for ($i = $filaEncabezados + 1; $i < count($filas); $i++) {
            $fila = $filas[$i];

            $relojUsuarioId = $this->valorFila($fila, $metaColumnas['reloj_usuario_id']);
            $numeroControl  = $this->valorFila($fila, $metaColumnas['numero_control']);

            if ($relojUsuarioId === '' && $numeroControl === '') {
                continue;
            }

            $dt = $this->resolverFechaHoraLog($fila, $metaColumnas);

            if (!$dt) {
                $advertencias[] = "Fila {$i}: no se pudo interpretar fecha/hora; se omitió la marcación.";
                continue;
            }

            $dt = $dt->seconds(0);

            if ($dt->lt($inicio) || $dt->gt($fin->copy()->endOfDay())) {
                continue;
            }

            $celdaCruda = $this->resumirFilaCruda($fila);

            $out[] = [
                'reloj_usuario_id' => $relojUsuarioId,
                'numero_control'   => $numeroControl,
                'ocurrio_en'       => $dt->toDateTimeString(),
                'celda_cruda'      => mb_substr($celdaCruda, 0, 255),
            ];
        }

        return $out;
    }

    protected function leerTurnosDesdeLog(
        array $filas,
        int $filaEncabezados,
        array $metaColumnas,
        Carbon $inicio,
        Carbon $fin,
        array &$advertencias
    ): array {
        /**
         * DEFAULT:
         * un CSV log normalmente NO trae turnos.
         *
         * Entonces por defecto aquí lanzamos excepción.
         * Solo debes sobrescribir este método si el proveedor
         * realmente mezcla turnos y log en el mismo archivo.
         */

        throw new \RuntimeException(
            'Este CSV tipo log no incluye turnos. Usa SOLO_ASISTENCIA o adapta leerTurnosDesdeLog() para ese proveedor.'
        );
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
                $fila = array_map(
                    fn ($v) => $this->limpiarBom($this->normTexto((string) $v)),
                    $fila
                );

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

    protected function resolverFechaHoraLog(array $fila, array $metaColumnas): ?Carbon
    {
        /**
         * Soporta dos variantes comunes:
         *
         * 1) fecha + hora por separado
         * 2) una sola columna datetime
         */

        $valorDatetime = $this->valorFila($fila, $metaColumnas['ocurrio_en']);
        if ($valorDatetime !== '') {
            return $this->intentarParsearFechaHora($valorDatetime);
        }

        $valorFecha = $this->valorFila($fila, $metaColumnas['fecha']);
        $valorHora  = $this->valorFila($fila, $metaColumnas['hora']);

        if ($valorFecha !== '' && $valorHora !== '') {
            return $this->intentarParsearFechaHora($valorFecha . ' ' . $valorHora);
        }

        return null;
    }

    protected function intentarParsearFechaHora(string $texto): ?Carbon
    {
        $texto = $this->normTexto($texto);

        if ($texto === '') {
            return null;
        }

        $formatos = [
            'Y-m-d H:i:s',
            'Y-m-d H:i',
            'd/m/Y H:i:s',
            'd/m/Y H:i',
            'd-m-Y H:i:s',
            'd-m-Y H:i',
            'Y/m/d H:i:s',
            'Y/m/d H:i',
        ];

        foreach ($formatos as $formato) {
            try {
                return Carbon::createFromFormat($formato, $texto);
            } catch (\Throwable $e) {
            }
        }

        try {
            return Carbon::parse($texto);
        } catch (\Throwable $e) {
            return null;
        }
    }

    protected function intentarParsearSoloFecha(string $texto): ?Carbon
    {
        $texto = $this->normTexto($texto);

        if ($texto === '') {
            return null;
        }

        $formatos = [
            'Y-m-d',
            'd/m/Y',
            'd-m-Y',
            'Y/m/d',
        ];

        foreach ($formatos as $formato) {
            try {
                return Carbon::createFromFormat($formato, $texto)->startOfDay();
            } catch (\Throwable $e) {
            }
        }

        return null;
    }

    protected function validarDiasPeriodo(int $diasPeriodo): void
    {
        if ($diasPeriodo < 1 || $diasPeriodo > 31) {
            throw new \RuntimeException(
                "Periodo inválido: {$diasPeriodo} días. Verifica que el rango esté entre 1 y 31 días."
            );
        }
    }

    protected function buscarIndiceEncabezado(array $encabezados, array $alias): ?int
    {
        $normalizados = array_map(
            fn ($x) => $this->normalizarEncabezado((string) $x),
            $encabezados
        );

        $aliasNorm = array_map(
            fn ($x) => $this->normalizarEncabezado((string) $x),
            $alias
        );

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

    protected function resumirFilaCruda(array $fila): string
    {
        return $this->normTexto(implode(' | ', array_map(
            fn ($v) => (string) $v,
            $fila
        )));
    }

    protected function contieneUnoDe(array $valores, array $buscados): bool
    {
        foreach ($buscados as $buscado) {
            if (in_array($this->normalizarEncabezado($buscado), $valores, true)) {
                return true;
            }
        }

        return false;
    }

    protected function normalizarEncabezado(string $texto): string
    {
        $texto = mb_strtolower($this->normTexto($texto));
        $texto = str_replace(
            ['á', 'é', 'í', 'ó', 'ú', 'ü', 'ñ'],
            ['a', 'e', 'i', 'o', 'u', 'u', 'n'],
            $texto
        );
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