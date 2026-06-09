<?php

namespace App\Services\ModuloImportacion\ParsersDatosEscolares;

use App\Models\FuenteDatosEscolares;
use RuntimeException;

//Este archivo solo tiene una responsabilidad: segun la fuente de datos seleccionado, decidir que parser usar para leer el archivo y extraer la info

class DatosEscolaresParserResolver
{
    public function resolver(FuenteDatosEscolares $fuente): DatosEscolaresParserInterface
    {
        $clave = $fuente->fuentesConParsers?->clave;

        if (!$clave) {
            throw new \RuntimeException(
                "La fuente '{$fuente->nombre}' no tiene un parser asociado. Verifica su parser asignado."
            );
        }
        
        //se encuentra en config/parsers_fuentes_datos_escolares.php
        $mapa = config('parsers_fuentes_datos_escolares', []);

        if (!array_key_exists($clave, $mapa)) {
            throw new \RuntimeException(
                "La clave de parser '{$clave}' no está registrada en config/parsers_fuentes_datos_escolares.php."
            );
        }

        $clase = $mapa[$clave];

        if (!class_exists($clase)) {
            throw new \RuntimeException(
                "La clase parser '{$clase}' no existe o no se puede autoloadear (cargador automáticamente)."
            );
        }

        $parser = app($clase);

        if (!$parser instanceof DatosEscolaresParserInterface) {
            throw new \RuntimeException(
                "La clase '{$clase}' no implementa DatosEscolaresParserInterface."
            );
        }

        return $parser;
    }
}