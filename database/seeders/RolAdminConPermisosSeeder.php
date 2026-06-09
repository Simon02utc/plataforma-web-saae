<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\RolesPersonalSaae;
use App\Models\PermisosSaae;

class RolAdminConPermisosSeeder extends Seeder
{
    public function run(): void
    {
        $rolAdmin = RolesPersonalSaae::where('clave', 'admin')->first();

        if (!$rolAdmin) {
            return;
        }

        $permisosIds = PermisosSaae::pluck('id')->toArray();

        $rolAdmin->permisos()->syncWithoutDetaching($permisosIds);
    }
}
