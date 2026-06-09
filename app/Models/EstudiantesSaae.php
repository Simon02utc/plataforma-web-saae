<?php

namespace App\Models;

use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;


class EstudiantesSaae extends Authenticatable implements CanResetPasswordContract
{
    use Notifiable, CanResetPassword;


    protected $table = 'estudiantes_saae';

    protected $fillable = [
        'numero_control',
        'nombre_completo',
        'nombre',
        'apellidos',
        'email',
        'correo_institucional_generado_en',
        'telefono',
        'password',
        'activo',
        'cuenta_activada_en',
        'ultimo_acceso_at'
    ];

    protected $hidden = [
        'password',
        'remember_token'
    ];

    protected $casts = [
        'correo_institucional_generado_en' => 'datetime',
        'activo' => 'boolean',
        'cuenta_activada_en' => 'datetime',
        'ultimo_acceso_at' => 'datetime',
    ];
    

    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function inscripcionesReloj() {
        return $this->hasMany(RelojInscripcion::class, 'estudiante_id');
    }

    public function marcaciones() {
        return $this->hasMany(MarcacionAsistencia::class, 'estudiante_id');
    }

    public function asistenciasDiarias() {
        return $this->hasMany(AsistenciaDiaria::class, 'estudiante_id');
    }

    public function periodoEstudiantes()
    {
        return $this->hasMany(PeriodoEstudiante::class, 'estudiante_id');
    }

    public function periodos()
    {
        return $this->belongsToMany(
            Periodo::class,
            'periodo_estudiantes',
            'estudiante_id',
            'periodo_id'
        )->withPivot('activo')->withTimestamps();
    }
    // ============================================================


    // ========== PARA LA IMPORTACION DE DATOS ESCOLARES ==========
    public function estudiantesConDatosEscolares()
    {
        return $this->hasOne(EstudianteConDatosEscolares::class, 'estudiante_id');
    }
    // ============================================================


    // ======= PARA LA ASIGNACION DE ESTUDIANTE CON PERSONAL =======
    public function estudianteConAsignacionPersonal()
    {
        return $this->hasMany(EstudianteConPersonalSaae::class, 'estudiante_id');
    }
    // ============================================================


    // ======= PARA LA ACTIVACION DE SU CUENTA DEL ESTUDIANTE =======
    public function activacionesCuenta()
    {
        return $this->hasMany(ActivacionCuentaEstudianteSaae::class, 'estudiante_id');
    }
    // ============================================================


    // ======= PARA EL HISTORIAL DE RECUPERACION DE CONTRASEÑA =======
    // Devuelve todos los intentos de recuperacion de contraseña asociados a este estudiante
    public function intentosRecuperacionContrasena()
    {
        return $this->hasMany(IntentoRecuperacionContrasenaEstudiante::class, 'estudiante_id');
    }
    // ===============================================================


    // ================== PARA ALERTAS DE ASISTENCIA ==================
    public function alertasAsistencia()
    {
        return $this->hasMany(AlertaAsistenciaEstudiante::class, 'estudiante_id');
    }
    // ===============================================================


    // ================== PARA LA SUBIDA DE JUSTIFICANTES ==================
    public function justificantes()
    {
        return $this->hasMany(JustificanteEstudiante::class, 'estudiante_id');
    }
    // ===============================================================
}