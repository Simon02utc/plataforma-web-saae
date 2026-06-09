<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContrasenaRestablecidaEstudianteMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $email,
        public string $loginUrl,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Tu contraseña fue restablecida correctamente - Plataforma SAAE',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.estudiantes.vista_correo_contrasena_restablecida_estudiante',
            with: [
                'nombre' => $this->nombre,
                'email' => $this->email,
                'loginUrl' => $this->loginUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}
