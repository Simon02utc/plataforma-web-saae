<?php

namespace App\Services\ModuloImportacion\ParsersAsistencia;

use App\Models\RelojChecador;
use RuntimeException;

//Este archivo solo tiene una responsabilidad: segun el reloj checador seleccionado, decidir que parser usar para leer el archivo y extraer la info

// NOTA: yaa hay archivo con la plantilla para reutilizar en nuevos parsers, donde su Archivo se
// - Excel: ParserRelojChecadorNuevoPlantillaArchivoXlsx.php
// - CSV para el tipo matriz: ParserRelojChecadorNuevoModeloArchivoTipoMatriz.php
// - CSV para el tipo log: ParserRelojChecadorNuevoModeloArchivoCsvTipoLog.php
class AsistenciaParserResolver
{
    public function resolver(RelojChecador $reloj): AsistenciaParserInterface
    {
        $clave = $reloj->parser?->clave;

        if (!$clave) {
            throw new \RuntimeException(
                "El reloj '{$reloj->nombre}' no tiene un parser asociado."
            );
        }
        
        //se encuentra en config/parsers_relojes_checadores.php
        $mapa = config('parsers_relojes_checadores', []);

        if (!array_key_exists($clave, $mapa)) {
            throw new \RuntimeException(
                "La clave de parser '{$clave}' no está registrada en config/parsers_relojes_checadores.php."
            );
        }

        $clase = $mapa[$clave];

        if (!class_exists($clase)) {
            throw new \RuntimeException(
                "La clase parser '{$clase}' no existe o no se puede autoloadear (cargador automáticamente)."
            );
        }

        $parser = app($clase);

        if (!$parser instanceof AsistenciaParserInterface) {
            throw new \RuntimeException(
                "La clase '{$clase}' no implementa AsistenciaParserInterface."
            );
        }

        return $parser;
    }
}