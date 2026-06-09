<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PeriodoFecha extends Model
{
    protected $table = 'periodo_fechas';

    protected $fillable = [
        'periodo_id',
        'fecha',
        'es_clase',
        'tipo_dia',
        'origen',
        'observaciones'
    ];

    protected $casts = [
        'fecha' => 'date',
        'es_clase' => 'boolean'
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function periodo()
    {
        return $this->belongsTo(Periodo::class, 'periodo_id'); //cada registro de la tabla 'periodo_fechas' pertenece a un solo periodo
    }
    // ============================================================
}
