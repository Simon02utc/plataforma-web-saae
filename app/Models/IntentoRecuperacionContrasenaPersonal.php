<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class IntentoRecuperacionContrasenaPersonal extends Model
{
    protected $table = 'intentos_recuperacion_contrasena_personal';

    protected $fillable = [
        'personal_id',
        'email_solicitado',
        'ip_address',
        'user_agent',
        'accion',
        'resultado',
        'motivo',
    ];

    // ======= PARA EL HISTORIAL DE RECUPERACION DE CONTRASEÑA =======
    public function personal()
    {
        return $this->belongsTo(PersonalSaae::class, 'personal_id');
    }
    // ============================================================

}
