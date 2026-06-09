<?php

namespace App\Mail;

use App\Models\AlertaAsistenciaEstudiante;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertaAsistenciaEstudianteMail extends Mailable
{
    use Queueable, SerializesModels;

    public AlertaAsistenciaEstudiante $alerta;

    public function __construct(AlertaAsistenciaEstudiante $alerta)
    {
        $this->alerta = $alerta;
    }

    public function build()
    {
        $tipoTexto = match ($this->alerta->tipo_alerta) {
            'FALTA_ACUMULADA' => 'Falta acumulada',
            'SUSPENSION_BECA_ESCOLAR' => 'Suspensión de beca escolar',
            default => 'Alerta de asistencia',
        };

        return $this->subject('SAAE | ' . $tipoTexto)
            ->view('emails.alertas.vista_correo_alerta_asistencia_estudiante')
            ->with([
                'loginUrl' => route('grup_estudiante.name_login_estudiante'),
            ]);
    }
}