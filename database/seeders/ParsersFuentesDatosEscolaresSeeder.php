<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\ParserFuenteDatosEscolares;

class ParsersFuentesDatosEscolaresSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $parsers = [
            [
                'clave' => 'parser_streaming_fuente_concentrado_estudiantes_xlsx',
                'nombre' => 'Parser Streaming de Concentrado de estudiantes en archivo Excel',
                'descripcion' => 'Este parser es para archivos actuales que estan mal (enormes o inflados), se detiene cuando hay muchas filas vacias. Mejor para producción si el archivo fuente puede venir mal optimizado ',
                'activo' => true,
            ],
            [
                'clave' => 'parser_phpspreadsheet_fuente_concentrado_estudiantes_xlsx',
                'nombre' => 'Parser con PhpSpreadsheet de Concentrado de estudiantes en archivo Excel',
                'descripcion' => 'Este parser es para archivo que son creados correctamente (normales y limpios).',
                'activo' => true,
            ],
        ];

        foreach ($parsers as $parser) {
            ParserFuenteDatosEscolares::updateOrCreate(
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