<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\PersonalSaae;
use App\Models\RolesPersonalSaae;

class AdminInicialSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_INICIAL_EMAIL', 'admin@saae.com');
        $telefono = env('ADMIN_INICIAL_TELEFONO', '1234567890');

        $admin = PersonalSaae::updateOrCreate(
            ['email' => $email],
            [
                'nombre' => env('ADMIN_INICIAL_NOMBRE', 'Administrador'),
                'apellidos' => env('ADMIN_INICIAL_APELLIDOS', 'Saae'),
                'telefono' => $telefono,
                'password' => Hash::make(env('ADMIN_INICIAL_PASSWORD', 'Admin123456*')),
                'activo' => true,
            ]
        );

        $rolAdmin = RolesPersonalSaae::where('clave', 'admin')->first();

        if ($rolAdmin) {
            $admin->roles()->syncWithoutDetaching([$rolAdmin->id]);
        }
    }
}
