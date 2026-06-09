<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionDatosEscolares extends Model
{
    protected $table = 'importaciones_datos_escolares';

    protected $fillable = [
        'fuente_datos_escolares_id',
        'archivo_nombre',
        'archivo_ruta',
        'archivo_hash',
        'tipo_importacion',
        'parser_clave',
        'hojas_detectadas',
        'importado_por',
        'importado_en',
        'estado',
        'advertencias',
        'resultados_importacion',
        'error_detalle',
        'notas',
    ];

    protected $casts = [
        'hojas_detectadas' => 'array',
        'importado_en' => 'datetime',
        'advertencias' => 'array',
        'resultados_importacion' => 'array',
    ];


    // ========== PARA LA IMPORTACION DE DATOS ESCOLARES ==========
    public function importacionesConFuentesDatosEscolares()
    {
        return $this->belongsTo(FuenteDatosEscolares::class, 'fuente_datos_escolares_id');
    }

    public function importacionesDelPersonal()
    {
        return $this->belongsTo(PersonalSaae::class, 'importado_por');
    }
    // ============================================================
}