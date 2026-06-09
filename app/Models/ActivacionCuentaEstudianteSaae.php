<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivacionCuentaEstudianteSaae extends Model
{
    protected $table = 'activaciones_cuenta_estudiantes_saae';

    protected $fillable = [
        'estudiante_id',
        'email_destino',
        'token_hash',
        'expira_en',
        'enviado_en',
        'usado_en',
        'estado',
        'generado_por',
        'error_detalle',
    ];

    protected $casts = [
        'expira_en' => 'datetime',
        'enviado_en' => 'datetime',
        'usado_en' => 'datetime',
    ];

    // ====== PARA LA ACTIVACION DE LA CUENTA DEL ESTUDIANTE ======
    public function estudiante()
    {
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id');
    }
    // ============================================================


    // === PARA EL REGISTRO DE QUE PERSONAL GENERO EL CORREO PARA LA ACTIVACION DE CUENTA ===
    public function personalQueGenero()
    {
        return $this->belongsTo(PersonalSaae::class, 'generado_por');
    }
    // ============================================================
}
