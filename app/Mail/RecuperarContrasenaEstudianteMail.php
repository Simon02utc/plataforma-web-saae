<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RecuperarContrasenaEstudianteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
        public string $token,
        public string $urlRestablecerContrasenaEstudiante,
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
            view: 'emails.estudiantes.vista_correo_restablecer_contrasena_estudiante',
            with: [
                'nombre' => $this->nombre,
                'email' => $this->email,
                'token' => $this->token,
                'urlRestablecerContrasenaEstudiante' => $this->urlRestablecerContrasenaEstudiante,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}