<?php

namespace App\Jobs;

use App\Models\EstudiantesSaae;
use App\Services\Estudiantes\GenerarCorreoInstitucionalEstudiantesService;
use App\Services\Estudiantes\NotificacionesEstudiantesService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerarCorreoInstitucionalYEnviarActivacionEstudianteJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3; //cuantas veces reintenta el Job si falla
    public int $timeout = 180; //cuantos segundos puede durar ejecutándose el Job
    public int $uniqueFor = 600; //cuanto tiempo Laravel considera ese Job como unico para no duplicarlo

    public function __construct(
        public int $estudianteId,
        public ?int $personalId = null
    ) {}

    public function uniqueId(): string
    {
        return 'generar_correo_institucional_estudiante_' . $this->estudianteId;
    }

    public function handle(
        GenerarCorreoInstitucionalEstudiantesService $correoService,
        NotificacionesEstudiantesService $notificaciones
    ): void
    {
        $resultado = DB::transaction(function () use ($correoService) {
            $estudiante = EstudiantesSaae::query()
                ->lockForUpdate() //para evitar carreras, si dos procesos intentaran generar correo al mismo estudiante casi al mismo tiempo, uno queda bloqueado mientras el otro termina
                ->find($this->estudianteId);

            if (!$estudiante) {
                return null;
            }

            if (!$correoService->puedeGenerarse($estudiante)) {
                return null;
            }

            $correoInstitucional = $correoService->construirCorreoInstitucional($estudiante->numero_control);

            $correoOcupado = EstudiantesSaae::query()
                ->where('email', $correoInstitucional)
                ->where('id', '!=', $estudiante->id)
                ->exists();

            if ($correoOcupado) {
                \Log::warning('No se pudo generar correo institucional: correo ya ocupado.', [
                    'estudiante_id' => $estudiante->id,
                    'numero_control' => $estudiante->numero_control,
                    'correo_intentado' => $correoInstitucional,
                ]);

                return null;
            }

            $estudiante->update([
                'email' => $correoInstitucional,
                'activo' => false,
                'correo_institucional_generado_en' => now(),
            ]);

            $tokenData = $correoService->crearActivacionPendiente(
                $estudiante->fresh(),
                $this->personalId
            );

            return [
                'estudiante' => $estudiante->fresh(),
                'activacion' => $tokenData['activacion'],
                'token_plano' => $tokenData['token_plano'],
            ];
        });

        if (!$resultado) {
            return;
        }

        $resultadoEnvio = $notificaciones->enviarCorreoActivacionCuentaEstudiante(
            $resultado['estudiante'],
            $resultado['activacion'],
            $resultado['token_plano']
        );

        if (!$resultadoEnvio) {
            throw new \RuntimeException('No se pudo enviar el correo de activación del estudiante.');
        }
    }
}