<?php

namespace App\Http\Controllers\Personal\AuditoriaSeguridad;

use App\Http\Controllers\Controller;
use App\Models\ImportacionAsistencia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HistorialImportacionesAsistenciaController extends Controller
{
    public function historial_simple_importaciones_asistencia(Request $request)
    {
        $perPage = (int) $request->query('per_page', 50);
        $perPage = in_array($perPage, [20, 50, 100, 200], true) ? $perPage : 50;

        $paginado = ImportacionAsistencia::query()
            ->with([
                'periodo:id,nombre,fecha_inicio,fecha_fin',
                'reloj:id,nombre',
                'importador:id,nombre,email',
            ])
            ->orderByDesc('importado_en')
            ->paginate($perPage);

        $items = collect($paginado->items())->map(function ($importacion) {
            return [
                'id' => $importacion->id,
                'archivo' => $importacion->archivo_nombre,
                'periodo' => $importacion->periodo?->nombre,
                'reloj' => $importacion->reloj?->nombre,
                'tipo_importacion' => $importacion->tipo_importacion,
                'estado' => $importacion->estado ?: 'OK',
                'importado_en' => optional($importacion->importado_en)
                    ?->timezone('America/Mexico_City')
                    ->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'data' => [
                'data' => $items,
                'meta' => [
                    'current_page' => $paginado->currentPage(),
                    'last_page'    => $paginado->lastPage(),
                    'per_page'     => $paginado->perPage(),
                    'total'        => $paginado->total(),
                    'from'         => $paginado->firstItem(),
                    'to'           => $paginado->lastItem(),
                ],
            ],
        ]);
    }

    public function ver_detalles_importacion_asistencia(int $id)
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
                'importado_en' => optional($importacion->importado_en)
                    ?->timezone('America/Mexico_City')
                    ->toIso8601String(),
                'importado_por' => $importacion->importador?->nombre
                    ? ($importacion->importador->nombre . ' (' . $importacion->importador->email . ')')
                    : null,
                'estado_importacion' => $importacion->estado ?: 'OK',
                'advertencias_importacion' => $importacion->advertencias,
                'resultados_importacion' => $importacion->resultados_importacion,
                'notas' => $importacion->notas,
            ]
        ]);
    }

    public function descargar_archivo_importacion_asistencia($id)
    {
        $imp = ImportacionAsistencia::select('id', 'archivo_nombre', 'archivo_ruta')->findOrFail($id);

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

        $downloadName = $imp->archivo_nombre;

        return Storage::download($imp->archivo_ruta, $downloadName);
    }
}