<?php

namespace App\Jobs;

use App\Mail\AlertaAsistenciaEstudianteMail;
use App\Mail\AlertaAsistenciaPersonalMail;
use App\Models\AlertaAsistenciaEstudiante;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Mail;

class EnviarCorreoAlertaAsistenciaJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $timeout = 180;
    public int $uniqueFor = 600;

    public function __construct(
        public int $alertaId
    ) {}

    public function uniqueId(): string
    {
        return 'enviar_correo_alerta_asistencia_' . $this->alertaId;
    }

    public function handle(): void
    {
        $alerta = AlertaAsistenciaEstudiante::query()
            ->with([
                'estudiante.estudianteConAsignacionPersonal.asignacionConPersonal',
                'estudiante.estudianteConAsignacionPersonal.asignacionConRol',
                'periodo',
                'asistenciaDiaria',
            ])
            ->find($this->alertaId);

        if (!$alerta) {
            return;
        }


        //por si falla el envio de las alertas por correo
        try {
            $estudiante = $alerta->estudiante;

            if (!$estudiante) {
                $alerta->update([
                    'correo_estado' => 'OMITIDO',
                    'correo_enviado_at' => null,
                    'correo_fallo_at' => null,
                    'correo_error' => 'La alerta no tiene estudiante relacionado.',
                ]);

                return;
            }

            $seEnvioAlMenosUno = false;

            if (
                (bool) $estudiante->activo === true &&
                !empty($estudiante->email)
            ) {
                Mail::to($estudiante->email)->send(
                    new AlertaAsistenciaEstudianteMail($alerta)
                );

                $seEnvioAlMenosUno = true;
            }

            $asignaciones = $estudiante->estudianteConAsignacionPersonal ?? collect();

            foreach ($asignaciones as $asignacion) {
                if (!(bool) $asignacion->activo) {
                    continue;
                }

                $personal = $asignacion->asignacionConPersonal;

                if (
                    !$personal ||
                    (bool) $personal->activo !== true ||
                    empty($personal->email)
                ) {
                    continue;
                }

                Mail::to($personal->email)->send(
                    new AlertaAsistenciaPersonalMail(
                        alerta: $alerta,
                        personal: $personal,
                        asignacion: $asignacion
                    )
                );

                $seEnvioAlMenosUno = true;
            }

            if ($seEnvioAlMenosUno) {
                $alerta->update([
                    'correo_estado' => 'ENVIADO',
                    'correo_enviado_at' => now(),
                    'correo_fallo_at' => null,
                    'correo_error' => null,
                ]);
            } else {
                $alerta->update([
                    'correo_estado' => 'OMITIDO',
                    'correo_enviado_at' => null,
                    'correo_fallo_at' => null,
                    'correo_error' => 'No se encontraron destinatarios válidos con cuenta activa y correo electrónico.',
                ]);
            }

        } catch (\Throwable $e) {
            $alerta->update([
                'correo_estado' => 'FALLIDO',
                'correo_fallo_at' => now(),
                'correo_error' => mb_substr($e->getMessage(), 0, 1000),
            ]);

            throw $e;
        }
    }
}