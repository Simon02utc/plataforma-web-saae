<!--ESTRUCTURA DEL CORREO-->
@extends('layouts.emails.estructura_email')

<!--TITULO DE LA VISTA DEL CORREO-->
@section('title', 'Alerta de asistencia de la plataforma SAAE')

<!--INDICADOR DEL ESTILOS PARA ESTA VISTA DEL CORREO-->
@section('extra_styles')

@endsection

<!--INDICADOR DE CONTENIDO DEL CORREO--->
@section('content_email')

    @php
        $estudiante = $alerta->estudiante;
        $periodo = $alerta->periodo;
        $asistencia = $alerta->asistenciaDiaria;

        $nombreEstudiante = trim((string) ($estudiante->nombre_completo ?? ''));
        if ($nombreEstudiante === '') {
            $nombreEstudiante = trim(($estudiante->nombre ?? '') . ' ' . ($estudiante->apellidos ?? ''));
        }
        if ($nombreEstudiante === '') {
            $nombreEstudiante = 'Estudiante';
        }

        $tipoTexto = match ($alerta->tipo_alerta) {
            'FALTA_ACUMULADA' => 'Falta acumulada',
            'SUSPENSION_BECA_ESCOLAR' => 'Suspensión de beca escolar',
            default => 'Alerta de asistencia',
        };
    @endphp


    <h2>Estudiante, {{ $nombreEstudiante }}</h2>

    <p>La plataforma detectó una alerta relacionada con tu asistencia.</p>
    <p>Te recomendamos revisar esta situación para evitar acumulación de faltas académicas.</p>

    <div class="contenedor-credenciales">
        <h3 class="titulo-contenedor-credenciales">Datos de la alerta:</h3>

        <p><strong>Tipo de alerta:</strong> {{ $tipoTexto }}</p>
        <p><strong>Número de control:</strong> {{ $estudiante->numero_control ?? '—' }}</p>
        <p><strong>Periodo:</strong> {{ $periodo->nombre ?? '—' }}</p>
        <p><strong>Valor detectado:</strong> {{ $alerta->valor_detectado ?? 0 }}</p>
        <p><strong>Fecha de referencia:</strong> {{ optional($alerta->fecha_referencia)->format('d/m/Y') ?? '—' }}</p>

        <div class="separador-contenedor-credenciale"></div>

        <h3 class="titulo-contenedor-credenciales">Descripción de alerta:</h3>
        @if($alerta->tipo_alerta === 'FALTA_ACUMULADA')
            <p>
                Se registró una nueva falta acumulada en tu historial de asistencia. Te recomendamos revisar tu situación y mantenerte al tanto de tus asistencias.
            </p>
        @endif

        @if($alerta->tipo_alerta === 'SUSPENSION_BECA_ESCOLAR')
            <p>
                Has alcanzado el umbral de 3 faltas acumuladas. Es importante que revises tu situación a la brevedad, por que perderas tu beca escolar.
            </p>
        @endif

        @if(!empty($alerta->observaciones))
            <p><strong>Observaciones del sistema:</strong> {{ $alerta->observaciones }}</p>
        @endif

    </div>

    <div class="contenedor-botones">
        <a href="{{ $loginUrl }}">
            Iniciar sesión
        </a>
    </div>

    <p class="informacion-final">
        Este correo fue generado automáticamente por SAAE.
    </p>
@endsection