<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstudianteConDatosEscolares extends Model
{
    protected $table = 'estudiantes_con_datos_escolares';

    protected $fillable = [
        'estudiante_id',
        'mes_ingreso',
        'anio_ingreso',
        'periodo_ingreso_texto',
        'especialidad_id',
        'estatus_escolar_id',
        'ultima_importacion_id',
    ];

    
    // ========== PARA LA IMPORTACION DE DATOS ESCOLARES ==========
    public function datosEscolaresDelEstudiante()
    {
        return $this->belongsTo(EstudiantesSaae::class, 'estudiante_id');
    }

    public function datoEscolarDeAreaEspecialidad()
    {
        return $this->belongsTo(AreasEspecialidadEstudiantesSaae::class, 'especialidad_id');
    }

    public function datoEscolarDeEstatus()
    {
        return $this->belongsTo(EstatusEscolaresEstudiantesSaae::class, 'estatus_escolar_id');
    }

    public function datoEscolarDeUltimaImportacion()
    {
        return $this->belongsTo(ImportacionDatosEscolares::class, 'ultima_importacion_id');
    }
    // ============================================================

}