<?php

namespace App\Http\Controllers\Personal\Justificantes;

use App\Http\Controllers\Controller;
use App\Models\AsistenciaDiaria;
use App\Models\PersonalSaae;
use App\Models\EstudianteConPersonalSaae;
use App\Models\JustificanteEstudiante;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class JustificantesPersonalController extends Controller
{

    //=========== METODO PRIVADOS DE APOYO
    private function validarAccesoPersonal(
        int $personalId,
        int $estudianteId,
        bool $esAdmin = false
    ): void
    {
        if ($esAdmin) {
            return;
        }

        $asignado = EstudianteConPersonalSaae::query()
            ->where('personal_id', $personalId)
            ->where('estudiante_id', $estudianteId)
            ->where('activo', true)
            ->exists();

        abort_if(!$asignado, 403, 'No tienes acceso a este justificante.');
    }

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


    // ================== TABLA DE JUSTIFICANTES ==================
    public function tabla_justificantes(Request $request)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $buscar = trim((string) $request->input('buscar', ''));
        $estado = $request->input('estado', '');
        $perPage = (int) $request->input('per_page', 20);
        $perPage = in_array($perPage, [10, 20, 50, 100]) ? $perPage : 20;

        $query = JustificanteEstudiante::query()
            ->with(['estudiante', 'periodo', 'detalles']);

        if (!$esAdmin) {

            $estudiantesAsignados = EstudianteConPersonalSaae::query()
                ->where('personal_id', $personalId)
                ->where('activo', true)
                ->pluck('estudiante_id');

            $query->whereIn('estudiante_id', $estudiantesAsignados);
        }

        if ($estado !== '') {
            $query->where('estado', $estado);
        }

        if ($buscar !== '') {
            $query->where(function ($q) use ($buscar) {
                $q->where('folio', 'like', "%{$buscar}%")
                    ->orWhereHas('estudiante', function ($qe) use ($buscar) {
                        $qe->where('numero_control', 'like', "%{$buscar}%")
                            ->orWhere('nombre_completo', 'like', "%{$buscar}%")
                            ->orWhere('nombre', 'like', "%{$buscar}%")
                            ->orWhere('apellidos', 'like', "%{$buscar}%");
                    });
            });
        }

        $paginado = $query->latest()->paginate($perPage);

        $paginado->getCollection()->transform(function ($j) {
            $nombre = $j->estudiante?->nombre_completo
                ?: trim(($j->estudiante?->nombre ?? '') . ' ' . ($j->estudiante?->apellidos ?? ''));

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
                'estudiante' => [
                    'id' => $j->estudiante?->id,
                    'numero_control' => $j->estudiante?->numero_control,
                    'nombre_completo' => $nombre ?: '—',
                    'email' => $j->estudiante?->email,
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


    public function ver_justificante(int $id)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $justificante = JustificanteEstudiante::query()
            ->with(['estudiante', 'periodo', 'detalles.asistencia', 'revisor'])
            ->findOrFail($id);

        $this->validarAccesoPersonal(
            $personalId,
            $justificante->estudiante_id,
            $esAdmin
        );

        $nombre = $justificante->estudiante?->nombre_completo
            ?: trim(($justificante->estudiante?->nombre ?? '') . ' ' . ($justificante->estudiante?->apellidos ?? ''));

        return response()->json([
            'data' => [
                'id' => $justificante->id,
                'folio' => $justificante->folio,
                'estado_justificante' => $justificante->estado,
                'motivo' => $justificante->motivo,
                'descripcion' => $justificante->descripcion,
                'comentario_revision' => $justificante->comentario_revision,
                'revisado_en' => optional($justificante->revisado_en)->format('Y-m-d H:i:s'),
                'archivo_nombre' => $justificante->archivo_nombre,

                'archivo_url' => $justificante->archivo_ruta
                        ? route('grup_personal.grup_justificantes.name_ver_archivo_justificante', $justificante->id)
                        : null,

                'archivo_descarga_url' => $justificante->archivo_ruta
                        ? route('grup_personal.grup_justificantes.name_descargar_archivo_justificante', $justificante->id)
                        : null,

                'periodo' => $justificante->periodo?->nombre,
                'estudiante' => [
                    'id' => $justificante->estudiante?->id,
                    'numero_control' => $justificante->estudiante?->numero_control,
                    'nombre_completo' => $nombre ?: '—',
                    'email' => $justificante->estudiante?->email,
                ],
                'fechas' => $justificante->detalles->map(function ($d) {
                    return [
                        'fecha' => optional($d->fecha)->format('Y-m-d'),
                        'estatus_asistencia' => $this->resolverEstatusAsistenciaTexto($d->asistencia),
                        'asistencia_diaria_id' => $d->asistencia_diaria_id,
                    ];
                })->values(),
            ]
        ]);
    }


    public function ver_archivo_justificante(int $id)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $justificante = JustificanteEstudiante::query()
            ->with(['estudiante', 'periodo', 'detalles.asistencia', 'revisor'])
            ->findOrFail($id);

        $this->validarAccesoPersonal(
            $personalId,
            $justificante->estudiante_id,
            $esAdmin
        );

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
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $justificante = JustificanteEstudiante::query()
            ->with(['estudiante', 'periodo', 'detalles.asistencia', 'revisor'])
            ->findOrFail($id);

        $this->validarAccesoPersonal(
            $personalId,
            $justificante->estudiante_id,
            $esAdmin
        );

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


    public function aprobar_justificante(Request $request, int $id)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $data = $request->validate([
            'comentario_revision' => ['nullable', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($id, $personalId, $esAdmin, $data) {
            $justificante = JustificanteEstudiante::query()
                ->with('detalles')
                ->lockForUpdate()
                ->findOrFail($id);

            $this->validarAccesoPersonal(
                $personalId,
                $justificante->estudiante_id,
                $esAdmin
            );

            if ($justificante->estado !== 'PENDIENTE') {
                return response()->json([
                    'message' => 'Este justificante ya fue revisado.'
                ], 422);
            }

            $justificante->update([
                'estado' => 'APROBADO',
                'revisado_por' => $personalId,
                'revisado_en' => now(),
                'comentario_revision' => $data['comentario_revision'] ?? null,
            ]);

            $idsAsistencia = $justificante->detalles
                ->pluck('asistencia_diaria_id')
                ->filter()
                ->values();

            AsistenciaDiaria::query()
                ->whereIn('id', $idsAsistencia)
                ->where('estudiante_id', $justificante->estudiante_id)
                ->where('periodo_id', $justificante->periodo_id)
                ->where('estatus', 'FALTA')
                ->where('justificada', false)
                ->update([
                    'justificada' => true,
                    'justificante_id' => $justificante->id,
                    'updated_at' => now(),
                ]);

            return response()->json([
                'message' => 'Justificante aprobado correctamente.'
            ]);
        });
    }


    public function rechazar_justificante(Request $request, int $id)
    {
        $personal = auth('personal')->user();
        $personalId = $personal->id;
        $esAdmin = $personal->esAdmin();

        $data = $request->validate([
            'comentario_revision' => ['required', 'string', 'max:2000'],
        ]);

        return DB::transaction(function () use ($id, $personalId, $esAdmin, $data) {
            $justificante = JustificanteEstudiante::query()
                ->lockForUpdate()
                ->findOrFail($id);

            $this->validarAccesoPersonal(
                $personalId,
                $justificante->estudiante_id,
                $esAdmin
            );

            if ($justificante->estado !== 'PENDIENTE') {
                return response()->json([
                    'message' => 'Este justificante ya fue revisado.'
                ], 422);
            }

            $justificante->update([
                'estado' => 'RECHAZADO',
                'revisado_por' => $personalId,
                'revisado_en' => now(),
                'comentario_revision' => $data['comentario_revision'],
            ]);

            return response()->json([
                'message' => 'Justificante rechazado correctamente.'
            ]);
        });
    }

}