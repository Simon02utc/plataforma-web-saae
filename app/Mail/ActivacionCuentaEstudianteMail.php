<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ActivacionCuentaEstudianteMail extends Mailable
{

    use Queueable, SerializesModels;

    public function __construct(
        public string $nombre,
        public string $numero_control,
        public string $email,
        public string $urlActivacion,
        public int $vigenciaHoras
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Activa tu cuenta institucional en SAAE',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.estudiantes.vista_correo_activacion_cuenta_estudiante',
            with: [
                'nombre' => $this->nombre,
                'numero_control' => $this->numero_control,
                'email' => $this->email,
                'urlActivacion' => $this->urlActivacion,
                'vigenciaHoras' => $this->vigenciaHoras,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }

}