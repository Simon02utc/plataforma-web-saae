<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\AreasEspecialidadEstudiantesSaae;

class AreasEspecialidadEstudiantesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        
        $areasEspecialidad = [
            [
                'clave' => 'computo_inteligente_y_ciencia_de_datos',
                'nombre' => 'COMPUTO INTELIGENTE Y CIENCIA DE DATOS',
                'activo' => true,
            ],
            [
                'clave' => 'tecnologias_inteligentes_de_software',
                'nombre' => 'TECNOLOGIAS INTELIGENTES DE SOFTWARE',
                'activo' => true,
            ],
            [
                'clave' => 'ingenieria_de_software',
                'nombre' => 'INGENIERIA DE SOFTWARE',
                'activo' => true,
            ],
            [
                'clave' => 'inteligencia_artificial',
                'nombre' => 'INTELIGENCIA ARTIFICIAL',
                'activo' => true,
            ],
            [
                'clave' => 'sistemas_hibridos_inteligentes',
                'nombre' => 'SISTEMAS HIBRIDOS INTELIGENTES',
                'activo' => true,
            ],
            [
                'clave' => 'sistemas_distribuidos',
                'nombre' => 'SISTEMAS DISTRIBUIDOS',
                'activo' => true,
            ],
        ];

        foreach ($areasEspecialidad as $area) {
            AreasEspecialidadEstudiantesSaae::updateOrCreate(
                [
                    'clave' => $area['clave'],
                ],
                [
                    'nombre' => $area['nombre'],
                    'activo' => $area['activo'],
                ]
            );
        }

    }
}