<?php

namespace App\Services\ModuloImportacion\ParsersDatosEscolares;


//Le dice a todos los parsers: “deben tener un motodo parsear() que reciba:
// - archivo, 
// - tipo de importación 
// - que devuelva un arreglo

interface DatosEscolaresParserInterface
{
    public function parsear(
        string $rutaFisicaArchivo,
        string $tipoImportacion,
    ): array;
}