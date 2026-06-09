<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Periodo extends Model
{
    protected $table = 'periodos';

    protected $fillable = [
        'nombre',
        'fecha_inicio',
        'fecha_fin',
        'activo'
    ];

    protected $casts = [
        'fecha_inicio' => 'date',
        'fecha_fin' => 'date',
        'activo' => 'boolean'
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function importaciones() 
    {
        return $this->hasMany(ImportacionAsistencia::class, 'periodo_id');
    }

    public function asistenciasDiarias() {
        return $this->hasMany(AsistenciaDiaria::class, 'periodo_id');
    }

    public function fechas()
    {
        return $this->hasMany(PeriodoFecha::class, 'periodo_id');
    }

    public function periodoEstudiantes()
    {
        return $this->hasMany(PeriodoEstudiante::class, 'periodo_id');
    }

    public function estudiantes()
    {
        return $this->belongsToMany(
            EstudiantesSaae::class,
            'periodo_estudiantes',
            'periodo_id',
            'estudiante_id'
        )->withPivot('activo')->withTimestamps();
    }
    // ============================================================


    // ================== PARA ALERTAS DE ASISTENCIA ==================
    public function alertasAsistencia()
    {
        return $this->hasMany(AlertaAsistenciaEstudiante::class, 'periodo_id');
    }
    // ===============================================================


    // ================== PARA LA SUBIDA DE JUSTIFICANTES ==================
    public function justificantesEstudiantes()
    {
        return $this->hasMany(JustificanteEstudiante::class, 'periodo_id');
    }
    // ===============================================================

}
