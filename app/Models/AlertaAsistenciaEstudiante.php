<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AlertaAsistenciaEstudiante extends Model
{
    protected $table = 'alertas_asistencia_estudiantes';

    protected $fillable = [
        'estudiante_id',
        'periodo_id',
        'asistencia_diaria_id',
        'tipo_alerta',
        'regla_codigo',
        'valor_detectado',
        'umbral_configurado',
        'fecha_referencia',
        'fecha_disparo',
        'estado',
        //si envio o no (ocurrio un error en el envio) el correo de alerta de asistencia
        'correo_estado', //registro del envio de correo si hay falta para el estudiante.
        'correo_enviado_at', //registro del envio de correo si hay falta para el estudiante.
        'correo_fallo_at', //registro del envio de correo si hay falta para el estudiante.
        'correo_error', //registro del envio de correo si hay falta para el estudiante.
        'atendida_por',
        'atendida_en',
        'observaciones',
    ];

    protected $casts = [
        'fecha_referencia' => 'date',
        'fecha_disparo' => 'datetime',
        'correo_enviado_at' => 'datetime',
        'correo_fallo_at' => 'datetime',
        'atendida_en' => 'datetime',
        'valor_detectado' => 'integer',
        'umbral_configurado' => 'integer',
    ];



    //================= RELACIONES =================

    public function estudiante()
    {
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function asistenciaDiaria()
    {
        return $this->belongsTo(AsistenciaDiaria::class, 'asistencia_diaria_id');
    }

    public function atendidaPor()
    {
        return $this->belongsTo(PersonalSaae::class, 'atendida_por');
    }
    // ===============================================================


    // ================= HELPERS =================
    public function getTipoAlertaTextoAttribute(): string
    {
        return match ($this->tipo_alerta) {
            'FALTA_ACUMULADA' => 'Falta acumulada',
            'SUSPENSION_BECA_ESCOLAR' => 'Suspensión de beca escolar',
            default => $this->tipo_alerta,
        };
    }

    public function getEstadoTextoAttribute(): string
    {
        return match ($this->estado) {
            'PENDIENTE' => 'Pendiente',
            'ATENDIDA' => 'Atendida',
            'CERRADA' => 'Cerrada',
            default => $this->estado,
        };
    }
    // ===============================================================
}
