<?php

namespace App\Services\Alertas;

use App\Models\AlertaAsistenciaEstudiante;
use App\Models\AsistenciaDiaria;
use App\Services\Alertas\NotificacionAlertasAsistenciaService;
use Illuminate\Support\Facades\DB;

// IMPORTANTE LEER
// - Cuando se realicen cambios con realacion a Jobs/Services/Mails con relacion al metodo "EnviarCorreoAlertaAsistenciaJob" es necesario ejecutar los siguientes comandos:
// - php artisan optimize:clear
// - composer dump-autoload
// - php artisan queue:restart
// - php artisan queue:work --queue=correos_estudiantes,default -vvv

class GenerarAlertasCriticasAsistenciaService
{
    
    // Procesa todos los estudiantes con faltas del periodo
    public function procesarPeriodo(int $periodoId, ?string $tipoImportacion = null): array
    {   

        //tipo de importacion no generar alertas
        if ($tipoImportacion === 'SOLO_TURNOS') {
            return [
                'estudiantes_revisados' => 0,
                'alertas_normales_creadas' => 0,
                'alertas_especiales_creadas' => 0,
                'alertas_creadas_ids' => [],
                'jobs_correos_alertas_despachados' => 0,
            ];
        }

        $estudiantesIds = AsistenciaDiaria::query()
            ->where('periodo_id', $periodoId)
            ->where('esperado', true)
            ->where('estatus', 'FALTA')
            ->where('justificada', false) // NUEVO: no contar faltas justificadas
            ->distinct()
            ->pluck('estudiante_id');

        $resultado = [
            'estudiantes_revisados' => 0,
            'alertas_normales_creadas' => 0,
            'alertas_especiales_creadas' => 0,
            'alertas_creadas_ids' => [],
            'jobs_correos_alertas_despachados' => 0,
        ];

        foreach ($estudiantesIds as $estudianteId) {
            $parcial = $this->procesarEstudiantePeriodo(
                estudianteId: (int) $estudianteId,
                periodoId: $periodoId
            );

            $resultado['estudiantes_revisados']++;
            $resultado['alertas_normales_creadas'] += $parcial['alertas_normales_creadas'];
            $resultado['alertas_especiales_creadas'] += $parcial['alertas_especiales_creadas'];
            $resultado['alertas_creadas_ids'] = array_merge(
                $resultado['alertas_creadas_ids'],
                $parcial['alertas_creadas_ids'] ?? []
            );
        }

        // =========================
        // ENVIAR CORREOS FUERA DE TRANSACCION
        // =========================
        if (!empty($resultado['alertas_creadas_ids'])) {
            $notificacionResultado = app(NotificacionAlertasAsistenciaService::class)
                ->notificarAlertasCreadas($resultado['alertas_creadas_ids']);

            $resultado['jobs_correos_alertas_despachados'] =
                $notificacionResultado['jobs_correos_alertas_despachados'] ?? 0;
        }

        return $resultado;
    }


    
    // Procesa las faltas de un estudiante
    public function procesarEstudiantePeriodo(int $estudianteId, int $periodoId): array
    {
        $faltas = AsistenciaDiaria::query()
            ->where('estudiante_id', $estudianteId)
            ->where('periodo_id', $periodoId)
            ->where('esperado', true)
            ->where('estatus', 'FALTA')
            ->where('justificada', false) //no contar faltas justificadas
            ->orderBy('fecha')
            ->orderBy('id')
            ->get([
                'id',
                'fecha',
                'estudiante_id',
                'periodo_id',
            ]);

        $resultado = [
            'alertas_normales_creadas' => 0,
            'alertas_especiales_creadas' => 0,
            'alertas_creadas_ids' => [],
        ];

        DB::transaction(function () use ($faltas, $estudianteId, &$resultado) {
            $contador = 0;

            foreach ($faltas as $falta) {
                $contador++;

                // FALTAS ACUMULADAS
                if ($contador !== 3) {

                    $alertaNormal = AlertaAsistenciaEstudiante::firstOrCreate(
                        [
                            'estudiante_id' => $estudianteId,
                            'periodo_id' => $falta->periodo_id,
                            'tipo_alerta' => 'FALTA_ACUMULADA',
                            'regla_codigo' => 'FALTA_' . $contador,
                        ],
                        [
                            'asistencia_diaria_id' => $falta->id,
                            'valor_detectado' => $contador,
                            'umbral_configurado' => null,
                            'fecha_referencia' => $falta->fecha,
                            'fecha_disparo' => now(),
                            'estado' => 'PENDIENTE',
                        ]
                    );

                    if ($alertaNormal->wasRecentlyCreated) {
                        $resultado['alertas_normales_creadas']++;
                        $resultado['alertas_creadas_ids'][] = $alertaNormal->id;
                    }
                }

                // ALERTA CRITICA - 3 FALTAS ACUMULADAS
                if ($contador === 3) {

                    $alertaEspecial = AlertaAsistenciaEstudiante::firstOrCreate(
                        [
                            'estudiante_id' => $estudianteId,
                            'periodo_id' => $falta->periodo_id,
                            'tipo_alerta' => 'SUSPENSION_BECA_ESCOLAR',
                            'regla_codigo' => 'UMBRAL_3_FALTAS',
                        ],
                        [
                            'asistencia_diaria_id' => $falta->id,
                            'valor_detectado' => 3,
                            'umbral_configurado' => 3,
                            'fecha_referencia' => $falta->fecha,
                            'fecha_disparo' => now(),
                            'estado' => 'PENDIENTE',
                            'observaciones' => 'El estudiante alcanzó 3 faltas acumuladas en su historial de asistencia.',
                        ]
                    );

                    if ($alertaEspecial->wasRecentlyCreated) {
                        $resultado['alertas_especiales_creadas']++;
                        $resultado['alertas_creadas_ids'][] = $alertaEspecial->id;
                    }
                }

            }
        });

        return $resultado;
    }


}