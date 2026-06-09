<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecuperarContrasenaPersonalMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
        public string $token,
        public string $urlRestablecerContrasenaPersonal,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Solicitud para recuperar tu contraseña - Plataforma SAAE',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.personal.vista_correo_restablecer_contrasena_personal',
            with: [
                'nombre' => $this->nombre,
                'email' => $this->email,
                'token' => $this->token,
                'urlRestablecerContrasenaPersonal' => $this->urlRestablecerContrasenaPersonal,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}