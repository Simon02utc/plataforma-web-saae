<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstatusEscolaresEstudiantesSaae extends Model
{
    protected $table = 'estatus_escolares_estudiantes_saae';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


    // ========== PARA LA IMPORTACION DE DATOS ESCOLARES ==========
    public function estatusEscolarConEstudiantes() 
    {
        return $this->hasMany(EstudianteConDatosEscolares::class, 'estatus_escolar_id');
    }
    // ============================================================
}