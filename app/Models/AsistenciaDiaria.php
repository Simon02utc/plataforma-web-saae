<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsistenciaDiaria extends Model
{
    protected $table = 'asistencia_diaria';

    protected $fillable = [
        'estudiante_id',
        'periodo_id',
        'reloj_checador_id',
        'fecha',
        'esperado',
        'estatus',
        'justificada',
        'justificante_id',
        'fuente',
        'primera_entrada',
        'ultima_salida',
        'conteo_marcaciones',
        'importacion_id'
    ];

    protected $casts = [
        'fecha' => 'date',
        'esperado' => 'boolean',
        'justificada' => 'boolean',
        'primera_entrada' => 'datetime',
        'ultima_salida' => 'datetime',
        'conteo_marcaciones' => 'integer',
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function estudiante() { 
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id'); 
    }

    public function periodo() { 
        return $this->belongsTo(Periodo::class, 'periodo_id'); 
    }

    public function reloj()
    {
        return $this->belongsTo(RelojChecador::class, 'reloj_checador_id');
    }

    public function importacion()
    {
        return $this->belongsTo(ImportacionAsistencia::class, 'importacion_id');
    }
    // ============================================================


    // =========== PARA ALERTAS GENERADAS DESDE UNA FALTA ===========
    public function alertasAsistencia()
    {
        return $this->hasMany(AlertaAsistenciaEstudiante::class, 'asistencia_diaria_id');
    }
    // ============================================================


    // =========== PARA LA SUBIDA DE JUSTIFICANTES ===========
    public function justificante()
    {
        return $this->belongsTo(JustificanteEstudiante::class, 'justificante_id');
    }
    // ============================================================
}
