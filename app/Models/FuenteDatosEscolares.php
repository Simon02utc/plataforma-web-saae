<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FuenteDatosEscolares extends Model
{
    protected $table = 'fuentes_datos_escolares';

    protected $fillable = [
        'nombre',
        'descripcion',
        'activo',
        'parser_fuente_dato_escolar_id',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


    // ========== PARA LA IMPORTACION DE DATOS ESCOLARES ==========
    public function fuentesConParsers() 
    {
        return $this->belongsTo(ParserFuenteDatosEscolares::class, 'parser_fuente_dato_escolar_id');
    }

    public function fuentesConImportaciones()
    {
        return $this->hasMany(ImportacionDatosEscolares::class, 'fuente_datos_escolares_id');
    }
    // ============================================================
}