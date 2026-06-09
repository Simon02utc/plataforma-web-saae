<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\PermisosSaae;

class PermisosSaaeSeeder extends Seeder
{
    public function run(): void
    {
        $permisos = [
            [
                'clave' => 'modulo_importacion.ver',
                'nombre' => 'Ver módulo de importación',
                'descripcion' => 'Permite acceder al módulo de importación.',
            ],
            [
                'clave' => 'panel_personal.ver',
                'nombre' => 'Ver el panel (dashboard) de la plataforma SAAE',
                'descripcion' => 'Permite acceder a la sección del panel.',
            ],
            [
                'clave' => 'estudiantes.ver',
                'nombre' => 'Ver estudiantes',
                'descripcion' => 'Permite acceder al módulo de estudiantes.',
            ],
            [
                'clave' => 'asistencia_estudiantes.ver',
                'nombre' => 'Ver asistencia de estudiantes',
                'descripcion' => 'Permite acceder al módulo de asistencia de estudiantes.',
            ],
            [
                'clave' => 'justificantes.ver',
                'nombre' => 'Ver justificantes',
                'descripcion' => 'Permite acceder al módulo de justificantes.',
            ],
            [
                'clave' => 'alertas.ver',
                'nombre' => 'Ver alertas',
                'descripcion' => 'Permite acceder al módulo de alertas.',
            ],
            [
                'clave' => 'auditoria_seguridad.ver',
                'nombre' => 'Ver auditoría y seguridad',
                'descripcion' => 'Permite acceder al módulo de auditoría y seguridad.',
            ],
            [
                'clave' => 'guia_manual_personal.ver',
                'nombre' => 'Ver guía o manual personal',
                'descripcion' => 'Permite acceder a la guía/manual del personal.',
            ],
        ];

        foreach ($permisos as $permiso) {
            PermisosSaae::updateOrCreate(
                [
                    'clave' => $permiso['clave']
                ],
                [
                    'nombre' => $permiso['nombre'],
                    'descripcion' => $permiso['descripcion'],
                ]
            );
        }
    }
}
