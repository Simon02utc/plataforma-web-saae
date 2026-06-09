<?php

namespace App\Http\Controllers\Personal\Plantillas;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PlantillaDatosEscolaresController extends Controller
{
    

    public function ver_plantilla_datos_escolares()
    {
        $ruta = storage_path('app/public/plantillas/PLANTILL_2026-XXXX.xlsx');
        
        if (!file_exists($ruta)) {
            abort(404, 'Plantilla no encontrada');
        }

        return response()->file($ruta);
    }


    public function descargar_plantilla_datos_escolares()
{
        $ruta = storage_path('app/public/plantillas/PLANTILLA_2026-XXXX.xlsx');

        if (!file_exists($ruta)) {
            abort(404, 'Plantilla no encontrada');
        }

        return response()->download($ruta, 'PLANTILLA_2026-XXXX.xlsx');

    }
}
