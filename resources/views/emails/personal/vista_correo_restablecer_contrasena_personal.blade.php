<!--ESTRUCTURA DEL CORREO-->
@extends('layouts.emails.estructura_email')

<!--TITULO DE LA VISTA DEL CORREO-->
@section('title', 'Recuperar contraseña en la plataforma SAAE')

<!--INDICADOR DEL ESTILOS PARA ESTA VISTA DEL CORREO-->
@section('extra_styles')

@endsection

<!--INDICADOR DE CONTENIDO DEL CORREO--->
@section('content_email')
    <h2>Hola, {{ $nombre }}</h2>

    <p>Recibimos una solicitud para restablecer la contraseña de tu cuenta en la plataforma SAAE.</p>

    <div class="contenedor-credenciales">
        <p>
            <b>El enlace caduca después de 10 minutos ⏱️.</b>
        </p>
        <p>
            <b>Recuerda que solo tienes 3 intentos por día.</b>
        </p>
    </div>

    <div class="contenedor-botones">
        <a href="{{ $urlRestablecerContrasenaPersonal }}">
            Restablecer contraseña
        </a>
    </div>

    <p class="informacion-final">
        Si tú no esperabas esta solicitud, puedes ignorar este correo o contactar al personal administrativo.
    </p>
@endsection