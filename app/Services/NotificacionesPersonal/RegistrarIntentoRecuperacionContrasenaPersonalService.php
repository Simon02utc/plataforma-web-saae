<?php

namespace App\Services\NotificacionesPersonal;

use App\Models\IntentoRecuperacionContrasenaPersonal;
use App\Models\PersonalSaae;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class RegistrarIntentoRecuperacionContrasenaPersonalService
{
    public function registrar(
        ?PersonalSaae $personal,
        string $emailSolicitado,
        string $accion,
        string $resultado,
        ?string $motivo = null,
        ?Request $request = null
    ): void {
        IntentoRecuperacionContrasenaPersonal::create([
            'personal_id' => $personal?->id,
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