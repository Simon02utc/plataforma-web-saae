<!--ESTRUCTURA DEL CORREO-->
@extends('layouts.emails.estructura_email')

<!--TITULO DE LA VISTA DEL CORREO-->
@section('title', 'Activación de tu cuenta de estudiante')

<!--INDICADOR DEL ESTILOS PARA ESTA VISTA DEL CORREO-->
@section('extra_styles')

@endsection

<!--INDICADOR DE CONTENIDO DEL CORREO--->
@section('content_email')
    <h2>Estudiante, {{ $nombre }}</h2>

    <p>
        Se generó tu correo institucional para la plataforma SAAE.
        Para activar tu cuenta, define tu contraseña desde el siguiente enlace:
    </p>

    <div class="contenedor-credenciales">
        <p><b>Número de control:</b> {{ $numero_control }}</p>
        <p><b>Correo institucional:</b> {{ $email }}</p>
        <p><b>Vigencia del enlace:</b> {{ $vigenciaHoras }} hrs</p>
    </div>

    <div class="contenedor-botones">
        <a href="{{ $urlActivacion  }}">
            Activar mi cuenta
        </a>
    </div>

    <p><b>Si el botón no funciona, copia y pega este enlace en tu navegador:</b></p>
    <p>{{ $urlActivacion }}</p>

    <p class="informacion-final">
        Si tú no solicitaste esta activación, puedes ignorar este correo.
    </p>
@endsection