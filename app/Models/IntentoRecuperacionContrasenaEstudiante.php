<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntentoRecuperacionContrasenaEstudiante extends Model
{
    protected $table = 'intentos_recuperacion_contrasena_estudiante';

    protected $fillable = [
        'estudiante_id',
        'email_solicitado',
        'ip_address',
        'user_agent',
        'accion',
        'resultado',
        'motivo',
    ];

    // ======= PARA EL HISTORIAL DE RECUPERACION DE CONTRASEÑA =======
    public function estudiante()
    {
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id');
    }
    // ============================================================

}
