<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\RelojChecador;
use App\Models\ParserRelojChecador;
use Illuminate\Database\QueryException;

class RelojesChecadoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parser = ParserRelojChecador::where('clave', 'parser_reloj_checador_on_the_minute_archivo_xlsx')->first();

        if (!$parser) {
            throw new \RuntimeException(
                "No se encontró el parser 'parser_reloj_checador_on_the_minute_archivo_xlsx' para el reloj inicial. Ejecuta primero el seeder de parsers."
            );
        }

        $relojesChecadores = [
            [
                'nombre' => 'Reloj principal On the minute',
                'parser_reloj_checador_id' => $parser->id,
                'ubicacion' => 'Edificio de administracion',
                'activo' => true,
            ],
        ];

        foreach ($relojesChecadores as $relojChecador) {
            RelojChecador::updateOrCreate(
                [
                    'nombre' => $relojChecador['nombre'],
                ],
                [
                    'parser_reloj_checador_id' => $relojChecador['parser_reloj_checador_id'],
                    'ubicacion' => $relojChecador['ubicacion'],
                    'activo' => $relojChecador['activo'],
                ]
            );
        }

    }
}