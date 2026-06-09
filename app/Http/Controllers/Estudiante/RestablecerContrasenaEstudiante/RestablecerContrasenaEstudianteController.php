<?php

namespace App\Http\Controllers\Estudiante\RestablecerContrasenaEstudiante;

use App\Http\Controllers\Controller;
use App\Models\EstudiantesSaae;
use App\Services\Estudiantes\NotificacionesEstudiantesService; //Utilizacion del correo electronico para notificar
use App\Services\Estudiantes\RegistrarIntentoRecuperacionContrasenaEstudianteService; //utilizado para el registro de intentos de restablecer contra del estudiante
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RestablecerContrasenaEstudianteController extends Controller
{

    //==========ENVIO DE ENLACE POR CORREO PARA DEL ESTUDIANTE
    public function enviar_correo_enlace_recuperacion_contrasena_estudiante(Request $request, NotificacionesEstudiantesService $notificacionEstudiantesService, RegistrarIntentoRecuperacionContrasenaEstudianteService $registroIntentoService) 
    {
        $request->merge([
            'email' => Str::of($request->input('email', ''))
                ->lower()
                ->replaceMatches('/\s+/', '')
                ->trim()
                ->toString(),
        ]);

        $request->validate(
            [
                'email' => ['required', 'email', 'max:190', 'ends_with:@cenidet.tecnm.mx'], //'ends_with:@cenidet.tecnm.mx' solo entren con @cenidet.tecnm.mx:
            ], 
            [
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'El correo no tiene un formato válido.',
                'email.ends_with' => 'El correo no tiene un formato válido con @cenidet.tecnm.mx'
            ]
        );
        

        $estudiante = EstudiantesSaae::query()
            ->where('email', $request->input('email'))
            ->where('activo', true)
            ->first();

        if (!$estudiante) {
            $registroIntentoService->registrar(
                estudiante: null,
                emailSolicitado: $request->input('email'),
                accion: 'solicitar_enlace',
                resultado: 'fallido',
                motivo: 'correo_no_registrado',
                request: $request
            );

            return response()->json([
                'status' => 'error',
                'message' => 'No existe un estudiante activo registrado con ese correo.',
            ], 404);
        }


        if (!$estudiante->activo) {
            $registroIntentoService->registrar(
                estudiante: $estudiante,
                emailSolicitado: $request->input('email'),
                accion: 'solicitar_enlace',
                resultado: 'bloqueado',
                motivo: 'cuenta_inactiva',
                request: $request
            );

            return response()->json([
                'status' => 'error',
                'message' => 'La cuenta está inactiva y no puede recuperar la contraseña.',
            ], 403);
        }

        $token = Password::broker('estudiante')->createToken($estudiante);

        $correoEnviado = $notificacionEstudiantesService->enviarCorreoRecuperacionContrasena(
            $estudiante,
            $token
        );


        if (!$correoEnviado) {
            $registroIntentoService->registrar(
                estudiante: $estudiante,
                emailSolicitado: $request->input('email'),
                accion: 'solicitar_enlace',
                resultado: 'fallido',
                motivo: 'error_envio_correo',
                request: $request
            );

            return response()->json([
                'status' => 'error',
                'message' => 'No se pudo enviar el correo de recuperación.',
            ], 500);
        }


        $registroIntentoService->registrar(
            estudiante: $estudiante,
            emailSolicitado: $request->input('email'),
            accion: 'solicitar_enlace',
            resultado: 'permitido',
            motivo: 'correo_enviado',
            request: $request
        );


        return response()->json([
            'status' => 'success',
            'message' => 'Se envió el enlace de recuperación al correo registrado.',
        ]);
    }


    private function vistaEnlaceRestablecimientoInvalido(string $titulo, string $mensaje)
    {
        return view('estudiantes.restablecer_contrasena_estudiante.enlace_restablecimiento_invalido', [
            'titulo' => $titulo,
            'mensaje' => $mensaje,
        ]);
    }


    //==========MOSTRAR EL FORMULARIO PARA RESTABLECER LA CONTRA DEL ESTUDIANTE
    public function mostrar_formulario_restablecer_contrasena_estudiante(Request $request, string $token, RegistrarIntentoRecuperacionContrasenaEstudianteService $registroIntentoService) 
    {
        $email = Str::of($request->query('email', ''))
            ->lower()
            ->replaceMatches('/\s+/', '')
            ->trim()
            ->toString();

        if ($email === '') {
            $registroIntentoService->registrar(
                estudiante: null,
                emailSolicitado: '',
                accion: 'abrir_enlace',
                resultado: 'fallido',
                motivo: 'correo_no_proporcionado',
                request: $request
            );

            return $this->vistaEnlaceRestablecimientoInvalido(
                'Enlace inválido',
                'El enlace de recuperación no contiene un correo válido o está incompleto.'
            );
        }

        $estudiante = EstudiantesSaae::query()
            ->where('email', $email)
            ->where('activo', true)
            ->first();

        if (!$estudiante) {
            $registroIntentoService->registrar(
                estudiante: null,
                emailSolicitado: $email,
                accion: 'abrir_enlace',
                resultado: 'fallido',
                motivo: 'correo_no_registrado',
                request: $request
            );

            return $this->vistaEnlaceRestablecimientoInvalido(
                'Enlace inválido',
                'El enlace es inválido, expiró o ya fue utilizado.'
            );
        }

        if (!$estudiante->activo) {
            $registroIntentoService->registrar(
                estudiante: $estudiante,
                emailSolicitado: $email,
                accion: 'abrir_enlace',
                resultado: 'bloqueado',
                motivo: 'cuenta_inactiva',
                request: $request
            );

            return $this->vistaEnlaceRestablecimientoInvalido(
                'Cuenta inactiva',
                'La cuenta está inactiva y no puede restablecer la contraseña.'
            );
        }

        // validar que el token realmente siga siendo válido
        $tokenValido = Password::broker('estudiante')->tokenExists($estudiante, $token);

        if (!$tokenValido) {
            $registroIntentoService->registrar(
                estudiante: $estudiante,
                emailSolicitado: $email,
                accion: 'abrir_enlace',
                resultado: 'fallido',
                motivo: 'token_invalido',
                request: $request
            );

            return $this->vistaEnlaceRestablecimientoInvalido(
                'Enlace inválido',
                'El enlace es inválido, expiró o ya fue utilizado.'
            );
        }

        // tomar la fecha real de creación del token para calcular el tiempo restante
        $resetRow = DB::table('password_reset_tokens')
            ->where('email', $email)
            ->first();

        if (!$resetRow || !$resetRow->created_at) {
            $registroIntentoService->registrar(
                estudiante: $estudiante,
                emailSolicitado: $email,
                accion: 'abrir_enlace',
                resultado: 'fallido',
                motivo: 'token_invalido',
                request: $request
            );

            return $this->vistaEnlaceRestablecimientoInvalido(
                'Enlace inválido',
                'El enlace es inválido, expiró o ya fue utilizado.'
            );
        }

        $minutosExpiracion = (int) config('auth.passwords.estudiante.expire', 10); //10 minutos

        $expiraEn = Carbon::parse($resetRow->created_at)
            ->addMinutes($minutosExpiracion);

        $segundosRestantes = (int) max(0, ceil(now()->diffInSeconds($expiraEn, false)));

        if ($segundosRestantes <= 0) {
            $registroIntentoService->registrar(
                estudiante: $estudiante,
                emailSolicitado: $email,
                accion: 'abrir_enlace',
                resultado: 'fallido',
                motivo: 'token_expirado',
                request: $request
            );

            return $this->vistaEnlaceRestablecimientoInvalido(
                'Enlace expirado',
                'El enlace ya expiró. Solicita uno nuevo.'
            );
        }

        $registroIntentoService->registrar(
            estudiante: $estudiante,
            emailSolicitado: $email,
            accion: 'abrir_enlace',
            resultado: 'permitido',
            motivo: 'token_valido',
            request: $request
        );

        return view('estudiantes.restablecer_contrasena_estudiante.restablecer_contrasena_estudiante', [
            'token' => $token,
            'email' => $email,
            'segundosRestantes' => $segundosRestantes,
            'expiraEnTexto' => $expiraEn->format('d/m/Y H:i:s'),
        ]);
    }



    //==========EJECUTAR LA ACTUALIZACION DE LA CONTRASEÑA DEL estudiante
    public function actualizar_contrasena_estudiante( Request $request, RegistrarIntentoRecuperacionContrasenaEstudianteService $registroIntentoService,  NotificacionesEstudiantesService $notificacionEstudiantesService) 
    {
        $request->merge([
            'email' => Str::of($request->input('email', ''))
                ->lower()
                ->replaceMatches('/\s+/', '')
                ->trim()
                ->toString(),
        ]);

        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email', 'max:190', 'ends_with:@cenidet.tecnm.mx'], 
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$/'
            ],
        ], [
            'email.ends_with' => 'El correo no tiene un formato válido con @cenidet.tecnm.mx',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe contener al menos 6 caracteres e incluir 1 mayúscula, 1 número y 1 símbolo (!@#$%^&*).',
        ]);

        $estudiante = EstudiantesSaae::query()
            ->where('email', $request->input('email'))
            ->first();


        if (!$estudiante) {
            $registroIntentoService->registrar(
                estudiante: null,
                emailSolicitado: $request->input('email'),
                accion: 'restablecer_contrasena',
                resultado: 'fallido',
                motivo: 'correo_no_registrado',
                request: $request
            );

            return response()->json([
                'status' => 'error',
                'message' => 'No fue posible restablecer la contraseña.',
            ], 422);
        }


        if (!$estudiante->activo) {
            $registroIntentoService->registrar(
                estudiante: $estudiante,
                emailSolicitado: $request->input('email'),
                accion: 'restablecer_contrasena',
                resultado: 'bloqueado',
                motivo: 'cuenta_inactiva',
                request: $request
            );

            return response()->json([
                'status' => 'error',
                'message' => 'La cuenta está inactiva y no puede restablecer la contraseña.',
            ], 403);
        }


        $status = Password::broker('estudiante')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (EstudiantesSaae $estudiante, string $password) {
                $estudiante->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($estudiante));
            }
        );


        //envio de correo al actualizar correctamente la contraseña
        if ($status === Password::PASSWORD_RESET) {
            $registroIntentoService->registrar(
                estudiante: $estudiante,
                emailSolicitado: $request->input('email'),
                accion: 'restablecer_contrasena',
                resultado: 'exitoso',
                motivo: 'contrasena_actualizada',
                request: $request
            );

            $correoEnviado = $notificacionEstudiantesService->enviarCorreoContrasenaRestablecida($estudiante);

            return response()->json([
                'status' => 'success',
                'message' => $correoEnviado
                    ? 'Tu contraseña se actualizó correctamente. Se envió un correo de confirmación.'
                    : 'Tu contraseña se actualizó correctamente, pero no se pudo enviar el correo de confirmación.',
                'redirect_url' => route('grup_estudiante.name_login_estudiante'),
            ]);
        }

        $registroIntentoService->registrar(
            estudiante: $estudiante,
            emailSolicitado: $request->input('email'),
            accion: 'restablecer_contrasena',
            resultado: 'fallido',
            motivo: 'token_invalido_o_expirado',
            request: $request
        );


        return response()->json([
            'status' => 'error',
            'message' => 'El enlace es inválido, expiró o ya fue utilizado.',
        ], 422);
    }

}