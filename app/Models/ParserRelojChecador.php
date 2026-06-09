<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ParserRelojChecador extends Model
{
    protected $table = 'parsers_relojes_checadores';

    protected $fillable = [
        'clave',
        'nombre',
        'descripcion',
        'activo',
    ];

    protected $casts = [
        'activo' => 'boolean',
    ];


    // ============= PARA LA IMPORTACION DE ASISTENCIA =============
    public function relojesChecadores() {
        return $this->hasMany(RelojChecador::class, 'parser_reloj_checador_id');
    }
    // ============================================================s
}
