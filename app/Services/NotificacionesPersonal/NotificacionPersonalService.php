<?php

namespace App\Services\NotificacionesPersonal;

use App\Models\PersonalSaae;
use App\Mail\RegistroPersonalMail;
use App\Mail\ActualizacionDatosRegistroPersonalMail;
use App\Mail\RecuperarContrasenaPersonalMail;
use App\Mail\ContrasenaRestablecidaPersonalMail;
use Illuminate\Support\Facades\Mail;

class NotificacionPersonalService
{
    public function enviarCorreoRegistro(PersonalSaae $personal, string $passwordPlano): bool
    {
        try {
            Mail::to($personal->email)->send(
                new RegistroPersonalMail(
                    nombre: $personal->nombre,
                    email: $personal->email,
                    password: $passwordPlano,
                    loginUrl: route('grup_personal.name_login_personal')
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function enviarCorreoActualizacionDatosPersonal(PersonalSaae $personal, ?string $correoAnterior = null, array $cambiosRealizados = []): bool
    {
        try {

            //recibe el correo anterior y arme la lista de destinatarios y quita duplicados
            $destinatarios = array_values(array_unique(array_filter([
                $correoAnterior,
                $personal->email,
            ])));

            Mail::to($destinatarios)->send(
                new ActualizacionDatosRegistroPersonalMail(
                    nombre: $personal->nombre,
                    cambiosRealizados: $cambiosRealizados,
                    loginUrl: route('grup_personal.name_login_personal')
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }


    public function enviarCorreoRecuperacionContrasena(PersonalSaae $personal, string $token): bool
    {
        try {
            $urlRestablecerContrasenaPersonal = route(
                'grup_personal.mostrar_formulario_restablecer_contrasena_personal',
                [
                    'token' => $token,
                    'email' => $personal->email,
                ]
            );

            Mail::to($personal->email)->send(
                new RecuperarContrasenaPersonalMail(
                    nombre: $personal->nombre ?: 'Usuario',
                    email: $personal->email,
                    token: $token,
                    urlRestablecerContrasenaPersonal: $urlRestablecerContrasenaPersonal,
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }


    public function enviarCorreoContrasenaRestablecida(PersonalSaae $personal): bool
    {
        try {
            Mail::to($personal->email)->send(
                new ContrasenaRestablecidaPersonalMail(
                    nombre: $personal->nombre ?: 'Usuario',
                    email: $personal->email,
                    loginUrl: route('grup_personal.name_login_personal')
                )
            );

            return true;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

}