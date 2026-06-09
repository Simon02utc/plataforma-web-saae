<?php

namespace App\Services\ModuloImportacion\ParsersAsistencia;

use App\Models\Periodo;

//Le dice a todos los parsers: “deben tener un motodo parsear() que reciba:
// - archivo, 
// - tipo de importación 
// - periodo opcional (segun el tipo de importacion), 
// - que devuelva un arreglo

interface AsistenciaParserInterface
{
    public function parsear(
        string $rutaFisicaArchivo,
        string $tipoImportacion,
        ?Periodo $periodo = null
    ): array;
}

// NOTA: yaa hay archivos con la plantilla para reutilizar en nuevos parsers, donde su Archivo es
// - Excel: ParserRelojChecadorNuevoPlantillaArchivoXlsx.php
// - CSV para el tipo matriz: ParserRelojChecadorNuevoModeloArchivoTipoMatriz.php
// - CSV para el tipo log: ParserRelojChecadorNuevoModeloArchivoCsvTipoLog.php