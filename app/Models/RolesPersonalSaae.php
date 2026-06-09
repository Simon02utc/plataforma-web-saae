<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolesPersonalSaae extends Model
{
    protected $table = 'roles_personal_saae';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion'
    ];


    // ========== PARA LA LOGICA DE ACCESO DEL PERSONAL ==========
    public function personal()
    {
        return $this->belongsToMany( 
                      //belongsToMany en Laravel se utiliza para definir relaciones de muchos a muchos (N:M) entre dos modelos de Eloquent. Permite que un modelo pertenezca a múltiples instancias de otro, y viceversa, facilitando la gestión de datos a través de una tabla intermedia o "pivote" (ej. un Usuario tiene muchos Roles, y un Rol pertenece a muchos Usuarios). 
            PersonalSaae::class, 
            'personal_con_rol_saae', 
            'role_id', 
            'personal_id'
        )->withTimestamps();
    }

    public function permisos()
    {
        return $this->belongsToMany(
                      //belongsToMany en Laravel se utiliza para definir relaciones de muchos a muchos (N:M) entre dos modelos de Eloquent. Permite que un modelo pertenezca a múltiples instancias de otro, y viceversa, facilitando la gestión de datos a través de una tabla intermedia o "pivote"
            PermisosSaae::class,
            'rol_con_permiso_saae',
            'role_id',
            'permiso_id'
        )->withTimestamps();
    }
    // ============================================================


    // ======= PARA LA ASIGNACION DE ESTUDIANTE CON PERSONAL =======
    public function RolConAsignacion()
    {
        return $this->hasMany(EstudianteConPersonalSaae::class, 'role_id');
    }
    // ============================================================
}