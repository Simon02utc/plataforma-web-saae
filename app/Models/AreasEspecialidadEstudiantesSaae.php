<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AreasEspecialidadEstudiantesSaae extends Model
{
    protected $table = 'areas_especialidad_estudiantes_saae';

    protected $fillable = [
        'clave',
        'nombre',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


    // ========== PARA LA IMPORTACION DE DATOS ESCOLARES ==========
    public function especialidadesConEstudiantes() 
    {
        return $this->hasMany(EstudianteConDatosEscolares::class, 'especialidad_id');
    }
    // ============================================================
}