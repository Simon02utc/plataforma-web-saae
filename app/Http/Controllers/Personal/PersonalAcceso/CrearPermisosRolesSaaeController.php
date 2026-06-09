<?php

namespace App\Http\Controllers\Personal\PersonalAcceso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\PermisosSaae;
use Illuminate\Support\Str;


class CrearPermisosRolesSaaeController extends Controller
{
    public function crear_permisos(Request $request) {
        //Verificar los datos
        $request->merge([
            'clave' => Str::of($request->input('clave', ''))
                ->lower()
                ->replaceMatches('/\s+/', '_')//los es espacios los hace en _
                ->replaceMatches('/[^a-z_.]/', '')//solo letras, _ y .
                ->replaceMatches('/_+/', '_') //no doble guion __ solo 1  _
                ->replaceMatches('/\.+/', '.')// no doble punto solo 1
                ->trim('_.')//no guion _ ni . al inici, ni al fin
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
                    'regex:/^[a-z]+(?:[._][a-z]+)*$/',
                    'unique:permisos_saae,clave'
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
            ],
            [
                'clave.regex'  => 'La clave solo puede contener letras minúsculas, puntos (.) y guiones bajo (_) en los espacios. Ejem: "estudiantes.ver" o "auditoria_seguridad.ver"',
                'clave.unique' => 'Esa clave ya está registrada. Verificala.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos básicos.',
            ]
        );

        $permiso = PermisosSaae::create($data);

        return response()->json([
            'status' => 'success',
            'message' => 'Permiso registrado correctamente.',
            //Para mandar el Permiso creado y colocarlo automaticamnete en el formulario del rol
            'permiso' => [
                'id' => $permiso->id,
                'clave' => $permiso->clave,
                'nombre' => $permiso->nombre,
            ],//
        ], 201);
    }

}