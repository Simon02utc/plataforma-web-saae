<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ParserRelojChecador;

class ParsersRelojesChecadoresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parsers = [
            [
                'clave' => 'parser_reloj_checador_on_the_minute_archivo_xlsx',
                'nombre' => 'Parser del Reloj On The Minute - Archivo XLSX',
                'descripcion' => 'Parser para el reloj On The Minute que exportan archivo Excel (.xlsx) con hojas de turnos y asistencia.',
                'activo' => true,
            ],
        ];

        foreach ($parsers as $parser) {
            ParserRelojChecador::updateOrCreate(
                [
                    'clave' => $parser['clave'],
                ],
                [
                    'nombre' => $parser['nombre'],
                    'descripcion' => $parser['descripcion'],
                    'activo' => $parser['activo'],
                ]
            );
        }

    }

}
