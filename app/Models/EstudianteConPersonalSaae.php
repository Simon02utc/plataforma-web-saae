<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteConPersonalSaae extends Model
{
    protected $table = 'estudiante_con_personal_saae';

    protected $fillable = [
        'estudiante_id',
        'personal_id',
        'role_id',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


    // ======= PARA LA ASIGNACION DE ESTUDIANTE CON PERSONAL =======
    public function asignacionConEstudiante() 
    {
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id');
    }

    public function asignacionConPersonal()
    {
        return $this->belongsTo(PersonalSaae::class,'personal_id');
    }

    public function asignacionConRol()
    {
        return $this->belongsTo(RolesPersonalSaae::class,'role_id');
    }
    // ============================================================
}