<?php

namespace App\Http\Controllers\Estudiante;

use App\Http\Controllers\Controller;
use App\Models\ActivacionCuentaEstudianteSaae;
use App\Models\EstudiantesSaae;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ActivacionCuentaEstudianteController extends Controller
{

    private function vistaEnlaceActivacionCuentaInvalida(string $titulo, string $mensaje)
    {
        return view('estudiantes.activar_cuenta_estudiante.activacion_cuenta_invalida', [
            'titulo' => $titulo,
            'mensaje' => $mensaje,
        ]);
    }


    public function mostrar_formulario_activacion(Request $request)
    {
        $email = trim((string) $request->query('email', ''));
        $token = trim((string) $request->query('token', ''));

        $activacion = ActivacionCuentaEstudianteSaae::query()
            ->where('email_destino', $email)
            ->where('token_hash', hash('sha256', $token))
            ->first();

        if (!$activacion) {
            return $this->vistaEnlaceActivacionCuentaInvalida(
                'Enlace inválido',
                'El enlace de activación no es válido o no existe.'
            );
        }

        if ($activacion->estado !== 'ENVIADO') {
            return $this->vistaEnlaceActivacionCuentaInvalida(
                'Enlace ya no disponible',
                'Este enlace ya no se puede utilizar. Solicita uno nuevo si aún no has activado tu cuenta.'
            );
        }

        if ($activacion->usado_en !== null) {
            return $this->vistaEnlaceActivacionCuentaInvalida(
                'Enlace ya utilizado',
                'Este enlace ya fue utilizado anteriormente para activar la cuenta.'
            );
        }

        $segundosRestantes = (int) max(0, ceil(now()->diffInSeconds($activacion->expira_en, false)));

        if ($segundosRestantes <= 0) {
            if ($activacion->estado !== 'EXPIRADO') {
                $activacion->update(['estado' => 'EXPIRADO']);
            }

            return $this->vistaEnlaceActivacionCuentaInvalida(
                'Enlace expirado',
                'El tiempo de vigencia del enlace ya terminó. Solicita uno nuevo para activar tu cuenta.'
            );
        }

        return view('estudiantes.activar_cuenta_estudiante.activar_cuenta_estudiante', [
            'email' => $email,
            'token' => $token,
            'segundosRestantes' => $segundosRestantes,
            'expiraEnTexto' => $activacion->expira_en->format('d/m/Y H:i:s'),
        ]);
    }


    public function activar_cuenta(Request $request)
    {
        $data = $request->validate(
            [
                'email' => ['required', 'email'],
                'token' => ['required', 'string'],
                'password' => [
                    'required',
                    'string',
                    'min:6',
                    'confirmed',
                    'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$/',
                ],
            ],
            [
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'El correo no tiene un formato válido.',
                'token.required' => 'El token de activación es obligatorio.',
                'password.required' => 'La contraseña es obligatoria.',
                'password.confirmed' => 'Las contraseñas no coinciden.',
                'password.regex' => 'La contraseña debe contener al menos 6 caracteres e incluir 1 mayúscula, 1 número y 1 símbolo (!@#$%^&*).',
            ]
        );

        $activacion = ActivacionCuentaEstudianteSaae::query()
            ->where('email_destino', trim($data['email']))
            ->where('token_hash', hash('sha256', trim($data['token'])))
            ->first();

        if (!$activacion) {
            return response()->json([
                'status' => 'error',
                'message' => 'La activación no es válida.',
            ], 422);
        }

        if ($activacion->estado !== 'ENVIADO') {
            return response()->json([
                'status' => 'error',
                'message' => 'Esta activación ya no es válida. Solicita un nuevo enlace.',
            ], 422);
        }

        if ($activacion->usado_en !== null) {
            return response()->json([
                'status' => 'error',
                'message' => 'Esta activación ya fue utilizada.',
            ], 422);
        }

        if (now()->greaterThan($activacion->expira_en)) {
            $activacion->update(['estado' => 'EXPIRADO']);

            return response()->json([
                'status' => 'error',
                'message' => 'La activación ha expirado.',
            ], 422);
        }

        DB::transaction(function () use ($activacion, $data) {
            $estudiante = EstudiantesSaae::query()
                ->lockForUpdate()
                ->findOrFail($activacion->estudiante_id);

            $estudiante->update([
                'password' => Hash::make($data['password']),
                'activo' => true,
                'cuenta_activada_en' => now(),
            ]);

            $activacion->update([
                'estado' => 'ACTIVADO',
                'usado_en' => now(),
                'error_detalle' => null,
            ]);

            ActivacionCuentaEstudianteSaae::query()
                ->where('estudiante_id', $estudiante->id)
                ->where('id', '!=', $activacion->id)
                ->whereNull('usado_en')
                ->whereIn('estado', ['PENDIENTE', 'ENVIADO'])
                ->update([
                    'estado' => 'EXPIRADO',
                    'expira_en' => now(),
                    'updated_at' => now(),
                ]);
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Tu cuenta ha sido activada correctamente.',
            'redirect_url' => route('grup_estudiante.name_login_estudiante'),
        ]);
    }
}