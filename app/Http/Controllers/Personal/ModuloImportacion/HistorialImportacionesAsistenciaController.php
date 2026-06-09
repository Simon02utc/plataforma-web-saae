<?php

namespace App\Http\Controllers\Personal\ModuloImportacion;

use App\Http\Controllers\Controller;
use App\Models\ImportacionAsistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


class HistorialImportacionesAsistenciaController extends Controller
{
    public function historial_simple_importaciones_asistencia(Request $request)
    {
        $limit = (int) $request->query('limit', 10);
        $limit = max(1, min($limit, 50)); // cap para no abusar

        $items = ImportacionAsistencia::query()
            ->with([
                'periodo:id,nombre,fecha_inicio,fecha_fin',
                'reloj:id,nombre',
                'importador:id,nombre,email',
            ])
            ->orderByDesc('importado_en')
            ->limit($limit)
            ->get()
            ->map(function ($importacion) {
                return [
                    'id' => $importacion->id,
                    'archivo' => $importacion->archivo_nombre,
                    'periodo' => $importacion->periodo?->nombre,
                    'reloj' => $importacion->reloj?->nombre,
                    'tipo_importacion' => $importacion->tipo_importacion,
                    'estado' => $importacion->estado ?: 'OK',
                    'importado_en'=> optional($importacion->importado_en)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }


    public function ver_detalles_importacion_asistencia_simple(int $id)
    {
        $importacion = ImportacionAsistencia::query()
            ->with([
                'periodo:id,nombre,fecha_inicio,fecha_fin',
                'reloj:id,nombre',
                'importador:id,nombre,email',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $importacion->id,
                'archivo' => $importacion->archivo_nombre,
                'hash' => $importacion->archivo_hash,
                'periodo' => $importacion->periodo?->nombre,
                'reloj' => $importacion->reloj?->nombre,
                'tipo_importacion' => $importacion->tipo_importacion,
                'parser_clave_reloj' => $importacion->parser_clave,
                'hojas_detectadas_perser_reloj' => $importacion->hojas_detectadas,
                'importado_en' => optional($importacion->importado_en)->toIso8601String(),
                'importado_por'=> $importacion->importador?->nombre
                                    ? ($importacion->importador->nombre.' ('.$importacion->importador->email.')')
                                    : null,
                'estado_importacion' => $importacion->estado ?: 'OK',
                'advertencias_importacion' => $importacion->advertencias,
                'resultados_importacion'=> $importacion->resultados_importacion,
                'notas' => $importacion->notas,
            ]
        ]);
    }


    public function descargar_archivo_importacion_asistencia_simple($id)
    {
        $imp = ImportacionAsistencia::select('id','archivo_nombre','archivo_ruta')->findOrFail($id);

        if (empty($imp->archivo_ruta)) {
            return response()->json([
                    'message' => 'Esta importación no tiene archivo asociado.'
                ], 404);
        }

        if (!Storage::exists($imp->archivo_ruta)) {
            return response()->json([
                    'message' => 'El archivo ya no existe en el servidor.'
                ], 404);
        }

        //con el nombre sugerido para descarga (usar el mismo archivo_nombre)
        $downloadName = $imp->archivo_nombre;
    
        //PARA ENCAJAR CON EL JS: Laravel responde con: 1. contenido binario del archivo  2. headers de descarga (incluye content-disposition con filename)
        return Storage::download($imp->archivo_ruta, $downloadName);
    }
}
