<?php

namespace App\Services\ModuloImportacion;

use App\Models\AreasEspecialidadEstudiantesSaae;
use App\Models\EstatusEscolaresEstudiantesSaae;
use App\Models\EstudiantesSaae;
use App\Models\EstudianteConDatosEscolares;
use App\Models\FuenteDatosEscolares;
use App\Models\ImportacionDatosEscolares;
use App\Services\ModuloImportacion\ParsersDatosEscolares\DatosEscolaresParserResolver;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class ImportarDatosEscolaresService
{

    public function __construct(
        private DatosEscolaresParserResolver $parserResolver
    ) {}

    //En vez de insertar 50,000 filas en un solo insert (pesado), las mete de 1000 en 1000, para mejorar el rendimeinto y evitar limites del motor
    private int $chunkSize = 1000;///chunkSize = tamaño de lote

    public function importar(
        string $rutaFisicaArchivo,
        string $rutaStorageArchivo,
        string $tipoImportacion,
        int $fuenteDatosEscolaresId,
        ?int $importadoPor = null,
        ?string $notas = null
    ): array {

        $tipoImportacion = strtoupper(trim($tipoImportacion));

        if (!in_array($tipoImportacion, ['COMPLETA', 'SOLO_ESTUDIANTES'], true)) {
            throw new \RuntimeException('Tipo de importación no soportado para datos escolares.');
        }

        $fuente = FuenteDatosEscolares::with('fuentesConParsers')->findOrFail($fuenteDatosEscolaresId);
        
        $fuentesConParsers = $this->parserResolver->resolver($fuente);

        $parsed = $fuentesConParsers->parsear($rutaFisicaArchivo, $tipoImportacion);

        $parserClave = $fuente->fuentesConParsers?->clave;
        if (!$parserClave) {
            throw new \RuntimeException("La fuente '{$fuente->nombre}' no tiene un parser asociado o no se pudo resolver su clave. Verifica el servicio.");
        }

        $hojasDetectadas = $parsed['hojas_detectadas'] ?? [];
        $advertencias = $parsed['advertencias'] ?? [];
        $estudiantes = $parsed['estudiantes'] ?? [];
        $resumenFuente = $parsed['resumen_fuente'] ?? [];

        if (empty($estudiantes)) {
            throw new \RuntimeException('El parser no devolvió estudiantes válidos para importar.');
        }

        $estudiantesPorControl = [];
        foreach ($estudiantes as $row) {
            $numeroControl = trim((string) ($row['numero_control'] ?? ''));

            if ($numeroControl === '') {
                continue;
            }

            if (!isset($estudiantesPorControl[$numeroControl])) {
                $estudiantesPorControl[$numeroControl] = $row;
                continue;
            }

            foreach ([
                'nombre_completo',
                'anio_ingreso',
                'mes_ingreso',
                'periodo_ingreso_texto',
                'especialidad_nombre',
                'estatus_clave',
                'estatus_nombre',
                'estatus_raw',
                'hoja_origen',
            ] as $campo) {
                if (
                    empty($estudiantesPorControl[$numeroControl][$campo]) &&
                    !empty($row[$campo])
                ) {
                    $estudiantesPorControl[$numeroControl][$campo] = $row[$campo];
                }
            }
        }

        $controles = array_keys($estudiantesPorControl);

        return DB::transaction(function () use (
            $rutaFisicaArchivo,
            $rutaStorageArchivo,
            $tipoImportacion,
            $importadoPor,
            $notas,
            $fuente,
            $parserClave,
            $hojasDetectadas,
            $advertencias,
            $estudiantesPorControl,
            $controles,
            $resumenFuente
        ) {
            $now = now();
            $hash = hash_file('sha256', $rutaFisicaArchivo);

            try {
                $importacion = ImportacionDatosEscolares::create([
                    'fuente_datos_escolares_id' => $fuente->id,
                    'archivo_nombre' => basename($rutaFisicaArchivo),
                    'archivo_ruta' => $rutaStorageArchivo,
                    'archivo_hash' => $hash,
                    'tipo_importacion' => $tipoImportacion,
                    'parser_clave' => $parserClave,
                    'hojas_detectadas' => $hojasDetectadas,
                    'importado_por' => $importadoPor,
                    'importado_en' => $now,
                    'estado' => 'EXITOSA',
                    'advertencias' => empty($advertencias) ? null : $advertencias,
                    'resultados_importacion' => null,
                    'notas' => $notas ? trim($notas) : null,
                ]);
            } catch (QueryException $e) {
                if (($e->errorInfo[1] ?? null) === 1062) {
                    return [
                        'ok' => false,
                        'mensaje' => "Este archivo ya fue importado para esa fuente y tipo de importación ({$tipoImportacion}).",
                    ];
                }

                throw $e;
            }


            // =====================================================
            // A) ESTUDIANTES SAAE
            // =====================================================
            $controlesExistentes = EstudiantesSaae::query()
                ->whereIn('numero_control', $controles)
                ->pluck('id', 'numero_control')
                ->all();

            $nuevosControles = array_values(array_diff($controles, array_keys($controlesExistentes)));

            $rowsNuevosEstudiantes = [];
            foreach ($nuevosControles as $numeroControl) {
                $data = $estudiantesPorControl[$numeroControl];

                $rowsNuevosEstudiantes[] = [
                    'numero_control' => $numeroControl,
                    'nombre_completo' => !empty($data['nombre_completo']) ? $data['nombre_completo'] : null,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $estudiantesInsertados = $this->insertOrIgnoreChunk('estudiantes_saae', $rowsNuevosEstudiantes);

            $rowsActualizarNombre = [];
            foreach ($estudiantesPorControl as $numeroControl => $data) {
                if (empty($data['nombre_completo'])) {
                    continue;
                }

                $rowsActualizarNombre[] = [
                    'numero_control' => $numeroControl,
                    'nombre_completo' => $data['nombre_completo'],
                    'updated_at' => $now,
                ];
            }

            $this->upsertChunk(
                'estudiantes_saae',
                $rowsActualizarNombre,
                ['numero_control'],
                ['nombre_completo', 'updated_at']
            );

            $mapControlToId = EstudiantesSaae::query()
                ->whereIn('numero_control', $controles)
                ->pluck('id', 'numero_control')
                ->all();


            // =====================================================
            // B) CATALOGO DE AREAS DE ESPECIALIDAD, NO se crean desde el Excel
            // =====================================================
            $catalogoAreas = AreasEspecialidadEstudiantesSaae::query()
                ->where('activo', true)
                ->get(['id', 'nombre']);

            if ($catalogoAreas->isEmpty()) {
                throw new \RuntimeException(
                    'No hay áreas de especialidad activas registradas. Primero regístralas y/o actívalas en el Catálogo académico, dentro de Configuración.'
                );
            }

            $mapAreaNormalizadaToId = [];
            foreach ($catalogoAreas as $area) {
                $mapAreaNormalizadaToId[$this->normalizarCatalogo($area->nombre)] = $area->id;
            }

            $areasNoReconocidas = [];


            // =====================================================
            // C) CATALOGO DE ESTATUS ESCOLARES, NO se crean desde el Excel
            // =====================================================
            $catalogoEstatus = EstatusEscolaresEstudiantesSaae::query()
                ->where('activo', true)
                ->get(['id', 'clave', 'nombre']);

            if ($catalogoEstatus->isEmpty()) {
                throw new \RuntimeException(
                    'No hay estatus escolares activos registrados. Primero registralos y/o activalos en el Catálogo academico, dentro de Configuración.'
                );
            }

            $mapEstatusNormalizadoToId = [];
            foreach ($catalogoEstatus as $estatus) {
                if (!empty($estatus->clave)) {
                    $mapEstatusNormalizadoToId[$this->normalizarCatalogo($estatus->clave)] = $estatus->id;
                }
                if (!empty($estatus->nombre)) {
                    $mapEstatusNormalizadoToId[$this->normalizarCatalogo($estatus->nombre)] = $estatus->id;
                }
            }

            $estatusNoReconocidos = [];


            // =====================================================
            // D) FICHA ESCOLAR DEL ESTUDIANTE
            // =====================================================
            $estudianteIds = array_values(array_unique(array_filter(array_values($mapControlToId))));

            $fichasExistentes = empty($estudianteIds)
                ? []
                : EstudianteConDatosEscolares::query()
                    ->whereIn('estudiante_id', $estudianteIds)
                    ->pluck('id', 'estudiante_id')
                    ->all();

            $rowsDatosEscolares = [];

            foreach ($estudiantesPorControl as $numeroControl => $data) {
                $estudianteId = $mapControlToId[$numeroControl] ?? null;
                if (!$estudianteId) {
                    continue;
                }

                $especialidadId = null;
                $especialidadRaw = trim((string) ($data['especialidad_nombre'] ?? ''));
                if ($especialidadRaw !== '') {
                    $especialidadId = $mapAreaNormalizadaToId[$this->normalizarCatalogo($especialidadRaw)] ?? null;

                    if (!$especialidadId) {
                        $areasNoReconocidas[$especialidadRaw] = true;
                    }
                }

                $estatusId = null;
                $estatusClave = trim((string) ($data['estatus_clave'] ?? ''));
                $estatusNombre = trim((string) ($data['estatus_nombre'] ?? ''));
                $estatusRaw = trim((string) ($data['estatus_raw'] ?? ''));

                if ($estatusClave !== '') {
                    $estatusId = $mapEstatusNormalizadoToId[$this->normalizarCatalogo($estatusClave)] ?? null;
                }

                if (!$estatusId && $estatusNombre !== '') {
                    $estatusId = $mapEstatusNormalizadoToId[$this->normalizarCatalogo($estatusNombre)] ?? null;
                }

                if (!$estatusId && $estatusRaw !== '') {
                    $estatusNoReconocidos[$estatusRaw] = true;
                }

                $rowsDatosEscolares[] = [
                    'estudiante_id' => $estudianteId,
                    'anio_ingreso' => $data['anio_ingreso'] ?? null,
                    'mes_ingreso' => $data['mes_ingreso'] ?? null,
                    'periodo_ingreso_texto' => $data['periodo_ingreso_texto'] ?? null,
                    'especialidad_id' => $especialidadId,
                    'estatus_escolar_id' => $estatusId,
                    'ultima_importacion_id' => $importacion->id,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $this->upsertChunk(
                'estudiantes_con_datos_escolares',
                $rowsDatosEscolares,
                ['estudiante_id'],
                [
                    'anio_ingreso',
                    'mes_ingreso',
                    'periodo_ingreso_texto',
                    'especialidad_id',
                    'estatus_escolar_id',
                    'ultima_importacion_id',
                    'updated_at',
                ]
            );

            $fichasInsertadas = 0;
            foreach ($rowsDatosEscolares as $row) {
                if (!isset($fichasExistentes[$row['estudiante_id']])) {
                    $fichasInsertadas++;
                }
            }

            $fichasActualizadas = max(count($rowsDatosEscolares) - $fichasInsertadas, 0);

            if (!empty($areasNoReconocidas)) {
                $advertencias[] = $this->construirAdvertenciaValoresNoReconocidos(
                    'área(s) de especialidad',
                    array_keys($areasNoReconocidas)
                );
            }

            if (!empty($estatusNoReconocidos)) {
                $advertencias[] = $this->construirAdvertenciaValoresNoReconocidos(
                    'estatus escolar(es)',
                    array_keys($estatusNoReconocidos)
                );
            }

            
            $advertencias = array_values(array_unique(array_filter($advertencias)));

            $resultadosImportacion = [
                'ok' => true,
                'mensaje' => 'Importación completada.',
                'filas_detectadas' => (int) ($resumenFuente['filas_detectadas'] ?? count($estudiantesPorControl)),
                'filas_omitidas_sin_numero_control' => (int) ($resumenFuente['filas_omitidas_sin_numero_control'] ?? 0),
                'estudiantes_insertados' => $estudiantesInsertados,
                'estudiantes_existentes_detectados' => max(count($controles) - $estudiantesInsertados, 0),
                'fichas_escolares_insertadas' => $fichasInsertadas,
                'fichas_escolares_actualizadas' => $fichasActualizadas,
                'areas_especialidad_no_reconocidas' => count($areasNoReconocidas),
                'estatus_escolares_no_reconocidos' => count($estatusNoReconocidos),
            ];

            $importacion->update([
                'advertencias' => empty($advertencias) ? null : $advertencias,
                'resultados_importacion' => $resultadosImportacion,
            ]);

            return $resultadosImportacion;
        });
    }




    private function construirAdvertenciaValoresNoReconocidos(string $tipo, array $valores): string
    {
        $valores = array_values(array_unique(array_filter(array_map(
            fn ($v) => trim((string) $v),
            $valores
        ))));

        $total = count($valores);
        $muestra = array_slice($valores, 0, 8);

        $mensaje = "Se detectaron {$total} {$tipo} no reconocido(s) en el Excel.";

        if (!empty($muestra)) {
            $mensaje .= ' Ejemplos: ' . implode(', ', $muestra) . '.';
        }

        $mensaje .= ' Revisa como debe(n) de estar en Catálogos académicos, dentro de Configuracion. Y corrige el archivo fuente.';

        return $mensaje;
    }


    private function normalizarCatalogo(string $texto): string
    {
        $texto = trim($texto);
        $texto = mb_strtoupper($texto, 'UTF-8');
        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $texto);
        $texto = $ascii !== false ? $ascii : $texto;
        $texto = preg_replace('/[^A-Z0-9]+/', '_', $texto);
        $texto = trim((string) $texto, '_');

        return $texto;
    }


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


    private function upsertChunk(string $table, array $rows, array $uniqueBy, array $updateColumns): void
    {
        if (empty($rows)) {
            return;
        }

        foreach (array_chunk($rows, $this->chunkSize) as $chunk) {
            DB::table($table)->upsert($chunk, $uniqueBy, $updateColumns);
        }
    }

}