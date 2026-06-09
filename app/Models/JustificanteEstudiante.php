<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class JustificanteEstudiante extends Model
{
    protected $table = 'justificantes_estudiantes';

    protected $fillable = [
        'estudiante_id',
        'periodo_id',
        'folio',
        'motivo',
        'descripcion',
        'archivo_ruta',
        'archivo_nombre',
        'estado',
        'revisado_por',
        'revisado_en',
        'comentario_revision',
    ];

    protected $casts = [
        'revisado_en' => 'datetime',
    ];

    public function estudiante()
    {
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id');
    }

    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function detalles()
    {
        return $this->hasMany(JustificanteEstudianteDetalle::class, 'justificante_id');
    }

    public function revisor()
    {
        return $this->belongsTo(PersonalSaae::class, 'revisado_por');
    }
}