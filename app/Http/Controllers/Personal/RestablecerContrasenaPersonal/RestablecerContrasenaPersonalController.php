<?php

namespace App\Http\Controllers\Personal\RestablecerContrasenaPersonal;

use App\Http\Controllers\Controller;
use App\Models\PersonalSaae;
use App\Services\NotificacionesPersonal\NotificacionPersonalService; //Utilizacion del correo electronico para notificar
use App\Services\NotificacionesPersonal\RegistrarIntentoRecuperacionContrasenaPersonalService; //utilizado para el registro de intentos de restablecer contra del personal
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class RestablecerContrasenaPersonalController extends Controller
{

    //==========ENVIO DE ENLACE POR CORREO PARA EL PERSONAL
    public function enviar_correo_enlace_recuperacion_contrasena_personal(Request $request, NotificacionPersonalService $notificacionPersonalService, RegistrarIntentoRecuperacionContrasenaPersonalService $registroIntentoService) 
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
                'email' => ['required', 'email', 'max:190'],
            ], 
            [
                'email.required' => 'El correo es obligatorio.',
                'email.email' => 'El correo no tiene un formato válido.',
            ]
        );
        

        $personal = PersonalSaae::query()
            ->where('email', $request->input('email'))
            ->where('activo', true)
            ->first();

        if (!$personal) {
            $registroIntentoService->registrar(
                personal: null,
                emailSolicitado: $request->input('email'),
                accion: 'solicitar_enlace',
                resultado: 'fallido',
                motivo: 'correo_no_registrado',
                request: $request
            );

            return response()->json([
                'status' => 'error',
                'message' => 'No existe un personal activo registrado con ese correo.',
            ], 404);
        }


        if (!$personal->activo) {
            $registroIntentoService->registrar(
                personal: $personal,
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


        //NO revelar si existe o no, ni si es un admin
        if ($personal->esAdmin()) {
            $registroIntentoService->registrar(
                personal: $personal,
                emailSolicitado: $request->input('email'),
                accion: 'solicitar_enlace',
                resultado: 'bloqueado',
                motivo: 'cuenta_admin',
                request: $request
            );

            return response()->json([
                'status' => 'error',
                'message' => 'La cuenta está inactiva  y no puede recuperar la contraseña...',
            ], 403);
        }

        $token = Password::broker('personal')->createToken($personal);

        $correoEnviado = $notificacionPersonalService->enviarCorreoRecuperacionContrasena(
            $personal,
            $token
        );


        if (!$correoEnviado) {
            $registroIntentoService->registrar(
                personal: $personal,
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
            personal: $personal,
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
        return view('personal.restablecer_contrasena_personal.enlace_restablecimiento_invalido', [
            'titulo' => $titulo,
            'mensaje' => $mensaje,
        ]);
    }


    //==========MOSTRAR EL FORMULARIO PARA RESTABLECER LA CONTRA DEL PERSONAL
    public function mostrar_formulario_restablecer_contrasena_personal(Request $request, string $token, RegistrarIntentoRecuperacionContrasenaPersonalService $registroIntentoService) 
    {
        $email = Str::of($request->query('email', ''))
            ->lower()
            ->replaceMatches('/\s+/', '')
            ->trim()
            ->toString();

        if ($email === '') {
            $registroIntentoService->registrar(
                personal: null,
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

        $personal = PersonalSaae::query()
            ->where('email', $email)
            ->where('activo', true)
            ->first();

        if (!$personal) {
            $registroIntentoService->registrar(
                personal: null,
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

        if (!$personal->activo) {
            $registroIntentoService->registrar(
                personal: $personal,
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

        // si por alguna razón ya existe un token viejo o alguien intenta abrir un enlace del Admin
        if ($personal->esAdmin()) {
            $registroIntentoService->registrar(
                personal: $personal,
                emailSolicitado: $email,
                accion: 'abrir_enlace',
                resultado: 'bloqueado',
                motivo: 'cuenta_admin',
                request: $request
            );

            return $this->vistaEnlaceRestablecimientoInvalido(
                'Acción no permitida',
                'Esta cuenta no puede usar el flujo de restablecimiento de contraseña desde este enlace.'
            );
        }

        // validar que el token realmente siga siendo válido
        $tokenValido = Password::broker('personal')->tokenExists($personal, $token);

        if (!$tokenValido) {
            $registroIntentoService->registrar(
                personal: $personal,
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
                personal: $personal,
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

        $minutosExpiracion = (int) config('auth.passwords.personal.expire', 10);

        $expiraEn = Carbon::parse($resetRow->created_at)
            ->addMinutes($minutosExpiracion);

        $segundosRestantes = (int) max(0, ceil(now()->diffInSeconds($expiraEn, false)));

        if ($segundosRestantes <= 0) {
            $registroIntentoService->registrar(
                personal: $personal,
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
            personal: $personal,
            emailSolicitado: $email,
            accion: 'abrir_enlace',
            resultado: 'permitido',
            motivo: 'token_valido',
            request: $request
        );

        return view('personal.restablecer_contrasena_personal.restablecer_contrasena_personal', [
            'token' => $token,
            'email' => $email,
            'segundosRestantes' => $segundosRestantes,
            'expiraEnTexto' => $expiraEn->format('d/m/Y H:i:s'),
        ]);
    }



    //==========EJECUTAR LA ACTUALIZACION DE LA CONTRASEÑA DEL PERSONAL
    public function actualizar_contrasena_personal( Request $request, RegistrarIntentoRecuperacionContrasenaPersonalService $registroIntentoService,  NotificacionPersonalService $notificacionPersonalService) 
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
            'email' => ['required', 'email', 'max:190'],
            'password' => [
                'required',
                'string',
                'min:6',
                'confirmed',
                'regex:/^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$/'
            ],
        ], [
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.regex' => 'La contraseña debe contener al menos 6 caracteres e incluir 1 mayúscula, 1 número y 1 símbolo (!@#$%^&*).',
        ]);

        $personal = PersonalSaae::query()
            ->with('roles:id,clave')
            ->where('email', $request->input('email'))
            ->first();


        if (!$personal) {
            $registroIntentoService->registrar(
                personal: null,
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


        if (!$personal->activo) {
            $registroIntentoService->registrar(
                personal: $personal,
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


        if ($personal->esAdmin()) {
            $registroIntentoService->registrar(
                personal: $personal,
                emailSolicitado: $request->input('email'),
                accion: 'restablecer_contrasena',
                resultado: 'bloqueado',
                motivo: 'cuenta_admin',
                request: $request
            );

            return response()->json([
                'status' => 'error',
                'message' => 'La cuenta está inactiva y no puede restablecer la contraseña...',
            ], 403);
        }


        $status = Password::broker('personal')->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
            function (PersonalSaae $personal, string $password) {
                $personal->forceFill([
                    'password' => Hash::make($password),
                    'remember_token' => Str::random(60),
                ])->save();

                event(new PasswordReset($personal));
            }
        );


        //envio de correo al actualizar correctamente la contraseña
        if ($status === Password::PASSWORD_RESET) {
            $registroIntentoService->registrar(
                personal: $personal,
                emailSolicitado: $request->input('email'),
                accion: 'restablecer_contrasena',
                resultado: 'exitoso',
                motivo: 'contrasena_actualizada',
                request: $request
            );

            $correoEnviado = $notificacionPersonalService->enviarCorreoContrasenaRestablecida($personal);

            return response()->json([
                'status' => 'success',
                'message' => $correoEnviado
                    ? 'Tu contraseña se actualizó correctamente. Se envió un correo de confirmación.'
                    : 'Tu contraseña se actualizó correctamente, pero no se pudo enviar el correo de confirmación.',
                'redirect_url' => route('grup_personal.name_login_personal'),
            ]);
        }

        $registroIntentoService->registrar(
            personal: $personal,
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