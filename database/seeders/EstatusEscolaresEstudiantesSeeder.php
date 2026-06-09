<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\EstatusEscolaresEstudiantesSaae;

class EstatusEscolaresEstudiantesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {

        $estatusEscolaresEstudiantes = [
            [
                'clave' => 'inscrito',
                'nombre' => 'INSCRITO',
                'descripcion' => null,
                'activo' => true,
            ],
            [
                'clave' => 'suspendido',
                'nombre' => 'SUSPENDIDO',
                'descripcion' => null,
                'activo' => true,
            ],
            [
                'clave' => 'no_inscrito',
                'nombre' => 'NO INSCRITO',
                'descripcion' => null,
                'activo' => true,
            ],
            [
                'clave' => 'titulado',
                'nombre' => 'TITULADO',
                'descripcion' => null,
                'activo' => true,
            ],
            [
                'clave' => 'extemporaneo',
                'nombre' => 'EXTEMPORANEO',
                'descripcion' => null,
                'activo' => true,
            ],

            [
                'clave' => 'baja_temporal',
                'nombre' => 'BAJA TEMPORAL',
                'descripcion' => null,
                'activo' => true,
            ],
            [
                'clave' => 'baja',
                'nombre' => 'BAJA',
                'descripcion' => null,
                'activo' => true,
            ],
            [
                'clave' => 'no_asistio_a_clases',
                'nombre' => 'NO ASISTIO A CLASES',
                'descripcion' => null,
                'activo' => true,
            ],
        ];

        foreach ($estatusEscolaresEstudiantes as $estatus) {
            EstatusEscolaresEstudiantesSaae::updateOrCreate(
                [
                    'clave' => $estatus['clave'],
                ],
                [
                    'nombre' => $estatus['nombre'],
                    'descripcion' =>  $estatus['descripcion'],
                    'activo' => $estatus['activo'],
                ]
            );
        }

    }
}