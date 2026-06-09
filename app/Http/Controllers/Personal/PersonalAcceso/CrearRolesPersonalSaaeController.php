<?php

namespace App\Http\Controllers\Personal\PersonalAcceso;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\RolesPersonalSaae;
use App\Models\PermisosSaae;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;


class CrearRolesPersonalSaaeController extends Controller
{

    public function crear_roles(Request $request)
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
                    'unique:roles_personal_saae,clave'
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
                'permisos' => [
                    'required',
                    'array'
                ],
                'permisos.*' => [
                    'integer',
                    'exists:permisos_saae,id'
                ],
            ],
            [
                'clave.regex'  => 'La clave solo puede contener letras minúsculas y guion bajo (_) en los espacios. Ejem: director_tesis',
                'clave.unique' => 'Esa clave ya está registrada. Elige otra.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos de puntuación.',
                'permisos.required' => 'El rol necesita por lo menos 1 permiso.',
            ]
        );


        $rol = RolesPersonalSaae::create([
            'clave' => $data['clave'],
            'nombre' => $data['nombre'],
            'descripcion' => $data['descripcion'] ?? null,
        ]);

        //se mandan los permisos, asigna (1 o varios)
        if (!empty($data['permisos'])) {
            $rol->permisos()->sync($data['permisos']);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Rol registrado correctamente.',
            //Para mandar el Rol creado y colocarlo automaticamnete en el formulario
            'rol' => [
                'id' => $rol->id,
                'clave' => $rol->clave,
                'nombre' => $rol->nombre,
            ],//
        ], 201);
    }
}