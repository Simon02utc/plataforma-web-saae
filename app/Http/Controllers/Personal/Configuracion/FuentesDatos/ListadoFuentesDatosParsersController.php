<?php

namespace App\Http\Controllers\Personal\Configuracion\FuentesDatos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParserFuenteDatosEscolares;
use App\Models\FuenteDatosEscolares;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;


class ListadoFuentesDatosParsersController extends Controller
{

    //===========TABLA DE FUENTES DE DATOS
    public function listado_fuentes_datos(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = FuenteDatosEscolares::query()
            //buscador
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('nombre', 'like', "%{$buscar}%");
                });
            })

            ->with('fuentesConParsers:id')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($fuenteDatos) {
                return [
                    'id' => $fuenteDatos->id,
                    'nombre_fuente' => $fuenteDatos->nombre,
                    'descripcion_fuente'=> $fuenteDatos->descripcion,
                    'total_parsers' => $fuenteDatos->fuentesConParsers ? 1 : 0,
                    'estado_fuente' => $fuenteDatos->activo,
                    'creado_en'=> optional($fuenteDatos->created_at)->toIso8601String(),
                    'editado_en'=> optional($fuenteDatos->updated_at)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }

    
    public function ver_parsers_fuente_datos(int $id)
    {
        $fuenteDatos = FuenteDatosEscolares::with(['fuentesConParsers:id,clave,nombre,created_at'])->findOrFail($id);

        $parser = $fuenteDatos->fuentesConParsers;

        return response()->json([
            'data' => [
                'id' => $fuenteDatos->id,
                'nombre_fuente_datos' => $fuenteDatos->nombre,
                'descripcion_fuente_datos' => $fuenteDatos->descripcion,
                'total_parsers' => $parser ? 1 : 0,
                'parsers_de_fuente_datos' => $parser ? [
                    [
                        'id' => $parser->id,
                        'clave' => $parser->clave,
                        'nombre_parser' => $parser->nombre,
                        'creado_en' => optional($parser->created_at)->toIso8601String(),
                    ]
                ] : [],
            ]
        ]);
    }


    public function ver_fuente_datos(int $id)
    {
        $fuenteDatos = FuenteDatosEscolares::with(['fuentesConParsers:id,clave,nombre'])->findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$fuenteDatos) {
            return response()->json([
                'message' => 'Esta fuente de datos ya no existe o fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        $parsersDisponibles = ParserFuenteDatosEscolares::query()
            ->where('activo', true)
            ->orderByDesc('created_at')
            ->get(['id', 'clave', 'nombre']);

        return response()->json([
            'data' => [
                'id' => $fuenteDatos->id,
                'nombre_fuente_datos' => $fuenteDatos->nombre,
                'descripcion_fuente_datos' => $fuenteDatos->descripcion,
                'estado_fuente_datos' => (bool) $fuenteDatos->activo,
                'parser_seleccionado_id' => $fuenteDatos->fuentesConParsers?->id,
                'parsers_disponibles' => $parsersDisponibles->map(function ($parser) {
                    return [
                        'id' => $parser->id,
                        'clave' => $parser->clave, //arreglar el diseño en el JS para que sea mas chica la clave
                        'nombre_parser' => $parser->nombre,
                    ];
                })->values(),
            ]
        ]);
    }


    public function editar_fuente_datos(Request $request, int $id)
    {
        $fuenteDatos = FuenteDatosEscolares::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$fuenteDatos) {
            return response()->json([
                'message' => 'No se puede guardar porque esta fuente de datos ya fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        //Verificar los datos
        $request->merge([
            'nombre' => Str::of($request->input('nombre',''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '')
                ->trim()
                ->title()
                ->toString(),
            
            'descripcion' => Str::of($request->input('descripcion',''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches("/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?()\"'._-]/u", '')
                ->trim()
                ->toString(),

        ]);

        $validated = $request->validate(
            [
                'nombre' => [
                    'required', 
                    'string', 
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                    Rule::unique((new FuenteDatosEscolares())->getTable(), 'nombre')->ignore($fuenteDatos->id),
                ],
                'descripcion' => [
                    'nullable', 
                    'string', 
                    'max:500',
                    "regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?()\"'._-]*$/u"
                ],
                'parser_id' => [
                    'required', 
                    Rule::exists((new ParserFuenteDatosEscolares())->getTable(), 'id'),
                ],
                'activo' => [
                    'required',
                    'boolean'
                ],
            ],
            [
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'nombre.unique' => 'Ese nombre del parser ya se encuentra en uso.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos básicos.',
                'parser_id.required' => 'La Fuente de datos necesita por lo menos 1 parser.',
                'parser_id.exists' => 'El parser seleccionado ya no esta disponible o fue eliminado.',
            ]
        );


        DB::transaction(function () use ($fuenteDatos, $validated) {
            $fuenteDatos->update([
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'activo' => (bool)($validated['activo'] ?? true),
                'parser_fuente_dato_escolar_id' => (int) $validated['parser_id'],
            ]);
        });

        return response()->json([
            'message' => 'Fuente de datos actualizada correctamente.'
        ]);
    }


    public function eliminar_fuente_datos(int $id)
    {
        $fuenteDatos = FuenteDatosEscolares::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$fuenteDatos) {
            return response()->json([
                'message' => 'Esta fuente de datos ya había sido eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        if ($fuenteDatos->fuentesConImportaciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar esta fuente de datos porque tiene registros de importaciones, los cuales son muy importantes para auditoría.'
            ], 409);
        }


        DB::transaction(function () use ($fuenteDatos) {
            $fuenteDatos->delete();
        });

        return response()->json([
            'message' => 'Fuente de datos eliminada correctamente..'
        ]);
    }




    //===========TABLA DE PARSERS
    public function listado_parsers(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = ParserFuenteDatosEscolares::query()

            //buscador
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('clave', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
                });
            })

            ->orderByDesc('created_at')
            ->get()
            ->map(function ($parser) {
                return [
                    'id' => $parser->id,
                    'clave' => $parser->clave,
                    'nombre' => $parser->nombre,
                    'descripcion' => $parser->descripcion,
                    'estado_parser' => $parser->activo,
                    'creado_en'=> optional($parser->created_at)->toIso8601String(),
                    'editado_en'=> optional($parser->updated_at)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }


    public function ver_parser(int $id)
    {
        $parser = ParserFuenteDatosEscolares::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$parser) {
            return response()->json([
                'message' => 'Este parser ya no existe o fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $parser->id,
                'clave' => $parser->clave,
                'nombre' => $parser->nombre,
                'descripcion' => $parser->descripcion,
                'estado_parser' => (bool) $parser->activo,
            ]
        ]);
    }


    public function editar_parser(Request $request, int $id)
    {
        $parser = ParserFuenteDatosEscolares::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$parser) {
            return response()->json([
                'message' => 'No se puede guardar porque este parser ya fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        $request->merge([
            'clave' => Str::of($request->input('clave', ''))
                ->lower()
                ->replaceMatches('/\s+/', '_')//los es espacios los hace en _
                ->replaceMatches('/[^a-z_]/', '')//solo letras y _
                ->replaceMatches('/_+/', '_') //no doble guion __ solo 1  _
                ->trim('_')//no guion _ al inici, ni al fin
                ->toString(),
            
            'nombre' => Str::of($request->input('nombre',''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches('/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s]/u', '')
                ->trim()
                ->title()
                ->toString(),

            'descripcion' => Str::of($request->input('descripcion',''))
                ->replaceMatches('/\s+/', ' ')
                ->replaceMatches("/[^A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?()\"'._-]/u", '')
                ->trim()
                ->toString(),

        ]);

        $validated = $request->validate(
            [
                'clave' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[a-z]+(?:[._][a-z]+)*$/',
                    Rule::unique((new ParserFuenteDatosEscolares())->getTable(), 'clave')->ignore($parser->id),
                ],
                'nombre' => [
                    'required', 
                    'string', 
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u'
                ],
                'descripcion' => [
                    'nullable', 
                    'string', 
                    'max:500',
                    "regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?()\"'._-]*$/u"
                ],
                'activo' => [
                    'required',
                    'boolean'
                ],
            ],
            [
                'clave.regex'  => 'La clave solo puede contener letras minúsculas) y guiones bajo (_) en los espacios. Ejem: parser_fuente_concentrado_estudiantes_xlsx.',
                'clave.unique' => 'Esa clave ya está registrada. Verificala.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos básicos.',
            ]
        );

        
        DB::transaction(function () use ($parser, $validated) {
            $parser->update([
                'clave' => $validated['clave'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'activo' => (bool) $validated['activo'],
            ]);
        });

        return response()->json([
            'message' => 'Parser actualizado correctamente.'
        ]);
    }


    public function eliminar_parser(int $id)
    {
        $parser = ParserFuenteDatosEscolares::findOrFail($id);
        
        //excepcion si ya no esta disponible o fue eliminado
        if (!$parser) {
            return response()->json([
                'message' => 'Este parser ya había sido eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }


        if ($parser->parsersConFuentesDatosEscolares()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este parser porque está asignado a uno o varias fuentes de datos.'
            ], 409);
        }

        DB::transaction(function () use ($parser) {
            $parser->delete();
        });

        return response()->json([
            'message' => 'Parser eliminado correctamente.'
        ]);
    }

}
