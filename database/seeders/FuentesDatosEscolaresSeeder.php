<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use App\Models\FuenteDatosEscolares;
use App\Models\ParserFuenteDatosEscolares;
use Illuminate\Database\Seeder;

class FuentesDatosEscolaresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parser = ParserFuenteDatosEscolares::where('clave', 'parser_phpspreadsheet_fuente_concentrado_estudiantes_xlsx')->first();

        if (!$parser) {
            throw new \RuntimeException(
                "No se encontró el parser 'parser_phpspreadsheet_fuente_concentrado_estudiantes_xlsx' para la fuente de datos escolares inicial. Ejecuta primero el seeder de parsers."
            );
        }

        $fuentesDatosEscolares = [
            [
                'nombre' => 'Concentrado de Estudiantes',
                'descripcion' => 'Fuente principal para la importación de datos escolares desde Excel. ',
                'activo' => true,
                'parser_fuente_dato_escolar_id' => $parser->id,
            ],
        ];

        foreach ($fuentesDatosEscolares as $fuenteDatoEscolar) {
            FuenteDatosEscolares::updateOrCreate(
                [
                    'nombre' => $fuenteDatoEscolar['nombre'],
                ],
                [
                    'descripcion' => $fuenteDatoEscolar['descripcion'],
                    'activo' => $fuenteDatoEscolar['activo'],
                    'parser_fuente_dato_escolar_id' => $fuenteDatoEscolar['parser_fuente_dato_escolar_id'],
                ]
            );
        }

    }
}