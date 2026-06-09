<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarcacionAsistencia extends Model
{
    protected $table = 'marcaciones_asistencia';

    protected $fillable = [
        'estudiante_id',
        'reloj_checador_id',
        'importacion_id',
        'ocurrio_en',
        'celda_cruda'
    ];

    protected $casts = [
        'ocurrio_en' => 'datetime'
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function estudiante() { 
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id'); 
    }

    public function reloj() { 
        return $this->belongsTo(RelojChecador::class, 'reloj_checador_id'); 
    }

    public function importacion() { 
        return $this->belongsTo(ImportacionAsistencia::class, 'importacion_id'); 
    }
    // ============================================================
}
