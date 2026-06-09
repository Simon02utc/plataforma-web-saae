<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelojInscripcion extends Model
{
    protected $table = 'reloj_inscripciones';

    protected $fillable = [
        'reloj_checador_id',
        'reloj_usuario_id',
        'estudiante_id',
        'activo'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function reloj() {
        return $this->belongsTo(RelojChecador::class, 'reloj_checador_id');
    }
    
    public function estudiante() {
        return $this->belongsTo(Estudiante::class, 'estudiante_id');
    }
    // ============================================================

}
