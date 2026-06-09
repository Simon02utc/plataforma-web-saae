<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParserFuenteDatosEscolares extends Model
{
    protected $table = 'parsers_fuentes_datos_escolares';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


    // ========== PARA LA IMPORTACION DE DATOS ESCOLARES ==========
    public function parsersConFuentesDatosEscolares() 
    {
        return $this->hasMany(FuenteDatosEscolares::class, 'parser_fuente_dato_escolar_id');
    }
    // ============================================================s
}