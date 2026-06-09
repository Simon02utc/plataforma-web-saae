<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RelojChecador extends Model
{
    protected $table = 'relojes_checadores';

    protected $fillable = [
        'nombre',
        //'parser_clave',
        'ubicacion',
        'activo',
        'parser_reloj_checador_id'
    ];

    protected $casts = [
        'activo' => 'boolean'
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function parser() 
    {
        return $this->belongsTo(ParserRelojChecador::class, 'parser_reloj_checador_id');
    }    

    public function inscripciones() 
    {
        return $this->hasMany(RelojInscripcion::class, 'reloj_checador_id');
    }

    public function importaciones() 
    {
        return $this->hasMany(ImportacionAsistencia::class, 'reloj_checador_id');
    }

    public function marcaciones()
    {
        return $this->hasMany(MarcacionAsistencia::class, 'reloj_checador_id');
    }

    public function asistenciasDiarias()
    {
        return $this->hasMany(AsistenciaDiaria::class, 'reloj_checador_id');
    }
    // ============================================================
}
