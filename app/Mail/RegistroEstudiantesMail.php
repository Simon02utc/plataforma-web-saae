<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class RegistroEstudiantesMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $numero_control,
        public string $nombre,
        public string $email,
        public string $telefono,
        public string $password,
        public string $estado_cuenta,
        public string $anio_ingreso,
        public string $mes_ingreso,
        public string $especialidad,
        public string $estatus,
        public string $loginUrl
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Registro de estudiante en la plataforma SAAE',
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.estudiantes.vista_correo_registro_estudiante',
            with: [
                'numero_control' => $this->numero_control,
                'nombre' => $this->nombre,
                'email' => $this->email,
                'telefono' => $this->telefono,
                'password' => $this->password,
                'estado_cuenta' => $this->estado_cuenta,
                'anio_ingreso' => $this->anio_ingreso,
                'mes_ingreso' => $this->mes_ingreso,
                'especialidad' => $this->especialidad,
                'estatus' => $this->estatus,                
                'loginUrl' => $this->loginUrl,
            ]
        );
    }

    public function attachments(): array
    {
        return [];
    }
}