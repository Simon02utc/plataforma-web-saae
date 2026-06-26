<?php

namespace App\Http\Controllers\Estudiante\Justificantes;

use App\Http\Controllers\Controller;
use App\Models\AsistenciaDiaria;
use App\Models\JustificanteEstudiante;
use App\Models\JustificanteEstudianteDetalle;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class JustificantesEstudianteController extends Controller
{

    //=========== METODO PRIVADOS DE APOYO
    private function resolverEstatusAsistenciaTexto($asistencia): string
    {
        if (!$asistencia) {
            return '—';
        }

        if ($asistencia->estatus === 'FALTA' && $asistencia->justificada) {
            return 'JUSTIFICADA';
        }

        return $asistencia->estatus ?? '—';
    }

    private function generarFolio(): string
    {
        do {
            $folio = 'JUST-' . now()->format('Ymd') . '-' . strtoupper(Str::random(6));
        } while (JustificanteEstudiante::where('folio', $folio)->exists());

        return $folio;
    }



    //=========== TABLA DE JUSTIFICANTES
    public function tabla_justificantes(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $buscar = trim((string) $request->input('buscar', ''));
        $estado = $request->input('estado', '');
        $perPage = (int) $request->input('per_page', 20);

        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;

        $query = JustificanteEstudiante::query()
            ->with(['periodo', 'detalles'])
            ->where('estudiante_id', $estudianteId);

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', "%{$buscar}%")
                    ->orWhere('motivo', 'like', "%{$buscar}%")
                    ->orWhere('estado', 'like', "%{$buscar}%")
                    ->orWhere('comentario_revision', 'like', "%{$buscar}%");
            });
        }

        if ($estado !== '') {
            $query->where('estado', $estado);
        }

        $paginado = $query
            ->latest()
            ->paginate($perPage);

        $paginado->getCollection()->transform(function ($j) {
            return [
                'id' => $j->id,
                'folio' => $j->folio,
                'motivo' => $j->motivo,
                'estado_justificante' => $j->estado,
                'comentario_revision' => $j->comentario_revision,
                'created_at' => optional($j->created_at)->format('Y-m-d H:i:s'),
                'periodo' => [
                    'id' => $j->periodo?->id,
                    'nombre' => $j->periodo?->nombre,
                ],
                'detalles' => $j->detalles->map(function ($d) {
                    return [
                        'id' => $d->id,
                        'fecha' => optional($d->fecha)->format('Y-m-d'),
                        'estatus_original' => $d->estatus_original,
                    ];
                })->values(),
            ];
        });

        return response()->json($paginado);
    }


    public function faltas_disponibles(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $asistenciasConJustificante = JustificanteEstudianteDetalle::query()
            ->whereHas('justificante', function ($q) {
                $q->whereIn('estado', [
                    'PENDIENTE',
                    'APROBADO',
                    // 'RECHAZADO',
                    // 'CANCELADO',
                ]);
            })
            ->pluck('asistencia_diaria_id');

        $query = AsistenciaDiaria::query()
            ->with('periodo')
            ->where('estudiante_id', $estudianteId)
            ->where('estatus', 'FALTA')
            ->where('justificada', false)
            ->whereNotIn('id', $asistenciasConJustificante);

        if ($request->filled('periodo_id')) {
            $query->where('periodo_id', (int) $request->input('periodo_id'));
        }

        $faltas = $query
            ->orderByDesc('fecha')
            ->get()
            ->map(function ($a) {
                return [
                    'id' => $a->id,
                    'periodo_id' => $a->periodo_id,
                    'periodo' => $a->periodo?->nombre,
                    'fecha' => optional($a->fecha)->format('Y-m-d'),
                    'estatus_asistencia' => $a->estatus,
                    'fuente' => $a->fuente,
                    'primera_entrada' => optional($a->primera_entrada)->format('Y-m-d H:i:s'),
                    'ultima_salida' => optional($a->ultima_salida)->format('Y-m-d H:i:s'),
                    'conteo_marcaciones' => $a->conteo_marcaciones,
                ];
            });

        return response()->json([
            'data' => $faltas,
        ]);
    }


    public function guardar_enviar_justificante(Request $request)
    {
        $estudianteId = auth('estudiante')->id();

        $data = $request->validate(
            [
                'motivo' => ['required', 'string', 'max:150'],
                'descripcion' => ['nullable', 'string', 'max:2000'],
                'asistencias_ids' => ['required', 'array', 'min:1'],
                'asistencias_ids.*' => ['required', 'integer', 'exists:asistencia_diaria,id'],
                'archivo' => [
                    'required',
                    'file',
                    'mimes:pdf,jpg,jpeg,png',
                    'mimetypes:application/pdf,image/jpeg,image/png',
                    'max:6144'
                ],
            ],
            [
                'motivo.required' => 'Escribe el motivo de tu justificante.',

                'asistencias_ids.required' => 'Selecciona al menos una falta a justificar.',
                'asistencias_ids.array' => 'Las faltas seleccionadas no son válidas.',
                'asistencias_ids.min' => 'Selecciona al menos una falta.',

                'archivo.required' => 'Debes adjuntar un archivo para validar tu justificante.',
                'archivo.file' => 'El archivo enviado no es válido.',
                'archivo.mimes' => 'Solo se permiten archivos PDF, JPG, JPEG o PNG.',
                'archivo.max' => 'El tamaño máximo permitido es de 6 MB.',

                'descripcion.max' => 'La descripción no puede superar los 2000 caracteres.',
            ]
        );

        return DB::transaction(function () use ($request, $data, $estudianteId) {

            $asistencias = AsistenciaDiaria::query()
                ->whereIn('id', $data['asistencias_ids'])
                ->where('estudiante_id', $estudianteId)
                ->where('estatus', 'FALTA')
                ->where('justificada', false)
                ->lockForUpdate()
                ->get();

            if ($asistencias->count() !== count($data['asistencias_ids'])) {
                return response()->json([
                    'message' => 'Una o más faltas no son válidas para justificar.'
                ], 422);
            }

            $periodos = $asistencias->pluck('periodo_id')->unique();

            if ($periodos->count() !== 1) {
                return response()->json([
                    'message' => 'Solo puedes enviar un justificante por faltas del mismo periodo.'
                ], 422);
            }

            $yaTieneJustificante = JustificanteEstudianteDetalle::query()
                ->whereIn('asistencia_diaria_id', $asistencias->pluck('id'))
                ->whereHas('justificante', function ($q) {
                    $q->whereIn('estado', [
                        'PENDIENTE',
                        'APROBADO',
                        // 'RECHAZADO',
                        // 'CANCELADO',
                    ]);
                })
                ->exists();

            if ($yaTieneJustificante) {
                return response()->json([
                    'message' => 'Una o más faltas ya tienen justificante pendiente o aprobado.'
                ], 422);
            }

            $archivoRuta = null;
            $archivoNombre = null;

            if ($request->hasFile('archivo')) {

                $archivo = $request->file('archivo');

                $ext = $archivo->getClientOriginalExtension();
                $archivoNombre = now()->format('Ymd_His').'_'.bin2hex(random_bytes(8)).'.'.$ext;

                $archivoRuta = $archivo->storeAs('justificantes_estudiantes', $archivoNombre, 'local');
            }

            $justificante = JustificanteEstudiante::create([
                'estudiante_id' => $estudianteId,
                'periodo_id' => $periodos->first(),
                'folio' => $this->generarFolio(),
                'motivo' => $data['motivo'],
                'descripcion' => $data['descripcion'] ?? null,
                'archivo_ruta' => $archivoRuta,
                'archivo_nombre' => $archivoNombre,
                'estado' => 'PENDIENTE',
            ]);

            foreach ($asistencias as $asistencia) {
                JustificanteEstudianteDetalle::create([
                    'justificante_id' => $justificante->id,
                    'asistencia_diaria_id' => $asistencia->id,
                    'fecha' => $asistencia->fecha,
                    'estatus_original' => $asistencia->estatus,
                ]);
            }

            return response()->json([
                'message' => 'Justificante enviado correctamente.',
                'justificante_id' => $justificante->id,
            ]);
        });
    }


    public function ver_detalles_justificante(int $id)
    {
        $estudianteId = auth('estudiante')->id();

        $justificante = JustificanteEstudiante::query()
            ->with(['periodo', 'detalles.asistencia', 'revisor'])
            ->where('estudiante_id', $estudianteId)
            ->findOrFail($id);

        return response()->json([
            'data' => [
                'id' => $justificante->id,
                'folio' => $justificante->folio,
                'estado_justificante' => $justificante->estado,
                'motivo' => $justificante->motivo,
                'descripcion' => $justificante->descripcion,
                'comentario_revision' => $justificante->comentario_revision,
                'archivo_nombre' => $justificante->archivo_nombre,

                'archivo_url' => $justificante->archivo_ruta
                    ? route('grup_estudiante.grup_justificantes.name_ver_archivo_justificante', $justificante->id)
                    : null,

                'archivo_descarga_url' => $justificante->archivo_ruta
                    ? route('grup_estudiante.grup_justificantes.name_descargar_archivo_justificante', $justificante->id)
                    : null,

                'periodo' => $justificante->periodo?->nombre,
                'fechas' => $justificante->detalles->map(function ($d) {
                    return [
                        'fecha' => optional($d->fecha)->format('Y-m-d'),
                        'estatus_asistencia' => $this->resolverEstatusAsistenciaTexto($d->asistencia),
                    ];
                })->values(),
            ]
        ]);
    }


    public function ver_archivo_justificante(int $id)
    {
        $estudianteId = auth('estudiante')->id();

        $justificante = JustificanteEstudiante::where('estudiante_id', $estudianteId)
            ->findOrFail($id);

        if (empty($justificante->archivo_ruta)) {
            return response()->json([
                'message' => 'No hay un archivo asociado.'
            ], 404);
        }

        if (!Storage::exists($justificante->archivo_ruta)) {
            return response()->json([
                'message' => 'El archivo ya no existe.'
            ], 404);
        }

        $rutaCompleta = Storage::path($justificante->archivo_ruta);

        return response()->file($rutaCompleta);
    }


    public function descargar_archivo_justificante(int $id)
    {
        $estudianteId = auth('estudiante')->id();

        $justificante = JustificanteEstudiante::where('estudiante_id', $estudianteId)
            ->findOrFail($id);

        if (empty($justificante->archivo_ruta)) {
            return response()->json([
                'message' => 'No hay un archivo asociado para descargar.'
            ], 404);
        }

        if (!Storage::exists($justificante->archivo_ruta)) {
            return response()->json([
                'message' => 'El archivo ya no existe para descargar.'
            ], 404);
        }

        $downloadName = $justificante->archivo_nombre;

        return Storage::download($justificante->archivo_ruta, $downloadName);
    }


}
