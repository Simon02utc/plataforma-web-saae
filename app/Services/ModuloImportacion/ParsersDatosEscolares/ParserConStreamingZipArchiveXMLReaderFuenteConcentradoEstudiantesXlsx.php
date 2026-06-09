<?php

namespace App\Services\ModuloImportacion\ParsersDatosEscolares;

use XMLReader;
use ZipArchive;
use SimpleXMLElement;

class ParserConStreamingZipArchiveXMLReaderFuenteConcentradoEstudiantesXlsx implements DatosEscolaresParserInterface
{
    private const EMPTY_ROW_STREAK_LIMIT = 200;

    public function parsear(
        string $rutaFisicaArchivo,
        string $tipoImportacion
    ): array {

        $tipoImportacion = strtoupper(trim($tipoImportacion));

        if (!in_array($tipoImportacion, ['COMPLETA', 'SOLO_ESTUDIANTES'], true)) {
            throw new \RuntimeException(
                'Tipo de importación no válido para el parser de esta fuente de datos escolares.'
            );
        }

        $zip = new ZipArchive();

        // un XLSX realmente es un ZIP con varios XML adentro
        if ($zip->open($rutaFisicaArchivo) !== true) {
            throw new \RuntimeException('No se pudo abrir el archivo XLSX.');
        }

        try {

            $sharedStrings = $this->cargarSharedStrings($zip);

            // relaciona nombres de hojas con la ruta interna de cada XML de hoja
            $mapaHojas = $this->obtenerMapaHojas($zip);

            $hojasDetectadas = array_keys($mapaHojas);

            // Se queda solo con hojas que interesan
            $hojasObjetivo = $this->obtenerHojasCargadasPorNombre($mapaHojas);

            if (empty($hojasObjetivo)) {
                throw new \RuntimeException(
                    'No se encontraron hojas válidas para importar datos escolares. ' .
                    'Se esperaba una hoja como "MAESTRIA" o "DOCTORADO".'
                );
            }

            $advertencias = [];
            $estudiantes = [];
            $filasDetectadas = 0;
            $filasOmitidasSinNumeroControl = 0;

            foreach ($hojasObjetivo as $tituloHoja => $sheetPath) {
                $resultado = $this->leerHojaStreaming(
                    $zip,
                    $sheetPath,
                    $tituloHoja,
                    $sharedStrings,
                    $advertencias
                );

                $estudiantes = array_merge($estudiantes, $resultado['estudiantes']);
                $filasDetectadas += $resultado['filas_detectadas'];
                $filasOmitidasSinNumeroControl += $resultado['filas_omitidas_sin_numero_control'];
            }

            if ($filasOmitidasSinNumeroControl > 0) {
                $advertencias[] =
                    "Se omitieron {$filasOmitidasSinNumeroControl} fila(s) porque no tenían número de control.";
            }

            //Limpias advertencias repetidas o vacias
            $advertencias = array_values(array_unique(array_filter($advertencias)));

            return [
                'hojas_detectadas' => $hojasDetectadas,
                'advertencias' => $advertencias,
                'estudiantes' => $estudiantes,
                'resumen_fuente' => [
                    'hojas_procesadas' => count($hojasObjetivo),
                    'filas_detectadas' => $filasDetectadas,
                    'filas_omitidas_sin_numero_control' => $filasOmitidasSinNumeroControl,
                ],
            ];
        } finally {
            // Se asegura de cerrar el ZIP aunque falle algo.
            $zip->close();
        }
    }




    private function cargarSharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $reader = new XMLReader();
        $reader->XML($xml, null, LIBXML_NONET | LIBXML_COMPACT);

        $strings = [];

        while ($reader->read()) {
            if ($reader->nodeType === XMLReader::ELEMENT && $reader->localName === 'si') {
                $outer = $reader->readOuterXML();
                $si = @simplexml_load_string($outer);

                if (!$si instanceof SimpleXMLElement) {
                    $strings[] = '';
                    continue;
                }

                $texto = '';

                // Puede venir como texto directo o fragmentado por runs.
                if (isset($si->t)) {
                    $texto = (string) $si->t;
                } else {
                    foreach ($si->r as $run) {
                        $texto .= (string) $run->t;
                    }
                }

                $strings[] = $this->normExcel($texto);
            }
        }

        $reader->close();

        return $strings;
    }


    private function obtenerMapaHojas(ZipArchive $zip): array
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            throw new \RuntimeException('No se pudo leer la estructura interna del archivo XLSX.');
        }

        $workbook = @simplexml_load_string($workbookXml);
        $rels = @simplexml_load_string($relsXml);

        if (!$workbook || !$rels) {
            throw new \RuntimeException('No se pudo interpretar la estructura del archivo XLSX.');
        }

        $workbook->registerXPathNamespace(
            'main',
            'http://schemas.openxmlformats.org/spreadsheetml/2006/main'
        );
        $workbook->registerXPathNamespace(
            'r',
            'http://schemas.openxmlformats.org/officeDocument/2006/relationships'
        );

        $rels->registerXPathNamespace(
            'rel',
            'http://schemas.openxmlformats.org/package/2006/relationships'
        );

        $relMap = [];

        foreach ($rels->xpath('//rel:Relationship') as $rel) {
            $id = (string) $rel['Id'];
            $target = (string) $rel['Target'];

            if ($id !== '' && $target !== '') {
                $relMap[$id] = 'xl/' . ltrim($target, '/');
            }
        }

        $out = [];

        foreach ($workbook->xpath('//main:sheets/main:sheet') as $sheet) {
            $nombre = (string) $sheet['name'];
            $rid = (string) $sheet->attributes('r', true)->id;

            if ($nombre !== '' && isset($relMap[$rid])) {
                $out[$nombre] = $relMap[$rid];
            }
        }

        return $out;
    }


    private function obtenerHojasCargadasPorNombre(array $mapaHojas): array
    {
        $objetivo = [];

        foreach ($mapaHojas as $nombre => $path) {
            $titulo = $this->normalizarTexto($nombre);

            if (str_contains($titulo, 'MAESTRIA') || str_contains($titulo, 'DOCTORADO')) {
                $objetivo[$nombre] = $path;
            }
        }

        return $objetivo;
    }


    //Este es el corazon del parser
    private function leerHojaStreaming(
        ZipArchive $zip,
        string $sheetPath,
        string $tituloHoja,
        array $sharedStrings,
        array &$advertencias
    ): array {

        $sheetXml = $zip->getFromName($sheetPath);

        if ($sheetXml === false) {
            throw new \RuntimeException("No se pudo leer la hoja '{$tituloHoja}' dentro del XLSX.");
        }

        $reader = new XMLReader();

        // LIBXML_PARSEHUGE ayuda con XML grandes
        $reader->XML($sheetXml, null, LIBXML_NONET | LIBXML_COMPACT | LIBXML_PARSEHUGE);

        $headers = [];
        $columnas = null;
        $estudiantes = [];
        $filasDetectadas = 0;
        $filasOmitidasSinNumeroControl = 0;
        $controlesRepetidos = [];
        $vistos = [];
        $emptyStreak = 0;

        while ($reader->read()) {

            if ($reader->nodeType !== XMLReader::ELEMENT || $reader->localName !== 'row') {
                continue;
            }

            $rowNumber = (int) ($reader->getAttribute('r') ?: 0);
            $rowXml = $reader->readOuterXML();

            if ($rowXml === '') {
                continue;
            }

            $row = @simplexml_load_string($rowXml);

            if (!$row instanceof SimpleXMLElement) {
                continue;
            }

            // convierte la fila XML a arreglo tipo:
            // ['A' => '...', 'B' => '...', 'G' => '12345']
            $rowData = $this->extraerRowData($row, $sharedStrings);

            // la fila 1 se toma como encabezado
            if ($rowNumber === 1) {
                $headers = $rowData;
                $columnas = $this->mapearColumnasConcentradoDesdeHeaders($headers, $tituloHoja);
                $this->validarColumnasMinimas($tituloHoja, $columnas);
                continue;
            }

            if ($columnas === null) {
                continue;
            }

            //las columnas mapeadas, saca los datos relevantes
            $anioRaw = $this->valorColumna($rowData, $columnas['anio_generacion']);
            $mesRaw = $this->valorColumna($rowData, $columnas['mes_ingreso']);
            $numeroControl = $this->normId($this->valorColumna($rowData, $columnas['numero_control']));
            $nombreCompleto = $this->normExcel($this->valorColumna($rowData, $columnas['alumno']));
            $especialidad = $this->normExcel($this->valorColumna($rowData, $columnas['especialidad']));
            $estatusCelda = $this->normExcel($this->valorColumna($rowData, $columnas['estatus']));

            // Si la fila viene completamente vacia, cuenta racha
            if (
                $anioRaw === '' &&
                $mesRaw === '' &&
                $numeroControl === '' &&
                $nombreCompleto === '' &&
                $especialidad === '' &&
                $estatusCelda === '' 
            ) {
                $emptyStreak++;

                // se aprovecha de este corte temprano, porque no se cargo toda la hoja por adelantado
                if ($emptyStreak >= self::EMPTY_ROW_STREAK_LIMIT) {
                    break;
                }

                continue;
            }

            $emptyStreak = 0;

            if ($numeroControl === '') {
                $filasOmitidasSinNumeroControl++;
                continue;
            }

            $filasDetectadas++;

            if (isset($vistos[$numeroControl])) {
                $controlesRepetidos[$numeroControl] = true;
            }
            $vistos[$numeroControl] = true;

            [$anioIngreso, $mesIngreso, $periodoTexto] = $this->parsearIngreso($anioRaw, $mesRaw);
            [$estatusClave, $estatusNombre, $estatusRaw] = $this->prepararEstatus($estatusCelda);

            $estudiantes[] = [
                'numero_control' => $numeroControl,
                'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : null,
                'anio_ingreso' => $anioIngreso,
                'mes_ingreso' => $mesIngreso,
                'periodo_ingreso_texto' => $periodoTexto,
                'especialidad_nombre' => $especialidad !== '' ? $especialidad : null,
                'estatus_clave' => $estatusClave,
                'estatus_nombre' => $estatusNombre,
                'estatus_raw' => $estatusRaw,
                'hoja_origen' => $tituloHoja,
            ];
        }

        $reader->close();

        if (!empty($controlesRepetidos)) {
            $advertencias[] =
                'Se detectaron números de control repetidos en la hoja "' .
                $tituloHoja .
                '": ' .
                implode(', ', array_keys($controlesRepetidos)) .
                '. Se conservará la información más completa durante la importación.';
        }

        return [
            'estudiantes' => $estudiantes,
            'filas_detectadas' => $filasDetectadas,
            'filas_omitidas_sin_numero_control' => $filasOmitidasSinNumeroControl,
        ];
    }


    private function extraerRowData(SimpleXMLElement $row, array $sharedStrings): array
    {
        $out = [];

        foreach ($row->c as $cell) {
            $ref = (string) $cell['r'];
            $type = (string) $cell['t'];

            if ($ref === '') {
                continue;
            }

            $col = preg_replace('/\d+/', '', $ref);
            $out[$col] = $this->extraerValorCelda($cell, $type, $sharedStrings);
        }

        return $out;
    }


    private function extraerValorCelda(SimpleXMLElement $cell, string $type, array $sharedStrings): string
    {
        if ($type === 'inlineStr' && isset($cell->is->t)) {
            return $this->normExcel((string) $cell->is->t);
        }

        $value = isset($cell->v) ? (string) $cell->v : '';

        if ($value === '') {
            return '';
        }

        if ($type === 's') {
            $idx = (int) $value;
            return $this->normExcel($sharedStrings[$idx] ?? '');
        }

        return $this->normExcel($value);
    }


    private function mapearColumnasConcentradoDesdeHeaders(array $headers, string $tituloHoja): array
    {
        $tituloHojaNorm = $this->normalizarTexto($tituloHoja);

        $mapa = [
            'anio_generacion' => null,
            'mes_ingreso' => null,
            'numero_control' => null,
            'alumno' => null,
            'especialidad' => null,
            'estatus' => null,
        ];

        // ==========================================================
        // 1) Normalizar encabezados una sola vez. Las claves son LETRAS: A, B, C, K, L...
        // ==========================================================
        $headersNorm = [];

        foreach ($headers as $col => $valor) {
            $headersNorm[$col] = $this->normalizarTexto((string) $valor);
        }

        // ==========================================================
        // 2) Prioridad fuerte para formatos conocidos usando letras reales del streaming.
        // ==========================================================
        if (str_contains($tituloHojaNorm, 'MAESTRIA') && (($headersNorm['K'] ?? '') === 'ESTATUS')) {
            $mapa['estatus'] = 'K';
        }

        if (str_contains($tituloHojaNorm, 'DOCTORADO') && (($headersNorm['L'] ?? '') === 'ESTATUS')) {
            $mapa['estatus'] = 'L';
        }

        // ==========================================================
        // 3) Primer pase: coincidencias exactas/seguras
        // ==========================================================
        foreach ($headersNorm as $col => $header) {
            if ($header === '') {
                continue;
            }

            if (
                $mapa['anio_generacion'] === null &&
                ($header === 'ANO DE GENERACION' || $header === 'AÑO DE GENERACION')
            ) {
                $mapa['anio_generacion'] = $col;
                continue;
            }

            if (
                $mapa['mes_ingreso'] === null &&
                $header === 'MES DE INGRESO'
            ) {
                $mapa['mes_ingreso'] = $col;
                continue;
            }

            if (
                $mapa['numero_control'] === null &&
                in_array($header, ['NUM CONTROL', 'NUM. CONTROL', 'NO CONTROL', 'NO. CONTROL'], true)
            ) {
                $mapa['numero_control'] = $col;
                continue;
            }

            if (
                $mapa['alumno'] === null &&
                preg_match('/(^|\\s)ALUMNO(\\s|$)/', $header)
            ) {
                $mapa['alumno'] = $col;
                continue;
            }

            if (
                $mapa['especialidad'] === null &&
                (
                    str_contains($header, 'AREA ESPC') ||
                    str_contains($header, 'AREA ESP') ||
                    $header === 'ESPECIALIDAD'
                )
            ) {
                $mapa['especialidad'] = $col;
                continue;
            }

            if (
                $mapa['estatus'] === null &&
                in_array($header, ['ESTATUS', 'STATUS', 'SITUACION'], true)
            ) {
                $mapa['estatus'] = $col;
                continue;
            }
        }

        // ==========================================================
        // 4) Segundo pase: coincidencia flexible controlada
        // ==========================================================
        if ($mapa['estatus'] === null) {
            foreach ($headersNorm as $col => $header) {
                if ($header === '') {
                    continue;
                }

                $pareceEstatus =
                    str_contains($header, 'ESTATUS') ||
                    str_contains($header, 'STATUS') ||
                    str_contains($header, 'SITUACION');

                $esEstatusSecundario =
                    str_contains($header, 'ESTATUS SEMESTRE') ||
                    str_contains($header, 'STATUS SEMESTRE') ||
                    str_contains($header, 'SITUACION SEMESTRE') ||
                    str_contains($header, 'SEMESTRE ACTUAL');

                if ($pareceEstatus && !$esEstatusSecundario) {
                    $mapa['estatus'] = $col;
                    break;
                }
            }
        }

        // ==========================================================
        // 5) Defaults por tipo de hoja
        //    usando LETRAS, no números.
        // ==========================================================
        if (str_contains($tituloHojaNorm, 'MAESTRIA')) {
            $mapa['anio_generacion'] ??= 'B';
            $mapa['mes_ingreso'] ??= 'C';
            $mapa['numero_control'] ??= 'G';
            $mapa['alumno'] ??= 'H';
            $mapa['especialidad'] ??= 'I';
            $mapa['estatus'] ??= 'K';
        }

        if (str_contains($tituloHojaNorm, 'DOCTORADO')) {
            $mapa['anio_generacion'] ??= 'B';
            $mapa['mes_ingreso'] ??= 'C';
            $mapa['numero_control'] ??= 'H';
            $mapa['alumno'] ??= 'I';
            $mapa['especialidad'] ??= 'J';
            $mapa['estatus'] ??= 'L';
        }

        return $mapa;
    }


    private function validarColumnasMinimas(string $tituloHoja, array $columnas): void
    {
        foreach (['anio_generacion', 'mes_ingreso', 'numero_control', 'alumno', 'especialidad', 'estatus'] as $campo) {
            if (empty($columnas[$campo])) {
                throw new \RuntimeException(
                    'No se pudo ubicar la columna requerida "' .
                    $campo .
                    '" en la hoja "' .
                    $tituloHoja .
                    '".'
                );
            }
        }
    }


    private function valorColumna(array $rowData, ?string $col): string
    {
        if (!$col) {
            return '';
        }

        return isset($rowData[$col]) ? $this->normExcel((string) $rowData[$col]) : '';
    }


    private function parsearIngreso(string $anioRaw, string $mesRaw): array
    {
        $anioTexto = $this->normExcel($anioRaw);
        $mesTexto = $this->normExcel($mesRaw);

        $anio = $this->extraerAnio($anioTexto);
        $mes = $this->extraerMes($mesTexto);

        $partes = [];

        if ($anioTexto !== '') {
            $partes[] = "AÑO GENERACIÓN: {$anioTexto}";
        }

        if ($mesTexto !== '') {
            $partes[] = "MES INGRESO: {$mesTexto}";
        }

        $periodoTexto = empty($partes) ? null : implode(' | ', $partes);

        return [$anio, $mes, $periodoTexto];
    }


    private function extraerAnio(string $texto): ?int
    {
        if (preg_match('/\b(19\d{2}|20\d{2})\b/', $texto, $m)) {
            return (int) $m[1];
        }

        return null;
    }


    private function extraerMes(string $texto): ?int
    {
        $normalizado = $this->normalizarTexto($texto);

        $meses = [
            'ENERO' => 1,
            'FEBRERO' => 2,
            'MARZO' => 3,
            'ABRIL' => 4,
            'MAYO' => 5,
            'JUNIO' => 6,
            'JULIO' => 7,
            'AGOSTO' => 8,
            'SEPTIEMBRE' => 9,
            'SETIEMBRE' => 9,
            'OCTUBRE' => 10,
            'NOVIEMBRE' => 11,
            'DICIEMBRE' => 12,
        ];

        foreach ($meses as $nombre => $numero) {
            if (str_contains($normalizado, $nombre)) {
                return $numero;
            }
        }

        if (preg_match('/\b(0?[1-9]|1[0-2])\b/', $normalizado, $m)) {
            return (int) $m[1];
        }

        return null;
    }


    private function prepararEstatus(?string $raw): array
    {
        $raw = $this->normExcel((string) $raw);

        if ($raw === '') {
            return [null, null, null];
        }

        $clave = $this->generarClaveCatalogo($raw);

        if (preg_match('/^[A-Za-z0-9_]+$/', $raw)) {
            $nombre = mb_strtoupper(str_replace('_', ' ', $raw), 'UTF-8');
            return [$clave, $nombre, $raw];
        }

        return [$clave, mb_strtoupper($raw, 'UTF-8'), $raw];
    }


    private function generarClaveCatalogo(string $texto): string
    {
        $texto = trim($texto);
        $texto = mb_strtoupper($texto, 'UTF-8');

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $texto = $ascii !== false ? $ascii : $texto;

        $texto = preg_replace('/[^A-Z0-9]+/', '_', $texto);
        $texto = trim((string) $texto, '_');
        $texto = strtolower($texto);

        return mb_substr($texto, 0, 60);
    }


    private function normExcel(string $s): string
    {
        $s = str_replace(["\xC2\xA0", "\xE2\x80\x8B"], ' ', $s);
        $s = preg_replace('/[[:space:]]+/u', ' ', $s);

        return trim($s);
    }


    private function normId(string $s): string
    {
        $s = $this->normExcel($s);

        return preg_replace('/\s+/u', '', $s);
    }


    private function normalizarTexto(string $s): string
    {
        $s = $this->normExcel($s);
        $s = mb_strtoupper($s, 'UTF-8');

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
        $s = $ascii !== false ? $ascii : $s;

        $s = preg_replace('/[^A-Z0-9 ]+/', ' ', $s);
        $s = preg_replace('/\s+/u', ' ', $s);

        return trim($s);
    }
}