<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RolesPersonalSaae;


class RolesPersonalSaaeSeeder extends Seeder
{
    public function run(): void
    {
        RolesPersonalSaae::updateOrCreate(
            ['clave' => 'admin'],
            [
                'nombre' => 'Administrador',
                'descripcion' => 'Acceso total de la plataforma (secciones, funciones, etc)',
            ]
        );
    }
}
