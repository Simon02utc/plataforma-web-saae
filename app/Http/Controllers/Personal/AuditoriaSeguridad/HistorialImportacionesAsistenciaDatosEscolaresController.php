<?php

namespace App\Http\Controllers\Personal\AuditoriaSeguridad;

use App\Http\Controllers\Controller;
use App\Models\ImportacionAsistencia;
use App\Models\ImportacionDatosEscolares;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class HistorialImportacionesAsistenciaDatosEscolaresController extends Controller
{
    
    public function ver_historial_modulo_importaciones()
    {
        return view('personal.auditoria_seguridad.historial_modulo_importaciones');
    }


    //===========TABLA DE IMPORTACIONES DE ASISTENCIA
    public function historial_importaciones_asistencia(Request $request)
    {   
        $buscar = trim((string) $request->query('buscar', ''));

        $perPage = (int) $request->query('per_page', 50);
        $perPage = in_array($perPage, [20, 50, 100, 200], true) ? $perPage : 50;

        $query = ImportacionAsistencia::query()
            ->with([
                'reloj:id,nombre',
                'importador:id,nombre,email',
            ]);

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                if (ctype_digit($buscar)) {
                    $q->orWhere('id', (int) $buscar);
                }

                $q->orWhere('archivo_nombre', 'like', "%{$buscar}%")
                ->orWhere('tipo_importacion', 'like', "%{$buscar}%")
                ->orWhere('estado', 'like', "%{$buscar}%")
                ->orWhereHas('reloj', function ($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%{$buscar}%");
                });
            });
        }

        $paginado = $query
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
                'importado_en' => optional($importacion->importado_en)->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page'    => $paginado->lastPage(),
                'per_page'     => $paginado->perPage(),
                'total'        => $paginado->total(),
                'from'         => $paginado->firstItem(),
                'to'           => $paginado->lastItem(),
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



    //===========TABLA DE IMPORTACIONES DATOS ESCOLARES
    public function historial_importaciones_datos_escolares(Request $request)
    {   
        $buscar = trim((string) $request->query('buscar', ''));

        $perPage = (int) $request->query('per_page', 50);
        $perPage = in_array($perPage, [20, 50, 100, 200], true) ? $perPage : 50;

        $query = ImportacionDatosEscolares::query()
            ->with([
                'importacionesConFuentesDatosEscolares:id,nombre',
                'importacionesDelPersonal:id,nombre,email',
            ]);

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                if (ctype_digit($buscar)) {
                    $q->orWhere('id', (int) $buscar);
                }

                $q->orWhere('archivo_nombre', 'like', "%{$buscar}%")
                ->orWhere('tipo_importacion', 'like', "%{$buscar}%")
                ->orWhere('estado', 'like', "%{$buscar}%")
                ->orWhereHas('importacionesConFuentesDatosEscolares', function ($sub) use ($buscar) {
                    $sub->where('nombre', 'like', "%{$buscar}%");
                });
            });
        }

        $paginado = $query
            ->orderByDesc('importado_en')
            ->paginate($perPage);

        $items = collect($paginado->items())->map(function ($importacion) {
            return [
                'id' => $importacion->id,
                'archivo' => $importacion->archivo_nombre,
                'fuente_datos_escolares' => $importacion->importacionesConFuentesDatosEscolares?->nombre,
                'tipo_importacion' => $importacion->tipo_importacion,
                'estado' => $importacion->estado ?: 'OK',
                'importado_en' => optional($importacion->importado_en)->toIso8601String(),
            ];
        })->values();

        return response()->json([
            'data' => $items,
            'meta' => [
                'current_page' => $paginado->currentPage(),
                'last_page'    => $paginado->lastPage(),
                'per_page'     => $paginado->perPage(),
                'total'        => $paginado->total(),
                'from'         => $paginado->firstItem(),
                'to'           => $paginado->lastItem(),
            ],
        ]);
    }


    public function ver_detalles_importacion_datos_escolares(int $id)
    {
        $importacion = ImportacionDatosEscolares::query()
            ->with([
                'importacionesConFuentesDatosEscolares:id,nombre',
                'importacionesDelPersonal:id,nombre,email',
            ])
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $importacion->id,
                'archivo' => $importacion->archivo_nombre,
                'fuente_datos_escolares' => $importacion->importacionesConFuentesDatosEscolares?->nombre,
                'tipo_importacion' => $importacion->tipo_importacion,
                'parser_clave_fuente_datos' => $importacion->parser_clave,
                'hojas_detectadas_perser_fuente_datos' => $importacion->hojas_detectadas,
                'importado_en' => optional($importacion->importado_en)->toIso8601String(),
                'importado_por'=> $importacion->importacionesDelPersonal?->nombre
                                    ? ($importacion->importacionesDelPersonal->nombre.' ('.$importacion->importacionesDelPersonal->email.')')
                                    : null,
                'estado_importacion' => $importacion->estado ?: 'OK',
                'advertencias_importacion' => $importacion->advertencias,
                'resultados_importacion'=> $importacion->resultados_importacion,
                'notas' => $importacion->notas,
            ]
        ]);
    }


    public function descargar_archivo_importacion_datos_escolares($id)
    {
        $imp = ImportacionDatosEscolares::select('id','archivo_nombre','archivo_ruta')->findOrFail($id);

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
