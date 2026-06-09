<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        
        //Orden de insercion de datos iniciales
        $this->call([
            RolesPersonalSaaeSeeder::class,
            PermisosSaaeSeeder::class,
            RolAdminConPermisosSeeder::class,
            AdminInicialSeeder::class,
            ParsersRelojesChecadoresSeeder::class,
            RelojesChecadoresSeeder::class,
            ParsersFuentesDatosEscolaresSeeder::class,
            FuentesDatosEscolaresSeeder::class,
            AreasEspecialidadEstudiantesSeeder::class,
            EstatusEscolaresEstudiantesSeeder::class,
        ]);

        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
    }
}
