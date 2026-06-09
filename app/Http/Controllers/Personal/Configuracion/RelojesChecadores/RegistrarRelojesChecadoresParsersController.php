<?php

namespace App\Http\Controllers\Personal\Configuracion\RelojesChecadores;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ParserRelojChecador;
use App\Models\RelojChecador;
use Illuminate\Support\Str;

class RegistrarRelojesChecadoresParsersController extends Controller
{

    public function cargar_relojes_checadores_parsers() 
    {

        $parsers = ParserRelojChecador::query()
            ->where('activo', true)
            ->orderByDesc('created_at')
            ->get(['id', 'nombre']);

        return view('personal.configuracion.relojes_checadores_parsers.relojes_checadores_parsers', compact('parsers'));
    }


    //===========REGISTRO DEL RELOJ CHECADOR
    public function registrar_reloj_checador(Request $request) 
    {
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


        $data = $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                    'unique:relojes_checadores,nombre'
                ],
                'ubicacion' => [
                    'nullable',
                    'string',
                    'max:500',
                    "regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?()\"'._-]*$/u"
                ],
                'parser_id' => [
                    'required', 
                    'exists:parsers_relojes_checadores,id'
                ],
                'activo' => [
                    'required',
                    'boolean'
                ],
            ],
            [
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'nombre.unique' => 'Ese nombre del parser ya se encuentra en uso.',
                'ubicacion.regex' => 'La ubicación solo puede contener texto y signos de puntuación.',
                'parser_id.required' => 'El reloj necesita por lo menos 1 parser.',
            ]
        );


        $relojChecador = RelojChecador::create([
            'nombre' => $data['nombre'],
            'ubicacion' => $data['ubicacion'] ?? null,
            'activo' => (bool)($data['activo'] ?? true),
            'parser_reloj_checador_id' => (int) $data['parser_id'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Reloj registrado correctamente.',
        ], 201);
    }


    //===========REGISTRO DEL PARSER
    public function registrar_parser(Request $request) 
    {
                //Verificar los datos
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


        $data = $request->validate(
            [
                'clave' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[a-z]+(?:_[a-z]+)*$/',
                    'unique:parsers_relojes_checadores,clave'
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
                'clave.regex'  => 'La clave solo puede contener letras minúsculas y guion bajo (_) en los espacios. Ejem: reloj_on_the_minute',
                'clave.unique' => 'Esa clave ya está registrada. Elige otra.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos básicos.',
            ]
        );


        $parser = ParserRelojChecador::create([
            'clave' => $data['clave'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => (bool)($data['activo'] ?? true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Parser registrado correctamente.',
        ], 201);
    }
}
