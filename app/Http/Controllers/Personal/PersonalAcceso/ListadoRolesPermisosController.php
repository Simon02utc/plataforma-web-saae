<?php

namespace App\Http\Controllers\Personal\PersonalAcceso;

use App\Http\Controllers\Controller;
use App\Models\PermisosSaae;
use App\Models\RolesPersonalSaae;
use App\Services\Personal\NotificacionPersonalService; //Utilizacion del correo electronico para notificar
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Color;


class ListadoRolesPermisosController extends Controller
{
    
    public function cargar_roles_permisos_disponibles()
    {
        $permisos = PermisosSaae::orderBy('created_at')->get();//colocar los permisos y ponerlos por su fecha de creacion
    
        return view('personal.personal_acceso.roles_permisos', compact('permisos'));
    }


    //===========TABLA DE ROLES
    public function listado_roles(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = RolesPersonalSaae::query()
            ->where('clave', '!=', 'admin') //no mostrar el rol del administrador
            
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

            ->withCount('permisos')
            ->orderByDesc('created_at')
            ->get()
            ->map(function ($rol) {
                return [
                    'id' => $rol->id,
                    'clave_rol' => $rol->clave,
                    'nombre_rol' => $rol->nombre,
                    'descripcion_rol'=> $rol->descripcion,
                    'total_permisos' => $rol->permisos_count,
                    'creado_en'=> optional($rol->created_at)->toIso8601String(),
                    'editado_en'=> optional($rol->updated_at)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }


    public function ver_permisos_rol(int $id)
    {
        $rol = RolesPersonalSaae::with(['permisos:id,clave,nombre,created_at'])->findOrFail($id);

        if ($rol->clave === 'admin') {
            return response()->json([
                'message' => 'El rol administrador no se puede consultar desde esta sección.'
            ], 403);
        }

        return response()->json([
            'data' => [
                'id' => $rol->id,
                'clave' => $rol->clave,
                'nombre' => $rol->nombre,
                'total_permisos' => $rol->permisos->count(),
                'permisos_del_rol' => $rol->permisos
                    ->sortByDesc('created_at')
                    ->values()
                    ->map(function ($permiso) {
                        return [
                            'id' => $permiso->id,
                            'clave' => $permiso->clave,
                            'nombre' => $permiso->nombre,
                            'creado_en' => optional($permiso->created_at)->toIso8601String(),
                        ];
                    }),
            ]
        ]);
    }


    public function ver_rol(int $id)
    {
        $rol = RolesPersonalSaae::with(['permisos:id,clave,nombre'])->findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$rol) {
            return response()->json([
                'message' => 'Este rol ya no existe o fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        //No mostrar el rol del administrador
        if ($rol->clave === 'admin') {
            return response()->json([
                'message' => 'El rol administrador no se puede consultar desde esta sección.'
            ], 403);
        }

        $permisosDisponibles = PermisosSaae::query()
            ->orderByDesc('created_at')
            ->get(['id', 'clave', 'nombre', 'created_at']);

        return response()->json([
            'data' => [
                'id' => $rol->id,
                'clave' => $rol->clave,
                'nombre' => $rol->nombre,
                'descripcion' => $rol->descripcion,
                'permisos_seleccionados' => $rol->permisos->pluck('id')->values(),
                'permisos_disponibles' => $permisosDisponibles,
            ]
        ]);
    }


    public function editar_rol(Request $request, int $id)
    {
        $rol = RolesPersonalSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$rol) {
            return response()->json([
                'message' => 'No se puede guardar porque este rol ya fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        //No mostrar el rol del administrador
        if ($rol->clave === 'admin') {
            return response()->json([
                'message' => 'El rol administrador no se puede consultar desde esta sección.'
            ], 403);
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
                    'regex:/^[a-z]+(?:_[a-z]+)*$/',
                    Rule::unique((new RolesPersonalSaae())->getTable(), 'clave')->ignore($rol->id),
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
                    'array',
                    'min:1'
                ],
                'permisos.*' => [
                    'required',
                    'integer',
                    Rule::exists((new PermisosSaae())->getTable(), 'id'),
                ],
            ],
            [
                'clave.regex'  => 'La clave solo puede contener letras minúsculas y guion bajo (_) en los espacios. Ejem: director_tesis',
                'clave.unique' => 'Esa clave ya está registrada. Elige otra.',
                'nombre.regex' => 'El nombre solo puede contener letras y espacios.',
                'descripcion.regex' => 'La descripción solo puede contener texto y signos básicos.',
                'permisos.min' => 'El rol necesita por lo menos 1 permiso.',
            ]
        );


        DB::transaction(function () use ($rol, $validated) {
            $rol->update([
                'clave' => $validated['clave'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
            ]);

            $rol->permisos()->sync($validated['permisos']);
        });

        return response()->json([
            'message' => 'Rol actualizado correctamente.'
        ]);
    }


    public function eliminar_rol(int $id)
    {
        $rol = RolesPersonalSaae::withCount([
            'personal',
            'RolConAsignacion',
            'permisos',
        ])->findOrFail($id);

        $bloqueos = [];

        if ($rol->clave === 'admin') {
            return response()->json([
                'message' => 'No se puede eliminar el rol administrador.'
            ], 422);
        }

        if ($rol->personal_count > 0) {
            $bloqueos[] = 'miembros del personal';
        }

        if ($rol->rol_con_asignacion_count > 0) {
            $bloqueos[] = 'asignaciones estudiante-personal';
        }

        if (!empty($bloqueos)) {
            return response()->json([
                'message' => 'No se puede eliminar el rol porque está relacionado con: ' . implode(', ', $bloqueos) . '.'
            ], 409);
        }

        DB::transaction(function () use ($rol) {
            $rol->permisos()->detach(); //“Quita todas las relaciones de ese registro con la tabla pivote de permisos”
            $rol->delete();
        });

        return response()->json([
            'message' => 'Rol eliminado correctamente.'
        ]);
    }


    public function exportar_roles_excel(Request $request): StreamedResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $roles = RolesPersonalSaae::query()
            ->where('clave', '!=', 'admin')
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('clave', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
                });
            })
            ->with('permisos:id,nombre')
            ->withCount('permisos')
            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Roles');

        $colorPrincipal = '1B396A';
        $colorSecundarioSuave = 'EAF0F8';
        $colorBorde = 'C9D4E5';
        $colorTextoOscuro = '1F2937';
        $colorExitoSuave = 'E8F5E9';
        $colorAdvertenciaSuave = 'FFF8E1';

        // ===================== RESUMEN SUPERIOR =====================
        $sheet->setCellValue('A1', 'Sección');
        $sheet->setCellValue('B1', 'Personal, Roles y Permisos');

        $sheet->setCellValue('A2', 'Reporte');
        $sheet->setCellValue('B2', 'Listado de roles');

        $sheet->setCellValue('A3', 'Búsqueda aplicada');
        $sheet->setCellValue('B3', $buscar !== '' ? $buscar : 'Sin filtro');

        $sheet->setCellValue('A4', 'Total de roles exportados');
        $sheet->setCellValue('B4', $roles->count());

        $sheet->setCellValue('A5', 'Fecha de exportación');
        $sheet->setCellValue('B5', now('America/Mexico_City')->format('d/m/Y H:i:s'));

        $sheet->setCellValue('A6', 'Nota');
        $sheet->setCellValue('B6', 'No se incluye el rol administrador.');

        // ===================== ENCABEZADOS TABLA =====================
        $filaEncabezado = 8;

        $sheet->setCellValue("A{$filaEncabezado}", 'ID');
        $sheet->setCellValue("B{$filaEncabezado}", 'Clave');
        $sheet->setCellValue("C{$filaEncabezado}", 'Nombre');
        $sheet->setCellValue("D{$filaEncabezado}", 'Descripción');
        $sheet->setCellValue("E{$filaEncabezado}", 'Total permisos');
        $sheet->setCellValue("F{$filaEncabezado}", 'Permisos asignados');
        $sheet->setCellValue("G{$filaEncabezado}", 'Creado en');
        $sheet->setCellValue("H{$filaEncabezado}", 'Editado en');

        $fila = 9;

        foreach ($roles as $rol) {
            $permisosTexto = $rol->permisos->isNotEmpty()
                ? $rol->permisos->pluck('nombre')->implode(', ')
                : 'Sin permisos asignados';

            $sheet->setCellValue("A{$fila}", $rol->id);
            $sheet->setCellValue("B{$fila}", $rol->clave ?? '—');
            $sheet->setCellValue("C{$fila}", $rol->nombre ?? '—');
            $sheet->setCellValue("D{$fila}", $rol->descripcion ?? '—');
            $sheet->setCellValue("E{$fila}", (int) $rol->permisos_count);
            $sheet->setCellValue("F{$fila}", $permisosTexto);
            $sheet->setCellValue("G{$fila}", optional($rol->created_at)->timezone('America/Mexico_City')->format('d/m/Y H:i:s'));
            $sheet->setCellValue("H{$fila}", optional($rol->updated_at)->timezone('America/Mexico_City')->format('d/m/Y H:i:s'));

            $fila++;
        }

        $ultimaFila = max($fila - 1, $filaEncabezado);

        // ===================== ESTILOS =====================
        $sheet->getStyle('A1:B6')->getFont()->setBold(true);
        $sheet->getStyle('A1:B6')->getFont()->getColor()->setARGB($colorPrincipal);
        $sheet->getStyle('A1:B6')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorSecundarioSuave);

        $sheet->getStyle('A1:B6')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->getFont()->setBold(true);
        $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorPrincipal);

        $sheet->getStyle("A{$filaEncabezado}:H{$filaEncabezado}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension($filaEncabezado)->setRowHeight(24);

        $sheet->getStyle("A{$filaEncabezado}:H{$ultimaFila}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        $sheet->getStyle("A" . ($filaEncabezado + 1) . ":H{$ultimaFila}")
            ->getFont()->getColor()->setARGB($colorTextoOscuro);

        $sheet->getStyle("A" . ($filaEncabezado + 1) . ":A{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("E" . ($filaEncabezado + 1) . ":E{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("G" . ($filaEncabezado + 1) . ":H{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        // Ajuste visual opcional
        if ($roles->count() > 0) {
            for ($f = 9; $f <= $ultimaFila; $f++) {
                $totalPermisos = (int) $sheet->getCell("E{$f}")->getValue();

                if ($totalPermisos > 0) {
                    $sheet->getStyle("E{$f}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($colorExitoSuave);
                } else {
                    $sheet->getStyle("E{$f}")->getFill()
                        ->setFillType(Fill::FILL_SOLID)
                        ->getStartColor()->setARGB($colorAdvertenciaSuave);
                }
            }
        }

        $sheet->setAutoFilter("A{$filaEncabezado}:H{$ultimaFila}");
        $sheet->freezePane('A9');

        // Anchos
        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(22);
        $sheet->getColumnDimension('C')->setWidth(28);
        $sheet->getColumnDimension('D')->setWidth(38);
        $sheet->getColumnDimension('E')->setWidth(16);
        $sheet->getColumnDimension('F')->setWidth(55);
        $sheet->getColumnDimension('G')->setWidth(22);
        $sheet->getColumnDimension('H')->setWidth(22);

        $sheet->getStyle("B1:B6")->getAlignment()->setWrapText(true);
        $sheet->getStyle("B9:H{$ultimaFila}")->getAlignment()->setWrapText(true);

        $nombreArchivo = 'roles_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }




    //===========TABLA DE PERMISOS
    public function listado_permisos(Request $request)
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $items = PermisosSaae::query()

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
            ->map(function ($permiso) {
                return [
                    'id' => $permiso->id,
                    'clave' => $permiso->clave,
                    'nombre' => $permiso->nombre,
                    'descripcion'=> $permiso->descripcion,
                    'creado_en'=> optional($permiso->created_at)->toIso8601String(),
                    'editado_en'=> optional($permiso->updated_at)->toIso8601String(),
                ];
            });

        return response()->json(['data' => $items]);
    }


    public function ver_permiso(int $id)
    {
        $permiso = PermisosSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$permiso) {
            return response()->json([
                'message' => 'Este permiso ya no existe o fue eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        return response()->json([
            'data' => [
                'id' => $permiso->id,
                'clave' => $permiso->clave,
                'nombre' => $permiso->nombre,
                'descripcion' => $permiso->descripcion,
            ]
        ]);
    }


    public function editar_permiso(Request $request, int $id)
    {
        $permiso = PermisosSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$permiso) {
            return response()->json([
                'message' => 'No se puede guardar porque este permiso ya fue eliminado desde otro dispositivo. Actualiza la tabla.'
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
                    Rule::unique((new PermisosSaae())->getTable(), 'clave')->ignore($permiso->id),
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

        
        DB::transaction(function () use ($permiso, $validated) {
            $permiso->update([
                'clave' => $validated['clave'],
                'nombre' => $validated['nombre'],
                'descripcion' => $validated['descripcion'] ?? null,
            ]);
        });

        return response()->json([
            'message' => 'Permiso actualizado correctamente.'
        ]);
    }


    public function eliminar_permiso(int $id)
    {
        $permiso = PermisosSaae::findOrFail($id);

        //excepcion si ya no esta disponible o fue eliminado
        if (!$permiso) {
            return response()->json([
                'message' => 'Este permiso ya había sido eliminado desde otro dispositivo. Actualiza la tabla.'
            ], 404);
        }

        if ($permiso->roles()->exists()) {
            return response()->json([
                'message' => 'No se puede eliminar este permiso porque está asignado a uno o varios roles.'
            ], 409);
        }

        DB::transaction(function () use ($permiso) {
            $permiso->delete();
        });

        return response()->json([
            'message' => 'Permiso eliminado correctamente.'
        ]);
    }


    public function exportar_permisos_excel(Request $request): StreamedResponse
    {
        $buscar = trim((string) $request->input('buscar', ''));

        $permisos = PermisosSaae::query()
            ->when($buscar !== '', function ($query) use ($buscar) {
                $query->where(function ($q) use ($buscar) {
                    if (is_numeric($buscar)) {
                        $q->orWhere('id', (int) $buscar);
                    }

                    $q->orWhere('clave', 'like', "%{$buscar}%")
                    ->orWhere('nombre', 'like', "%{$buscar}%");
                });
            })
            ->withCount('roles')
            ->orderByDesc('created_at')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Permisos');

        $colorPrincipal = '1B396A';
        $colorSecundarioSuave = 'EAF0F8';
        $colorBorde = 'C9D4E5';
        $colorTextoOscuro = '1F2937';
        $colorInfoSuave = 'EEF4FF';

        // ===================== RESUMEN SUPERIOR =====================
        $sheet->setCellValue('A1', 'Sección');
        $sheet->setCellValue('B1', 'Personal, Roles y Permisos');

        $sheet->setCellValue('A2', 'Reporte');
        $sheet->setCellValue('B2', 'Listado de permisos');

        $sheet->setCellValue('A3', 'Búsqueda aplicada');
        $sheet->setCellValue('B3', $buscar !== '' ? $buscar : 'Sin filtro');

        $sheet->setCellValue('A4', 'Total de permisos exportados');
        $sheet->setCellValue('B4', $permisos->count());

        $sheet->setCellValue('A5', 'Fecha de exportación');
        $sheet->setCellValue('B5', now('America/Mexico_City')->format('d/m/Y H:i:s'));

        $sheet->setCellValue('A6', 'Nota');
        $sheet->setCellValue('B6', 'Reporte generado desde la sección de permisos.');

        // ===================== ENCABEZADOS =====================
        $filaEncabezado = 8;

        $sheet->setCellValue("A{$filaEncabezado}", 'ID');
        $sheet->setCellValue("B{$filaEncabezado}", 'Clave');
        $sheet->setCellValue("C{$filaEncabezado}", 'Nombre');
        $sheet->setCellValue("D{$filaEncabezado}", 'Descripción');
        $sheet->setCellValue("E{$filaEncabezado}", 'Roles que lo usan');
        $sheet->setCellValue("F{$filaEncabezado}", 'Creado en');
        $sheet->setCellValue("G{$filaEncabezado}", 'Editado en');

        $fila = 9;

        foreach ($permisos as $permiso) {
            $sheet->setCellValue("A{$fila}", $permiso->id);
            $sheet->setCellValue("B{$fila}", $permiso->clave ?? '—');
            $sheet->setCellValue("C{$fila}", $permiso->nombre ?? '—');
            $sheet->setCellValue("D{$fila}", $permiso->descripcion ?? '—');
            $sheet->setCellValue("E{$fila}", (int) $permiso->roles_count);
            $sheet->setCellValue("F{$fila}", optional($permiso->created_at)->timezone('America/Mexico_City')->format('d/m/Y H:i:s'));
            $sheet->setCellValue("G{$fila}", optional($permiso->updated_at)->timezone('America/Mexico_City')->format('d/m/Y H:i:s'));

            $fila++;
        }

        $ultimaFila = max($fila - 1, $filaEncabezado);

        // ===================== ESTILOS =====================
        $sheet->getStyle('A1:B6')->getFont()->setBold(true);
        $sheet->getStyle('A1:B6')->getFont()->getColor()->setARGB($colorPrincipal);
        $sheet->getStyle('A1:B6')->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorSecundarioSuave);

        $sheet->getStyle('A1:B6')->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        $sheet->getStyle("A{$filaEncabezado}:G{$filaEncabezado}")->getFont()->setBold(true);
        $sheet->getStyle("A{$filaEncabezado}:G{$filaEncabezado}")->getFont()->getColor()->setARGB(Color::COLOR_WHITE);
        $sheet->getStyle("A{$filaEncabezado}:G{$filaEncabezado}")->getFill()
            ->setFillType(Fill::FILL_SOLID)
            ->getStartColor()->setARGB($colorPrincipal);

        $sheet->getStyle("A{$filaEncabezado}:G{$filaEncabezado}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getRowDimension($filaEncabezado)->setRowHeight(24);

        $sheet->getStyle("A{$filaEncabezado}:G{$ultimaFila}")->getBorders()->getAllBorders()
            ->setBorderStyle(Border::BORDER_THIN)
            ->getColor()->setARGB($colorBorde);

        $sheet->getStyle("A9:G{$ultimaFila}")->getFont()->getColor()->setARGB($colorTextoOscuro);

        $sheet->getStyle("A9:A{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        $sheet->getStyle("E9:G{$ultimaFila}")->getAlignment()
            ->setHorizontal(Alignment::HORIZONTAL_CENTER)
            ->setVertical(Alignment::VERTICAL_CENTER);

        if ($permisos->count() > 0) {
            $sheet->getStyle("E9:E{$ultimaFila}")->getFill()
                ->setFillType(Fill::FILL_SOLID)
                ->getStartColor()->setARGB($colorInfoSuave);
        }

        $sheet->setAutoFilter("A{$filaEncabezado}:G{$ultimaFila}");
        $sheet->freezePane('A9');

        $sheet->getColumnDimension('A')->setWidth(10);
        $sheet->getColumnDimension('B')->setWidth(28);
        $sheet->getColumnDimension('C')->setWidth(34);
        $sheet->getColumnDimension('D')->setWidth(42);
        $sheet->getColumnDimension('E')->setWidth(18);
        $sheet->getColumnDimension('F')->setWidth(22);
        $sheet->getColumnDimension('G')->setWidth(22);

        $sheet->getStyle("B1:B6")->getAlignment()->setWrapText(true);
        $sheet->getStyle("B9:G{$ultimaFila}")->getAlignment()->setWrapText(true);

        $nombreArchivo = 'permisos_' . now('America/Mexico_City')->format('Ymd_His') . '.xlsx';

        return response()->streamDownload(function () use ($spreadsheet) {
            $writer = new Xlsx($spreadsheet);
            $writer->save('php://output');
        }, $nombreArchivo, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0, no-cache, no-store, must-revalidate',
            'Pragma' => 'public',
        ]);
    }


}
