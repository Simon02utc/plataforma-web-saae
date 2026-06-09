<?php

namespace App\Services\Estudiantes;

use App\Models\IntentoRecuperacionContrasenaEstudiante;
use App\Models\EstudiantesSaae;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrarIntentoRecuperacionContrasenaEstudianteService
{
    public function registrar(
        ?EstudiantesSaae $estudiante,
        string $emailSolicitado,
        string $accion,
        string $resultado,
        ?string $motivo = null,
        ?Request $request = null
    ): void {
        IntentoRecuperacionContrasenaEstudiante::create([
            'estudiante_id' => $estudiante?->id,
            'email_solicitado' => Str::of($emailSolicitado)
                ->lower()
                ->replaceMatches('/\s+/', '')
                ->trim()
                ->toString(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'accion' => $accion,
            'resultado' => $resultado,
            'motivo' => $motivo,
        ]);
    }
}