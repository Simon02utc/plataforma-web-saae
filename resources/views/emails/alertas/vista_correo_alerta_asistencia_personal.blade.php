<!--ESTRUCTURA DEL CORREO-->
@extends('layouts.emails.estructura_email')

<!--TITULO DE LA VISTA DEL CORREO-->
@section('title', 'Notificación de alerta de asistencia de la plataforma SAAE')

<!--INDICADOR DEL ESTILOS PARA ESTA VISTA DEL CORREO-->
@section('extra_styles')

@endsection

<!--INDICADOR DE CONTENIDO DEL CORREO--->
@section('content_email')

    @php
        $estudiante = $alerta->estudiante;
        $periodo = $alerta->periodo;
        $rol = $asignacion->asignacionConRol?->nombre ?? 'Responsable';

        $nombrePersonal = trim(($personal->nombre ?? '') . ' ' . ($personal->apellidos ?? ''));
        if ($nombrePersonal === '') {
            $nombrePersonal = 'Personal';
        }

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


    <h2>Hola, {{ $nombrePersonal }}</h2>

    <p>SAAE detectó una alerta de asistencia correspondiente a uno de tus estudiantes asignados.</p>

    <div class="contenedor-credenciales">
        <h3 class="titulo-contenedor-credenciales">Datos de la alerta:</h3>

        <p><strong>Tipo de alerta:</strong> {{ $tipoTexto }}</p>
        <p><strong>Estudiante:</strong> {{ $nombreEstudiante }}</p>
        <p><strong>Número de control:</strong> {{ $estudiante->numero_control ?? '—' }}</p>
        <p><strong>Periodo:</strong> {{ $periodo->nombre ?? '—' }}</p>
        <p><strong>Valor detectado:</strong> {{ $alerta->valor_detectado ?? 0 }}</p>
        <p><strong>Fecha de referencia:</strong> {{ optional($alerta->fecha_referencia)->format('d/m/Y') ?? '—' }}</p>
        <p><strong>Con rol asignado a:</strong> {{ $rol }}</p>

        <div class="separador-contenedor-credenciale"></div>

        <h3 class="titulo-contenedor-credenciales">Descripción de alerta:</h3>
        @if($alerta->tipo_alerta === 'FALTA_ACUMULADA')
            <p>
                El estudiante registró una nueva falta acumulada en el periodo actual.
            </p>
        @endif

        @if($alerta->tipo_alerta === 'SUSPENSION_BECA_ESCOLAR')
            <p>
                El estudiante alcanzó el umbral de 3 faltas acumuladas, por lo que se generó una alerta especial.
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
        Te recomendamos revisar la sección de alertas y dar seguimiento a la situación del estudiante.
    </p>
@endsection