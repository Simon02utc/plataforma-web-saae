<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoEstudiante extends Model
{
    protected $table = 'periodo_estudiantes';

    protected $fillable = [
        'periodo_id',
        'estudiante_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id'); //cada registro de la tabla 'periodo_estudiantes' pertenece a un solo periodo
    }

    public function estudiante()
    {
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id'); //cada registro de la tabla 'periodo_estudiantes' pertenece a un solo estudiante
    }
    // ============================================================
}
