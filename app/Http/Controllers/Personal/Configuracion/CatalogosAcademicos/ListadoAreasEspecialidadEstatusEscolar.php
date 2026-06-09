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

class ListadoAreasEspecialidadEstatusEscolar extends Controller
{
    
    public function ver_catalogos_academicos() {

        return view('personal.configuracion.catalogos_academicos.areas_especialidad_estatus_escolar');
    }


    //===========TABLA DE AREAS DE ESPECIALIDAD
    public function listado_areas_especialidad(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = AreasEspecialidadEstudiantesSaae::query()
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
            ->map(function ($area) {
                return [
                    'id' => $area->id,
                    'clave_area' => $area->clave,
                    'nombre_area' => $area->nombre,
                    'estado_area' => $area->activo,
                    'creado_en'=> optional($area->created_at)->toIso8601String(),
                    'editado_en'=> optional($area->updated_at)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }


    public function ver_area_especialidad(int $id)
    {
        $area = AreasEspecialidadEstudiantesSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$area) {
            return response()->json([
                'message' => 'Esta área ya no existe o fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $area->id,
                'clave_area' => $area->clave,
                'nombre_area' => $area->nombre,
                'estado_area' => (bool) $area->activo,
            ]
        ]);
    }


    public function editar_area_especialidad(Request $request, int $id)
    {
        $area = AreasEspecialidadEstudiantesSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$area) {
            return response()->json([
                'message' => 'No se puede guardar porque esta área ya fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

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

        $validated = $request->validate(
            [
                'clave' => [
                    'required',
                    'string',
                    'max:100',
                    'regex:/^[a-z]+(?:_[a-z]+)*$/',
                    Rule::unique((new AreasEspecialidadEstudiantesSaae())->getTable(), 'clave')->ignore($area->id),
                ],
                'nombre' => [
                    'required', 
                    'string', 
                    'max:150',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                    Rule::unique((new AreasEspecialidadEstudiantesSaae())->getTable(), 'nombre')->ignore($area->id),
                ],
                'activo' => [
                    'required',
                    'boolean'
                ],
            ],
            [
                'clave.regex'  => 'La clave solo puede contener letras minúsculas y guion bajo (_) en los espacios. Ejem: ingenieria_de_software',
                'clave.unique' => 'Esa clave ya está registrada. Elige otra.',
                'nombre.unique' => 'El nombre ya se encuentra en uso. Verificalo.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
            ]
        );


        DB::transaction(function () use ($area, $validated) {
            $area->update([
                'clave' => $validated['clave'],
                'nombre' => $validated['nombre'],
                'activo' => (bool)($validated['activo'] ?? true),
            ]);

        });

        return response()->json([
            'message' => 'Área actualizado correctamente.'
        ]);
    }


    public function eliminar_area_especialidad(int $id)
    {
        $area = AreasEspecialidadEstudiantesSaae::findOrFail($id);

        if (!empty($bloqueos)) {
            return response()->json([
                'message' => 'No se puede eliminar la área de especialidad porque está relacionado con: ' . implode(', ', $bloqueos) . '.'
            ], 409);
        }

        if ($area->especialidadesConEstudiantes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar esta área de especialidad porque está asignada a fichas escolares de estudiantes.'
            ], 409);
        }

        DB::transaction(function () use ($area) {
            $area->delete();
        });

        return response()->json([
            'message' => 'Área de especialidad eliminada correctamente.'
        ]);
    }




    //===========TABLA DE ESTATUS ESCOLARES
    public function listado_estatus_escolares(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = EstatusEscolaresEstudiantesSaae::query()

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
            ->map(function ($estatus) {
                return [
                    'id' => $estatus->id,
                    'clave_estatus' => $estatus->clave,
                    'nombre_estatus' => $estatus->nombre,
                    'descripcion'=> $estatus->descripcion,
                    'estado_estatus' => $estatus->activo,
                    'creado_en'=> optional($estatus->created_at)->toIso8601String(),
                    'editado_en'=> optional($estatus->updated_at)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }


    public function ver_estatus_escolar(int $id)
    {
        $estatus = EstatusEscolaresEstudiantesSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$estatus) {
            return response()->json([
                'message' => 'Este estatus escolar ya no existe o fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $estatus->id,
                'clave_estatus' => $estatus->clave,
                'nombre_estatus' => $estatus->nombre,
                'descripcion' => $estatus->descripcion,
                'estado_estatus' => $estatus->activo,
            ]
        ]);
    }


    public function editar_estatus_escolar(Request $request, int $id)
    {
        $estatus = EstatusEscolaresEstudiantesSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$estatus) {
            return response()->json([
                'message' => 'No se puede guardar porque este estatus ya fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

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
                ->upper()
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
                    'max:60',
                    'regex:/^[a-z]+(?:[_][a-z]+)*$/',
                    Rule::unique((new EstatusEscolaresEstudiantesSaae())->getTable(), 'clave')->ignore($estatus->id),
                ],
                'nombre' => [
                    'required', 
                    'string', 
                    'max:120',
                    'regex:/^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$/u',
                    Rule::unique((new EstatusEscolaresEstudiantesSaae())->getTable(), 'nombre')->ignore($estatus->id),
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
                'clave.regex'  => 'La clave solo puede contener letras minúsculas, puntos (.) y guiones bajo (_) en los espacios. Ejem: no_inscrito',
                'clave.unique' => 'Esa clave ya está registrada. Verificalo.',
                'nombre.unique' => 'El nombre ya se encuentra en uso. Verificalo.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos básicos.',
            ]
        );

        
        DB::transaction(function () use ($estatus, $validated) {
            $estatus->update([
                'clave' => $validated['clave'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
                'activo' => (bool)($validated['activo'] ?? true),
            ]);
        });

        return response()->json([
            'message' => 'Estatus escolar actualizado correctamente.'
        ]);
    }


    public function eliminar_estatus_escolar(int $id)
    {
        $estatus = EstatusEscolaresEstudiantesSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$estatus) {
            return response()->json([
                'message' => 'Este estatus escolar ya había sido eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        if ($estatus->estatusEscolarConEstudiantes()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este estatus porque está asignado a fichas escolares de estudiantes.'
            ], 409);
        }

        DB::transaction(function () use ($estatus) {
            $estatus->delete();
        });

        return response()->json([
            'message' => 'Estatus escolar eliminado correctamente.'
        ]);
    }


}
