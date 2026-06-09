<?php

namespace App\Http\Controllers\Personal\ModuloImportacion;

use App\Http\Controllers\Controller;
use App\Models\FuenteDatosEscolares;
use App\Services\ModuloImportacion\ImportarDatosEscolaresService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ImportacionDatosEscolaresController extends Controller
{
    
    public function ver_importacion_datos_escolares() 
    {
        $fuentesDatosEscolares = FuenteDatosEscolares::query()
            ->where('activo', true)
            ->orderBy('created_at')
            ->get(['id', 'nombre']);

        return view ('personal.modulo_importacion.importacion_datos_escolares', compact('fuentesDatosEscolares'));
    }


    public function ejecutar_importacion_datos_escolares(Request $request, ImportarDatosEscolaresService $service)
    {
        //Verificar los datos
        $request->merge([
            'notas' => Str::of($request->input('notas',''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches("/[^0-9A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?()\"'._-]/u", '')
                ->trim()
                ->toString(),
        ]);


        $data = $request->validate(
            [
                'archivo_importacion' => ['required', 'file', 'mimes:xls,xlsx', 'max:10240'], //10 MB = 10240 KB
                'tipo_importacion' => ['required', Rule::in(['COMPLETA', 'SOLO_ESTUDIANTES'])],
                'fuente_datos_escolares_id' => ['required', 'exists:fuentes_datos_escolares,id'],
                'notas' => ['nullable','string','max:500'],
            ],
            [
                'archivo_importacion.required' => 'Es obligatorio el Archivo para la importación.',
                'tipo_importacion.required' => 'Es obligatorio seleccionar el Tipo de importacion.',
                'fuente_datos_escolares_id.required' => 'Es obligatorio seleccionar una fuente de datos escolares (de donde biene el archivo).',
                'notas.max' => 'La nota no puede exceder de 500 caracteres.',
            ]
        );

        $archivo = $request->file('archivo_importacion');

        //evitar caracteres raros, rutas, etc. y hace nombres unicos
        $ext = $archivo->getClientOriginalExtension();
        $nombre = now()->format('Ymd_His').'_'.bin2hex(random_bytes(8)).'.'.$ext;

        $rutaStorageArchivo = $archivo->storeAs('importaciones_datos_escolares', $nombre, 'local');
        $rutaFisicaArchivo = Storage::disk('local')->path($rutaStorageArchivo);

        $importadoPor = Auth::guard('personal')->id(); // o ->user()->id
        $notas = $data['notas'] ?? null;

        try {
            $resultado = $service->importar(
                $rutaFisicaArchivo, 
                $rutaStorageArchivo, 
                $data['tipo_importacion'],
                (int) $data['fuente_datos_escolares_id'],
                $importadoPor, 
                $notas
            );
            
            // Si fue duplicado (ok=false), borramos el archivo
            if (!$resultado['ok']) {
                Storage::delete($rutaStorageArchivo);
            }

            return back()->with('resultado', $resultado);

        } catch (\RuntimeException $e) {
            //bora el archivo si algo falla
            Storage::delete($rutaStorageArchivo);

            return back()->with('resultado', [
                'ok' => false,
                'mensaje' => $e->getMessage(),
            ]);

        } catch (\Throwable $e) {
            //bora el archivo si algo falla
            Storage::delete($rutaStorageArchivo);

            //guarda el error en logs
            report($e);

            return back()->with('resultado', [
                'ok' => false,
                'mensaje' => 'Ocurrió un error al importar el archivo. Verifica el formato del archivo e inténtalo de nuevo.',
            ]);
        }

    }

}