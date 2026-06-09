<?php

namespace App\Http\Controllers\Personal\Configuracion\CatalogosAcademicos;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\AreasEspecialidadEstudiantesSaae;
use App\Models\EstatusEscolaresEstudiantesSaae;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

class RegistrarAreasEspecialidadEstatusEscolar extends Controller
{
    

    public function registrar_areas_especialidad(Request $request)
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
                ->upper()
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
                    'unique:areas_especialidad_estudiantes_saae,clave'
                ],
                'nombre' => [
                    'required',
                    'string',
                    'max:150',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                    'unique:areas_especialidad_estudiantes_saae,nombre'
                ],
                'activo' => [
                    'required',
                    'boolean'
                ],
            ],
            [
                'clave.regex'  => 'La clave solo puede contener letras minúsculas y guion bajo (_) en los espacios. Ej: baja_temporal',
                'clave.unique' => 'Esa clave ya está registrada. Elige otra.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'nombre.unique' => 'El nombre ya se encuentra en uso. Verificalo.',
            ]
        );


        $area = AreasEspecialidadEstudiantesSaae::create([
            'clave' => $data['clave'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => (bool)($data['activo'] ?? true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Área de especialidad registrada correctamente.',
        ], 201);
    }



    public function registrar_estatus_escolares(Request $request)
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
                ->upper()
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
                    'max:60',
                    'regex:/^[a-z]+(?:_[a-z]+)*$/',
                    'unique:estatus_escolares_estudiantes_saae,clave'
                ],
                'nombre' => [
                    'required',
                    'string',
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                    'unique:estatus_escolares_estudiantes_saae,nombre'
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
                'clave.regex'  => 'La clave solo puede contener letras minúsculas y guion bajo (_) en los espacios. Ejem: director_tesis',
                'clave.unique' => 'Esa clave ya está registrada. Elige otra.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'nombre.unique' => 'El nombre ya se encuentra en uso. Verificalo.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos de puntuación.',
            ]
        );


        $estatus = EstatusEscolaresEstudiantesSaae::create([
            'clave' => $data['clave'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
            'activo' => (bool)($data['activo'] ?? true),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Estatus escolar registrado correctamente.',
        ], 201);
    }
}
