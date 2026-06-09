<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PermisosSaae extends Model
{
    protected $table = 'permisos_saae';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function roles()
    {
        return $this->belongsToMany(
            RolesPersonalSaae::class,
            'rol_con_permiso_saae',
            'permiso_id',
            'role_id'
        )->withTimestamps();
    }
    // ============================================================
}
