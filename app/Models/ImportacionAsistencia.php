<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ImportacionAsistencia extends Model
{
    protected $table = 'importaciones_asistencia';

    protected $fillable = [
        'reloj_checador_id',
        'periodo_id',
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


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function reloj() 
    {
        return $this->belongsTo(RelojChecador::class, 'reloj_checador_id');
    }
    
    public function periodo() 
    {
        return $this->belongsTo(Periodo::class, 'periodo_id');
    }

    public function marcaciones() 
    {
        return $this->hasMany(MarcacionAsistencia::class, 'importacion_id');
    }

    public function importador()
    {
        return $this->belongsTo(PersonalSaae::class, 'importado_por'); //para que importado_por apunte a personal_saae
    }
    // ============================================================
}