<?php

namespace App\Http\Controllers\Personal\Configuracion\FuentesDatos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\ParserFuenteDatosEscolares;
use App\Models\FuenteDatosEscolares;

class RegistrarFuentesDatosParsersController extends Controller
{
    public function cargar_fuentes_datos_parsers() 
    {

        $parsers = ParserFuenteDatosEscolares::query()
            ->where('activo', true)
            ->orderByDesc('created_at')
            ->get(['id', 'nombre']);

        return view('personal.configuracion.fuentes_datos_parsers.fuentes_datos_parsers', compact('parsers'));
    }


    //===========REGISTRO DE FUENTES DE DATOS
    public function registrar_fuente_datos(Request $request) 
    {
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


        $data = $request->validate(
            [
                'nombre' => [
                    'required',
                    'string',
                    'max:150',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                    'unique:fuentes_datos_escolares,nombre'
                ],
                'descripcion' => [
                    'nullable',
                    'string',
                    'max:500',
                    "regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ\s.,;:¡!¿?()\"'._-]*$/u"
                ],
                'parser_id' => [
                    'required', 
                    'exists:parsers_fuentes_datos_escolares,id'
                ],
                'activo' => [
                    'required',
                    'boolean'
                ],
            ],
            [
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'nombre.unique' => 'Ese nombre del parser ya se encuentra en uso.',
                'parser_id.required' => 'El reloj necesita por lo menos 1 parser.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos básicos.',
            ]
        );


        $FuenteDatosEscolares = FuenteDatosEscolares::create([
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => (bool)($data['activo'] ?? true),
            'parser_fuente_dato_escolar_id' => (int) $data['parser_id'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Fuente de datos registrada correctamente.',
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
                    'unique:parsers_fuentes_datos_escolares,clave'
                ],
                'nombre' => [
                    'required',
                    'string',
                    'max:150',
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


        $parser = ParserFuenteDatosEscolares::create([
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
