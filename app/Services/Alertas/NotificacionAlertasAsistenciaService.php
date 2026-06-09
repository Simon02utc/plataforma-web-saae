<?php

namespace App\Services\Alertas;

use App\Jobs\EnviarCorreoAlertaAsistenciaJob;

class NotificacionAlertasAsistenciaService
{
    public function notificarAlertasCreadas(array $alertasIds): array
    {
        $resultado = [
            'alertas_procesadas' => 0,
            'jobs_correos_alertas_despachados' => 0,
        ];

        if (empty($alertasIds)) {
            return $resultado;
        }

        foreach (array_unique($alertasIds) as $alertaId) {
            EnviarCorreoAlertaAsistenciaJob::dispatch((int) $alertaId)
                ->onQueue('correos_estudiantes')
                ->afterCommit();

            $resultado['alertas_procesadas']++;
            $resultado['jobs_correos_alertas_despachados']++;
        }

        return $resultado;
    }
}