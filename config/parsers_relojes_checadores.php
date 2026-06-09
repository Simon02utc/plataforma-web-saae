<?php

// NOTA: yaa hay archivos con la plantilla para reutilizar en nuevos parsers, donde su Archivo se
// - Excel: ParserRelojChecadorNuevoPlantillaArchivoXlsx.php
// - CSV para el tipo matriz: ParserRelojChecadorNuevoModeloArchivoTipoMatriz.php
// - CSV para el tipo log: ParserRelojChecadorNuevoModeloArchivoCsvTipoLog.php

return [
    'parser_reloj_checador_on_the_minute_archivo_xlsx' => 
    \App\Services\ModuloImportacion\ParsersAsistencia\ParserRelojChecadorOnTheMinuteArchivoXlsx::class,

    'parser_reloj_dos_ejemplo' => \App\Services\ModuloImportacion\ParsersAsistencia\ParserOtroRelojEjemplo::class, //no utilizable

];