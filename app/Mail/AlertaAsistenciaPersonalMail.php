<?php

namespace App\Mail;

use App\Models\AlertaAsistenciaEstudiante;
use App\Models\EstudianteConPersonalSaae;
use App\Models\PersonalSaae;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class AlertaAsistenciaPersonalMail extends Mailable
{
    use Queueable, SerializesModels;

    public AlertaAsistenciaEstudiante $alerta;
    public PersonalSaae $personal;
    public EstudianteConPersonalSaae $asignacion;

    public function __construct(
        AlertaAsistenciaEstudiante $alerta,
        PersonalSaae $personal,
        EstudianteConPersonalSaae $asignacion
    ) {
        $this->alerta = $alerta;
        $this->personal = $personal;
        $this->asignacion = $asignacion;
    }

    public function build()
    {
        $tipoTexto = match ($this->alerta->tipo_alerta) {
            'FALTA_ACUMULADA' => 'Falta acumulada de estudiante asignado',
            'SUSPENSION_BECA_ESCOLAR' => 'Alerta crítica de asistencia',
            default => 'Alerta de asistencia',
        };

        return $this->subject('SAAE | ' . $tipoTexto)
            ->view('emails.alertas.vista_correo_alerta_asistencia_personal')
            ->with([
                'loginUrl' => route('grup_personal.name_login_personal'),
            ]);
    }
}