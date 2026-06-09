<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class PersonalSaae extends Authenticatable implements CanResetPasswordContract
{
    use Notifiable, CanResetPassword;

    protected $table = 'personal_saae';

    protected $fillable = [
        'nombre',
        'apellidos',
        'email',
        'telefono',
        'password',
        'activo',
        'ultimo_acceso_at',
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'ultimo_acceso_at' => 'datetime',
    ];


    // ========== PARA LA LOGICA DE ACCESO DEL PERSONAL ==========
    public function roles()
    {
        return $this->belongsToMany(
                     //belongsToMany en Laravel se utiliza para definir relaciones de muchos a muchos (N:M) entre dos modelos de Eloquent. Permite que un modelo pertenezca a múltiples instancias de otro, y viceversa, facilitando la gestión de datos a través de una tabla intermedia o "pivote"
            RolesPersonalSaae::class, 
            'personal_con_rol_saae', 
            'personal_id', 
            'role_id'
        )->withTimestamps();
    }

    // Verifica si el personal tiene asignado un rol específico por su clave
    // Devuelve true si existe ese rol en su relación de roles; en caso contrario, false
    public function hasRole(string $clave): bool
    {
        return $this->roles()->where('clave', $clave)->exists();
    }


    // Verifica si el personal tiene al menos uno de varios roles, usando un arreglo de claves
    // Devuelve true si encuentra cualquiera de esos roles en su relación de roles; si no, false
    public function hasAnyRole(array $claves): bool
    {
        return $this->roles()->whereIn('clave', $claves)->exists();
    }


    // Verifica si el personal tiene un permiso específico a traves de sus roles
    // Si el personal tiene el rol admin, devuelve true automaticamente
    // En otro caso, revisa si alguno de sus roles contiene ese permiso por clave
    public function hasPermission(string $permiso): bool
    {
        //El administrador ve y hace todo
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->roles()->whereHas('permisos', function ($q) use ($permiso) {
            $q->where('clave', $permiso);
        })->exists();
    }


    // Verifica si el personal tiene al menos uno de varios permisos a traves de sus roles
    // Si el personal tiene el rol admin, devuelve true automaticamente
    // En otro caso, revisa si alguno de sus roles contiene cualquiera de esos permisos por clave
    public function hasAnyPermission(array $permisos): bool
    {
        if ($this->hasRole('admin')) {
            return true;
        }

        return $this->roles()->whereHas('permisos', function ($q) use ($permisos) {
            $q->whereIn('clave', $permisos);
        })->exists();
    }


    // Verifica si el personal tiene específicamente el rol admin
    // Es un atajo semantico de hasRole('admin') para hacer el codigo más claro
    public function esAdmin(): bool
    {
        return $this->hasRole('admin');
    }


    // Scope local para excluir de la consulta a los personales que tengan el rol admin
    // Permite reutilizar el filtro en consultas como: PersonalSaae::sinAdmin()->get();
    public function scopeSinAdmin($query)
    {
        return $query->whereDoesntHave('roles', function ($q) {
            $q->where('clave', 'admin');
        });
    }
    // ============================================================


    // ======= PARA EL HISTORIAL DE RECUPERACION DE CONTRASEÑA =======
    // Devuelve todos los intentos de recuperacion de contraseña asociados a este personal
    public function intentosRecuperacionContrasena()
    {
        return $this->hasMany(IntentoRecuperacionContrasenaPersonal::class, 'personal_id');
    }
    // ===============================================================


    // === PARA EL REGISTRO DE QUE PERSONAL GENERO EL CORREO PARA LA ACTIVACION DE LA CUENTA DEL ESTUDIANTE ===
    public function generoActivacionCuentaEstudiantes()
    {
        return $this->hasMany(ActivacionCuentaEstudianteSaae::class, 'generado_por');
    }
    // ============================================================


    // ======= PARA LA ASIGNACION DE ESTUDIANTE CON PERSONAL =======
    public function personalConAsignacion()
    {
        return $this->hasMany(EstudianteConPersonalSaae::class, 'personal_id');
    }
    // ============================================================


    // ================== PARA ALERTAS ATENDIDAS ==================
    public function alertasAsistenciaAtendidas()
    {
        return $this->hasMany(AlertaAsistenciaEstudiante::class, 'atendida_por');
    }
    // ============================================================
}