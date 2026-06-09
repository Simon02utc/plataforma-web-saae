<?php

namespace App\Services\Estudiantes;

use App\Models\EstudiantesSaae;
use App\Models\ActivacionCuentaEstudianteSaae;
use App\Mail\ActivacionCuentaEstudianteMail;
use App\Mail\RegistroEstudiantesMail;
use App\Mail\ActualizacionDatosRegistroEstudianteMail;
use App\Mail\RecuperarContrasenaEstudianteMail;
use App\Mail\ContrasenaRestablecidaEstudianteMail;
use Illuminate\Support\Facades\Mail;

class NotificacionesEstudiantesService  
{

    public function enviarCorreoRegistroEstudiante(EstudiantesSaae $estudiantes, string $passwordPlano): bool
    {
        try {

            //relaciones de los modelos de EstudiantesSaae y EstudianteConDatosEscolares
            $estudiantes->loadMissing([
                'EstudiantesConDatosEscolares.datoEscolarDeAreaEspecialidad',
                'EstudiantesConDatosEscolares.datoEscolarDeEstatus',
            ]);

            $datosEscolares = $estudiantes->EstudiantesConDatosEscolares;

            $meses = [
                1 => 'Enero',
                2 => 'Febrero',
                3 => 'Marzo',
                4 => 'Abril',
                5 => 'Mayo',
                6 => 'Junio',
                7 => 'Julio',
                8 => 'Agosto',
                9 => 'Septiembre',
                10 => 'Octubre',
                11 => 'Noviembre',
                12 => 'Diciembre',
            ];

            $estadoCuentaTexto = $estudiantes->activo
                ? 'Cuenta activada'
                : 'Cuenta pendiente de activación';

            $mesIngresoTexto = $datosEscolares?->mes_ingreso
                ? ($meses[(int) $datosEscolares->mes_ingreso] ?? 'No proporcionado')
                : 'No proporcionado';

            Mail::to($estudiantes->email)->send(
                new RegistroEstudiantesMail(
                    numero_control: $estudiantes->numero_control,
                    nombre: trim(($estudiantes->nombre ?? '') . ' ' . ($estudiantes->apellidos ?? '')) ?: ($estudiantes->nombre_completo ?: 'No proporcionado'),
                    email: $estudiantes->email ?: 'No proporcionado',
                    telefono: $estudiantes->telefono ?: 'No proporcionado',
                    password: $passwordPlano,
                    estado_cuenta: $estadoCuentaTexto,
                    anio_ingreso: (string) ($datosEscolares?->anio_ingreso ?? 'No proporcionado'),
                    mes_ingreso: $mesIngresoTexto,
                    especialidad: $datosEscolares?->datoEscolarDeAreaEspecialidad?->nombre ?? 'No proporcionado',
                    estatus: $datosEscolares?->datoEscolarDeEstatus?->nombre ?? 'No proporcionado',
                    loginUrl: route('grup_estudiante.name_login_estudiante')
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }


    public function enviarCorreoActivacionCuentaEstudiante(EstudiantesSaae $estudiantes, ActivacionCuentaEstudianteSaae $activacion, string $tokenPlano
    ): bool {
        try {
            $urlActivacion = route(
                'grup_estudiante.name_mostrar_formulario_activacion_cuenta_estudiante',
                [
                    'token' => $tokenPlano,
                    'email' => $estudiantes->email,
                ]
            );

            Mail::to($estudiantes->email)->send(
                new ActivacionCuentaEstudianteMail(
                    nombre: trim(($estudiantes->nombre ?? '') . ' ' . ($estudiantes->apellidos ?? ''))
                        ?: ($estudiantes->nombre_completo ?: 'Estudiante'),
                    numero_control: $estudiantes->numero_control,
                    email: $estudiantes->email,
                    urlActivacion: $urlActivacion,
                    vigenciaHoras: 24
                )
            );

            $activacion->update([
                'estado' => 'ENVIADO',
                'enviado_en' => now(),
                'error_detalle' => null,
            ]);

            return true;
        } catch (\Throwable $e) {
            report($e);

            $activacion->update([
                'estado' => 'ERROR',
                'error_detalle' => $e->getMessage(),
            ]);

            return false;
        }
    }


    public function enviarCorreoActualizacionDatosEstudiante(EstudiantesSaae $estudiantes, ?string $correoAnterior = null, array $cambiosRealizados = []): bool {
        try {
            $destinatarios = array_values(array_unique(array_filter([
                $correoAnterior,
                $estudiantes->email,
            ])));

            Mail::to($destinatarios)->send(
                new ActualizacionDatosRegistroEstudianteMail(
                    nombre: $estudiantes->nombre ?: ($estudiantes->nombre_completo ?: 'Estudiante'),
                    cambiosRealizados: $cambiosRealizados,
                    loginUrl: route('grup_estudiante.name_login_estudiante')
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }


    public function enviarCorreoRecuperacionContrasena(EstudiantesSaae $estudiante, string $token): bool
    {
        try {
            $urlRestablecerContrasenaEstudiante = route(
                'grup_estudiante.mostrar_formulario_restablecer_contrasena_estudiante',
                [
                    'token' => $token,
                    'email' => $estudiante->email,
                ]
            );

            Mail::to($estudiante->email)->send(
                new RecuperarContrasenaEstudianteMail(
                    nombre: $estudiante->nombre ?: ($estudiante->nombre_completo ?: 'Usuario'),
                    email: $estudiante->email,
                    token: $token,
                    urlRestablecerContrasenaEstudiante: $urlRestablecerContrasenaEstudiante,
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }


    public function enviarCorreoContrasenaRestablecida(EstudiantesSaae $estudiante): bool
    {
        try {
            Mail::to($estudiante->email)->send(
                new ContrasenaRestablecidaEstudianteMail(
                    nombre: $estudiante->nombre ?: ($estudiante->nombre_completo ?: 'Usuario'),
                    email: $estudiante->email,
                    loginUrl: route('grup_estudiante.name_login_estudiante')
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }
}