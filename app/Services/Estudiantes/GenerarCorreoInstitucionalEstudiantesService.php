<?php

namespace App\Services\Estudiantes;

use App\Models\ActivacionCuentaEstudianteSaae;
use App\Models\EstudiantesSaae;
use Illuminate\Support\Str;

class GenerarCorreoInstitucionalEstudiantesService
{
    public const DOMINIO = 'cenidet.tecnm.mx'; //cenidet.tecnm.mx

    public function numeroControlEsFormalizado(?string $numeroControl): bool
    {
        return (bool) preg_match(
            '/^[A-Z][0-9]{2}[A-Z]{2}[0-9]{3}$/',
            strtoupper(trim((string) $numeroControl))
        );
    }


    public function construirCorreoInstitucional(string $numeroControl): string
    {
        return strtolower(trim($numeroControl)) . '@' . self::DOMINIO;
    }


    public function correoEsInstitucional(?string $correo): bool
    {
        if (blank($correo)) {
            return false;
        }

        return Str::endsWith(
            Str::lower(trim($correo)),
            '@' . self::DOMINIO
        );
    }


    public function puedeGenerarse(EstudiantesSaae $estudiante): bool
    {
        return blank($estudiante->email)
            && !$estudiante->activo
            && $this->numeroControlEsFormalizado($estudiante->numero_control);
    }

    public function puedeReenviarActivacion(EstudiantesSaae $estudiante): bool
    {
        return filled($estudiante->email)
            && !$estudiante->activo
            && $this->correoEsInstitucional($estudiante->email);
    }


    public function invalidarActivacionesPendientes(EstudiantesSaae $estudiante): void
    {
        ActivacionCuentaEstudianteSaae::query()
            ->where('estudiante_id', $estudiante->id)
            ->whereNull('usado_en')
            ->whereIn('estado', ['PENDIENTE', 'ENVIADO'])
            ->update([
                'estado' => 'EXPIRADO',
                'expira_en' => now(),
                'updated_at' => now(),
            ]);
    }


    public function crearActivacionPendiente(EstudiantesSaae $estudiante, ?int $generadoPor = null): array
    {
        $this->invalidarActivacionesPendientes($estudiante);

        $tokenPlano = Str::random(64);

        $activacion = ActivacionCuentaEstudianteSaae::create([
            'estudiante_id' => $estudiante->id,
            'email_destino' => $estudiante->email,
            'token_hash' => hash('sha256', $tokenPlano),
            'expira_en' => now()->addHours(24), // ============================ LA VIGENCIA DEL ENLACE SE DEFINE AQUI, para minutos poner addMinutes(30)
            'estado' => 'PENDIENTE',
            'generado_por' => $generadoPor,
        ]);

        return [
            'token_plano' => $tokenPlano,
            'activacion' => $activacion,
        ];
    }
}