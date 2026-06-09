<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JustificanteEstudianteDetalle extends Model
{
    protected $table = 'justificantes_estudiantes_detalles';

    protected $fillable = [
        'justificante_id',
        'asistencia_diaria_id',
        'fecha',
        'estatus_original',
    ];

    protected $casts = [
        'fecha' => 'date',
    ];

    public function justificante()
    {
        return $this->belongsTo(JustificanteEstudiante::class, 'justificante_id');
    }

    public function asistencia()
    {
        return $this->belongsTo(AsistenciaDiaria::class, 'asistencia_diaria_id');
    }
}