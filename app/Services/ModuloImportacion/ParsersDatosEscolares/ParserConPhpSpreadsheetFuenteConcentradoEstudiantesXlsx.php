<?php

namespace App\Services\ModuloImportacion\ParsersDatosEscolares;

use PhpOffice\PhpSpreadsheet\Cell\Coordinate;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use ZipArchive;

class ParserConPhpSpreadsheetFuenteConcentradoEstudiantesXlsx implements DatosEscolaresParserInterface
{
    public function parsear(
        string $rutaFisicaArchivo,
        string $tipoImportacion,
    ): array {

        $tipoImportacion = strtoupper(trim($tipoImportacion));

        if (!in_array($tipoImportacion, ['COMPLETA', 'SOLO_ESTUDIANTES'], true)) {
            throw new \RuntimeException(
                'Tipo de importación no válido para este parser de la fuente de Concentrado de Estudiantes.'
            );
        }

        $advertencias = [];

        $advertencias = array_merge(
            $advertencias,
            $this->prevalidarXlsxAntesDeLoad($rutaFisicaArchivo)
        );

        $reader = IOFactory::createReaderForFile($rutaFisicaArchivo);

        $reader->setReadDataOnly(true);

        $hojasDetectadas = $reader->listWorksheetNames($rutaFisicaArchivo);

        $nombresHojasObjetivo = $this->seleccionarHojasPorNombre($hojasDetectadas);

        if (empty($nombresHojasObjetivo)) {
            throw new \RuntimeException(
                'El archivo no contiene una hoja válida llamada "MAESTRIA" o "DOCTORADO". ' .
                'Corrige el nombre de la hoja según la plantilla oficial. ' .
                'Hojas detectadas: ' . implode(', ', $hojasDetectadas)
            );
        }

        $reader->setLoadSheetsOnly($nombresHojasObjetivo);

        $spreadsheet = $reader->load($rutaFisicaArchivo);

        $estudiantes = [];
        $filasDetectadas = 0;
        $filasOmitidasSinNumeroControl = 0;

        $hojasObjetivo = $this->obtenerHojasCargadasPorNombre($spreadsheet, $nombresHojasObjetivo);

        if (empty($hojasObjetivo)) {
            throw new \RuntimeException(
                'No se pudieron cargar las hojas válidas esperadas del archivo XLSX.'
            );
        }

        foreach ($hojasObjetivo as $ws) {
            $resultadoHoja = $this->leerHojaConcentrado($ws, $advertencias);

            $estudiantes = array_merge($estudiantes, $resultadoHoja['estudiantes']);

            $filasDetectadas += $resultadoHoja['filas_detectadas'];
            $filasOmitidasSinNumeroControl += $resultadoHoja['filas_omitidas_sin_numero_control'];
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
    }



    private function prevalidarXlsxAntesDeLoad(string $rutaFisicaArchivo): array
    {
        if (!is_file($rutaFisicaArchivo)) {
            throw new \RuntimeException('No se encontró el archivo XLSX a validar.');
        }

        clearstatcache(true, $rutaFisicaArchivo);

        $tamArchivo = filesize($rutaFisicaArchivo);
        if ($tamArchivo === false || $tamArchivo <= 0) {
            throw new \RuntimeException('No se pudo determinar el tamaño del archivo XLSX.');
        }

        $advertencias = [];

        // LIMITES DE ADVERTENCIA
        $warnArchivoComprimido = 8 * 1024 * 1024;   // 8 MB - Tamaño aun aceptable del .xlsx como archivo físico 
        $warnXmlHojasTotal = 20 * 1024 * 1024;  // 20 MB - suma del tamaño descomprimido de los XML de las hojas, avisa que el interior del archivo ya es grande
        $warnSharedStrings = 4 * 1024 * 1024;   // 4 MB - varias celdas de texto, texto repetido, estructura cargada. Advertencia si ya está grande
        $warnStylesXml = 1 * 1024 * 1024;   // 1 MB - detectar Excel con demasiados estilos, formatos, colores, bordes o combinaciones raras.
        $warnHojas = 4; // Controla cuántas hojas como minimo puede traer el archivo.
        $warnCompressionRatio = 40;

        // LIMITES DE BLOQUEO
        $maxArchivoComprimido = 10 * 1024 * 1024;   // 10 MB - Tamaño maximo del .xlsx como archivo físico
        $maxXmlHojasTotal = 40 * 1024 * 1024;   // 40 MB - suma del tamaño descomprimido de los XML de las hojas. bloquea cuando ya es demasiado
        $maxSharedStrings = 8 * 1024 * 1024;    // 8 MB - muchísimas celdas, texto repetido exagerado, estructura muy cargada. Bloqueo si ya es demasiado grande
        $maxStylesXml = 2 * 1024 * 1024;    // 2 MB - detectar Excel con demasiados estilos, formatos, colores, bordes o combinaciones raras.
        $maxHojas = 6; // controla cuántas hojas como maximo puede trae el archivo.
        $maxCompressionRatio = 80;

        if ($tamArchivo > $maxArchivoComprimido) {
            throw new \RuntimeException(
                'El archivo XLSX es demasiado grande para este parser. Usa el parser streaming o corrige el archivo.'
            );
        }

        if ($tamArchivo > $warnArchivoComprimido) {
            $advertencias[] =
                'El archivo XLSX tiene un tamaño considerable. Puede tardar más de lo normal en procesarse.';
        }

        $zip = new ZipArchive();

        if ($zip->open($rutaFisicaArchivo) !== true) {
            throw new \RuntimeException('El archivo no es un XLSX válido o no se pudo abrir como ZIP.  Verifica el archivo y corrigelo.');
        }

        try {
            foreach (['xl/workbook.xml', 'xl/_rels/workbook.xml.rels'] as $entry) {
                if ($zip->locateName($entry) === false) {
                    throw new \RuntimeException('El archivo XLSX no tiene la estructura interna esperada. Verifica el archivo y corrigelo.');
                }
            }

            $totalXmlHojas = 0;
            $totalHojas = 0;

            for ($i = 0; $i < $zip->numFiles; $i++) {
                $stat = $zip->statIndex($i);

                if (!$stat) {
                    continue;
                }

                $name = (string) ($stat['name'] ?? '');
                $size = (int) ($stat['size'] ?? 0);
                $comp = (int) ($stat['comp_size'] ?? 0);

                $ratio = $comp > 0 ? ($size / $comp) : ($size > 0 ? INF : 1);

                if ($ratio > $maxCompressionRatio && $size > 1024 * 1024) {
                    throw new \RuntimeException(
                        "El archivo XLSX parece inflado o sucio internamente ({$name}). Verifica el archivo y corrigelo."
                    );
                }

                if ($ratio > $warnCompressionRatio && $size > 1024 * 1024) {
                    $advertencias[] =
                        "Se detectó compresión interna elevada en {$name}. El archivo podría venir inflado o mal optimizado.";
                }

                if (preg_match('#^xl/worksheets/[^/]+\.xml$#', $name)) {
                    $totalHojas++;
                    $totalXmlHojas += $size;
                }

                if ($name === 'xl/sharedStrings.xml' && $size > $maxSharedStrings) {
                    throw new \RuntimeException(
                        'El archivo XLSX tiene demasiadas cadenas compartidas y puede agotar recursos. Verifica el archivo y corrigelo.'
                    );
                }

                if ($name === 'xl/sharedStrings.xml' && $size > $warnSharedStrings) {
                    $advertencias[] =
                        'El archivo XLSX tiene muchas cadenas compartidas. Puede consumir más memoria de lo normal.';
                }

                if ($name === 'xl/styles.xml' && $size > $maxStylesXml) {
                    throw new \RuntimeException(
                        'El archivo XLSX tiene demasiados estilos internos y puede agotar recursos. Verifica el archivo y corrigelo.'
                    );
                }

                if ($name === 'xl/styles.xml' && $size > $warnStylesXml) {
                    $advertencias[] =
                        'El archivo XLSX contiene muchos estilos internos. Esto puede volver más pesado el procesamiento.';
                }
            }

            if ($totalHojas <= 0) {
                throw new \RuntimeException('El archivo XLSX no contiene hojas válidas.');
            }

            if ($totalHojas > $maxHojas) {
                throw new \RuntimeException(
                    'El archivo XLSX contiene demasiadas hojas para este proceso. Verifica el archivo y corrigelo. El limite es de 6.'
                );
            }

            if ($totalHojas > $warnHojas) {
                $advertencias[] =
                    'El archivo XLSX contiene varias hojas. Solo se procesarán las hojas objetivo válidas. El limite es de 6.';
            }

            if ($totalXmlHojas > $maxXmlHojasTotal) {
                throw new \RuntimeException(
                    'El contenido interno de las hojas del XLSX es demasiado grande para este parser de la fuente de datos. Verifica el archivo y corrigelo.'
                );
            }

            if ($totalXmlHojas > $warnXmlHojasTotal) {
                $advertencias[] =
                    'El contenido interno de las hojas del XLSX es grande. El procesamiento podría ser más lento.';
            }
        } finally {
            $zip->close();
        }

        return array_values(array_unique(array_filter($advertencias)));
    }


    private function seleccionarHojasPorNombre(array $sheetNames): array
    {
        $objetivo = [];

        foreach ($sheetNames as $name) {
            $titulo = $this->normalizarTexto($name);

            // Si el nombre contiene MAESTRIA o DOCTORADO se considera candidata
            if (str_contains($titulo, 'MAESTRIA') || str_contains($titulo, 'DOCTORADO')) {
                $objetivo[] = $name;
            }
        }

        return $objetivo;
    }


    private function obtenerHojasCargadasPorNombre($spreadsheet, array $nombres): array
    {
        $out = [];

        foreach ($nombres as $name) {
            $sheet = $spreadsheet->getSheetByName($name);

            if ($sheet instanceof Worksheet) {
                $out[] = $sheet;
            }
        }

        return $out;
    }


    private function leerHojaConcentrado(Worksheet $ws, array &$advertencias): array
    {
        $columnas = $this->mapearColumnasConcentrado($ws);

        $this->validarColumnasMinimas($ws, $columnas);

        $maxRow = $this->determinarUltimaFilaRelevante($ws, $columnas);

        $estudiantes = [];
        $filasDetectadas = 0;
        $filasOmitidasSinNumeroControl = 0;
        $controlesRepetidos = [];
        $vistos = [];
        $filasVaciasConsecutivas = 0;

        for ($fila = 2; $fila <= $maxRow; $fila++) {

            $anioRaw = $this->celdaTexto($ws, $columnas['anio_generacion'], $fila);
            $mesRaw = $this->celdaTexto($ws, $columnas['mes_ingreso'], $fila);
            $numeroControl = $this->celdaId($ws, $columnas['numero_control'], $fila);
            $nombreCompleto = $this->celdaTexto($ws, $columnas['alumno'], $fila);
            $especialidad = $this->celdaTexto($ws, $columnas['especialidad'], $fila);
            $estatusRaw = $this->celdaTexto($ws, $columnas['estatus'], $fila);

            if (
                $anioRaw === '' &&
                $mesRaw === '' &&
                $numeroControl === '' &&
                $nombreCompleto === '' &&
                $especialidad === '' &&
                $estatusRaw === '' 
            ) {
                $filasVaciasConsecutivas++;

                if ($filasVaciasConsecutivas >= 200) {
                    break;
                }

                continue;
            }

            $filasVaciasConsecutivas = 0;

            if ($numeroControl === '') {
                $filasOmitidasSinNumeroControl++;
                continue;
            }

            $filasDetectadas++;

            if (isset($vistos[$numeroControl])) {
                $controlesRepetidos[$numeroControl] = true;
            }
            $vistos[$numeroControl] = true;

            [$anioIngreso, $mesIngreso, $periodoTexto] = $this->parsearIngreso(
                $anioRaw,
                $mesRaw,
                $ws,
                $columnas['anio_generacion'],
                $columnas['mes_ingreso'],
                $fila
            );

            [$estatusClave, $estatusNombre, $estatusRawNormalizado] = $this->prepararEstatus($estatusRaw);

            $estudiantes[] = [
                'numero_control' => $numeroControl,
                'nombre_completo' => $nombreCompleto !== '' ? $nombreCompleto : null,
                'anio_ingreso' => $anioIngreso,
                'mes_ingreso' => $mesIngreso,
                'periodo_ingreso_texto' => $periodoTexto,
                'especialidad_nombre' => $especialidad !== '' ? $especialidad : null,
                'estatus_clave' => $estatusClave,
                'estatus_nombre' => $estatusNombre,
                'estatus_raw' => $estatusRawNormalizado,
                'hoja_origen' => $ws->getTitle(),
            ];
        }

        if (!empty($controlesRepetidos)) {
            $advertencias[] =
                'Se detectaron números de control repetidos en la hoja "' .
                $ws->getTitle() .
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


    private function mapearColumnasConcentrado(Worksheet $ws): array
    {
        $tituloHoja = $this->normalizarTexto($ws->getTitle());

        $mapa = [
            'anio_generacion' => null,
            'mes_ingreso' => null,
            'numero_control' => null,
            'alumno' => null,
            'especialidad' => null,
            'estatus' => null,
        ];

        $maxCol = Coordinate::columnIndexFromString($ws->getHighestColumn());

        $headersNorm = [];

        for ($col = 1; $col <= $maxCol; $col++) {
            $headersNorm[$col] = $this->normalizarTexto($this->celdaTexto($ws, $col, 1));
        }

        if (str_contains($tituloHoja, 'MAESTRIA') && (($headersNorm[11] ?? '') === 'ESTATUS')) {
            $mapa['estatus'] = 11; // K
        }

        if (str_contains($tituloHoja, 'DOCTORADO') && (($headersNorm[12] ?? '') === 'ESTATUS')) {
            $mapa['estatus'] = 12; // L
        }

        for ($col = 1; $col <= $maxCol; $col++) {
            $header = $headersNorm[$col] ?? '';

            if ($header === '') {
                continue;
            }

            if ($mapa['anio_generacion'] === null) {
                $esAnioGeneracion =
                    $header === 'ANO DE GENERACION' ||
                    $header === 'ANO GENERACION' ||
                    (
                        str_contains($header, 'ANO') &&
                        str_contains($header, 'GENERACION')
                    );

                if ($esAnioGeneracion) {
                    $mapa['anio_generacion'] = $col;
                    continue;
                }
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

        if ($mapa['estatus'] === null) {
            for ($col = 1; $col <= $maxCol; $col++) {
                $header = $headersNorm[$col] ?? '';

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

        if (str_contains($tituloHoja, 'MAESTRIA')) {
            $mapa['anio_generacion'] ??= 2;  // B
            $mapa['mes_ingreso'] ??= 3; // C
            $mapa['numero_control'] ??= 7; // G
            $mapa['alumno'] ??= 8; // H
            $mapa['especialidad'] ??= 9; // I
            $mapa['estatus'] ??= 11; // K
        }

        if (str_contains($tituloHoja, 'DOCTORADO')) {
            $mapa['anio_generacion'] ??= 2;// B
            $mapa['mes_ingreso'] ??= 3; // C
            $mapa['numero_control'] ??= 8; // H
            $mapa['alumno'] ??= 9; // I
            $mapa['especialidad'] ??= 10; // J
            $mapa['estatus'] ??= 12; // L
        }

        return $mapa;
    }


    private function validarColumnasMinimas(Worksheet $ws, array $columnas): void
    {
        foreach (['anio_generacion', 'mes_ingreso', 'numero_control', 'alumno', 'especialidad', 'estatus'] as $campo) {
            if (empty($columnas[$campo])) {
                throw new \RuntimeException(
                    'No se pudo ubicar la columna requerida "' .
                    $campo .
                    '" en la hoja "' .
                    $ws->getTitle() .
                    '".'
                );
            }
        }
    }


    private function determinarUltimaFilaRelevante(Worksheet $ws, array $columnas): int
    {
        $maxRow = 1;

        foreach (['anio_generacion', 'mes_ingreso', 'numero_control', 'alumno', 'especialidad', 'estatus'] as $campo) {
            $col = $columnas[$campo] ?? null;

            if (!$col) {
                continue;
            }

            $letra = Coordinate::stringFromColumnIndex((int) $col);
            $maxRow = max($maxRow, (int) $ws->getHighestDataRow($letra));
        }

        return max($maxRow, 2);
    }


    private function parsearIngreso(
        string $anioRaw,
        string $mesRaw,
        Worksheet $ws,
        ?int $colAnio,
        ?int $colMes,
        int $fila
    ): array {
        $anioTexto = $this->normExcel($anioRaw);
        $mesTexto = $this->normExcel($mesRaw);

        $anio = $this->extraerAnio($anioTexto, $ws, $colAnio, $fila);
        $mes = $this->extraerMes($mesTexto, $ws, $colMes, $fila);

        $partes = [];

        if ($anioTexto !== '') {
            $partes[] = "AÑO GENERACION: {$anioTexto}";
        }

        if ($mesTexto !== '') {
            $partes[] = "MES INGRESO: {$mesTexto}";
        }

        $periodoTexto = empty($partes) ? null : implode(' | ', $partes);

        return [$anio, $mes, $periodoTexto];
    }


    private function extraerAnio(string $texto, Worksheet $ws, ?int $col, int $fila): ?int
    {
        if (preg_match('/\\b(19\\d{2}|20\\d{2})\\b/', $texto, $m)) {
            return (int) $m[1];
        }

        if (!$col) {
            return null;
        }

        $raw = $ws->getCell([$col, $fila])->getValue();

        if ($raw instanceof \DateTimeInterface) {
            return (int) $raw->format('Y');
        }

        if (is_numeric($raw)) {
            $n = (float) $raw;

            if ($n >= 1900 && $n <= 2100) {
                return (int) round($n);
            }

            try {
                return (int) ExcelDate::excelToDateTimeObject($n)->format('Y');
            } catch (\Throwable $e) {
                return null;
            }
        }

        return null;
    }


    private function extraerMes(string $texto, Worksheet $ws, ?int $col, int $fila): ?int
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

        if (preg_match('/\\b(0?[1-9]|1[0-2])\\b/', $normalizado, $m)) {
            return (int) $m[1];
        }

        if (!$col) {
            return null;
        }

        $raw = $ws->getCell([$col, $fila])->getValue();

        if ($raw instanceof \DateTimeInterface) {
            return (int) $raw->format('n');
        }

        if (is_numeric($raw)) {
            try {
                return (int) ExcelDate::excelToDateTimeObject((float) $raw)->format('n');
            } catch (\Throwable $e) {
                return null;
            }
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


    private function celdaTexto(Worksheet $ws, ?int $col, int $row): string
    {
        if (!$col) {
            return '';
        }

        $cell = $ws->getCell([$col, $row]);
        $value = $cell->getValue();

        if ($value instanceof RichText) {
            $value = $value->getPlainText();
        }

        if ($value instanceof \DateTimeInterface) {
            return $this->normExcel($value->format('Y-m-d'));
        }

        if (is_numeric($value)) {
            return $this->normExcel((string) ($cell->getFormattedValue() ?? $value));
        }

        $texto = (string) ($value ?? '');

        if ($texto === '') {
            $texto = (string) ($cell->getFormattedValue() ?? '');
        }

        return $this->normExcel($texto);
    }


    private function celdaId(Worksheet $ws, ?int $col, int $row): string
    {
        if (!$col) {
            return '';
        }

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