<?php

namespace App\Http\Controllers\Personal\Configuracion\RelojesChecadores;

use App\Http\Controllers\Controller;
use App\Models\ParserRelojChecador;
use App\Models\RelojChecador;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class ListadoRelojesChecadoresParsersController extends Controller
{

    //===========TABLA DE RELOJES CHECADORES
    public function listado_relojes(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = RelojChecador::query()
            //buscador
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('nombre', 'like', "%{$buscar}%");
                });
            })

            ->with('parser:id')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($reloj) {
                return [
                    'id' => $reloj->id,
                    'nombre_reloj' => $reloj->nombre,
                    'ubicacion_reloj'=> $reloj->ubicacion,
                    'total_parsers' => $reloj->parser ? 1 : 0,
                    'estado_reloj' => $reloj->activo,
                    'creado_en'=> optional($reloj->created_at)->toIso8601String(),
                    'editado_en'=> optional($reloj->updated_at)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }


    public function ver_parsers_reloj(int $id)
    {
        $reloj = RelojChecador::with(['parser:id,clave,nombre,created_at'])->findOrFail($id);

        $parser = $reloj->parser;

        return response()->json([
            'data' => [
                'id' => $reloj->id,
                'nombre_reloj' => $reloj->nombre,
                'ubicacion_reloj' => $reloj->ubicacion,
                'total_parsers' => $parser ? 1 : 0,
                'parsers_del_reloj' => $parser ? [
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


    public function ver_reloj(int $id)
    {
        $reloj = RelojChecador::with(['parser:id,clave,nombre'])->findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$reloj) {
            return response()->json([
                'message' => 'Este reloj ya no existe o fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        $parsersDisponibles = ParserRelojChecador::query()
            ->where('activo', true)
            ->orderByDesc('created_at')
            ->get(['id', 'clave', 'nombre']);

        return response()->json([
            'data' => [
                'id' => $reloj->id,
                'nombre_reloj' => $reloj->nombre,
                'ubicacion' => $reloj->ubicacion,
                'estado_reloj' => (bool) $reloj->activo,
                'parser_seleccionado_id' => $reloj->parser?->id,
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


    public function editar_reloj(Request $request, int $id)
    {
        $reloj = RelojChecador::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$reloj) {
            return response()->json([
                'message' => 'No se puede guardar porque este reloj ya fue eliminado desde otro dispositivo. Actualiza la tabla.'
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

            'ubicacion' => Str::of($request->input('ubicacion',''))
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
                    Rule::unique((new RelojChecador())->getTable(), 'nombre')->ignore($reloj->id),
                ],
                'ubicacion' => [
                    'nullable', 
                    'string', 
                    'max:500',
                    "regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?()\"'._-]*$/u"
                ],
                'parser_id' => [
                    'required', 
                    Rule::exists((new ParserRelojChecador())->getTable(), 'id'),
                ],
                'activo' => [
                    'required',
                    'boolean'
                ],
            ],
            [
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'nombre.unique' => 'Ese nombre del parser ya se encuentra en uso.',
                'ubicacion.regex' => 'La ubicación solo puede contener texto y signos básicos.',
                'parser_id.required' => 'El reloj necesita por lo menos 1 parser.',
                'parser_id.exists' => 'El parser seleccionado ya no esta disponible o fue eliminado.',
            ]
        );


        DB::transaction(function () use ($reloj, $validated) {
            $reloj->update([
                'nombre' => $validated['nombre'],
                'ubicacion' => $validated['ubicacion'] ?? null,
                'activo' => (bool)($validated['activo'] ?? true),
                'parser_reloj_checador_id' => (int) $validated['parser_id'],
            ]);
        });

        return response()->json([
            'message' => 'Reloj actualizado correctamente.'
        ]);
    }


    public function eliminar_reloj(int $id)
    {
        $reloj = RelojChecador::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$reloj) {
            return response()->json([
                'message' => 'Este reloj ya había sido eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        if ($reloj->inscripciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este reloj porque tiene registros de inscripciones, los cuales son muy importantes para auditoría.'
            ], 409);
        }

        if ($reloj->importaciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este reloj porque tiene registros de importaciones, los cuales son muy importantes para auditoría.'
            ], 409);
        }

        if ($reloj->marcaciones()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este reloj porque tiene registros de marcaciones, los cuales son muy importantes para auditoría.'
            ], 409);
        }

        if ($reloj->asistenciasDiarias()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este reloj porque tiene registros de asistencia diaria, los cuales son muy importantes para auditoría.'
            ], 409);
        }


        DB::transaction(function () use ($reloj) {
            $reloj->delete();
        });

        return response()->json([
            'message' => 'Reloj eliminado correctamente.'
        ]);
    }




    //===========TABLA DE PARSERS
    public function listado_parsers(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = ParserRelojChecador::query()

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
        $parser = ParserRelojChecador::findOrFail($id);

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
        $parser = ParserRelojChecador::findOrFail($id);

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
                    Rule::unique((new ParserRelojChecador())->getTable(), 'clave')->ignore($parser->id),
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
                'clave.regex'  => 'La clave solo puede contener letras minúsculas) y guiones bajo (_) en los espacios. Ejem: reloj_on_the_minute.',
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
        $parser = ParserRelojChecador::findOrFail($id);
        
        //excepcion si ya no esta disponible o fue eliminado
        if (!$parser) {
            return response()->json([
                'message' => 'Este parser ya había sido eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }


        if ($parser->relojesChecadores()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este parser porque está asignado a uno o varios relojes.'
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
