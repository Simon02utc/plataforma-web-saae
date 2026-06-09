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

class ReenviarActivacionCuentaEstudianteJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3; //cuantas veces reintenta el Job si falla
    public int $timeout = 120; //cuantos segundos puede durar ejecutándose el Job
    public int $uniqueFor = 600; //cuanto tiempo Laravel considera ese Job como unico para no duplicarlo

    public function __construct(
        public int $estudianteId,
        public ?int $personalId = null
    ) {}

    public function uniqueId(): string
    {
        return 'reenviar_activacion_estudiante_' . $this->estudianteId;
    }

    public function handle(
        GenerarCorreoInstitucionalEstudiantesService $correoService,
        NotificacionesEstudiantesService $notificaciones
    ): void {
        $resultado = DB::transaction(function () use ($correoService) {
            $estudiante = EstudiantesSaae::query()
                ->lockForUpdate()
                ->find($this->estudianteId);

            if (!$estudiante) {
                return null;
            }

            if (!$correoService->puedeReenviarActivacion($estudiante)) {
                return null;
            }

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
            throw new \RuntimeException('No se pudo reenviar el correo de activación del estudiante.');
        }
    }
}