<!--ESTRUCTURA DEL CORREO-->
@extends('layouts.emails.estructura_email')

<!--TITULO DE LA VISTA DEL CORREO-->
@section('title', 'Registro en la plataforma SAAE')

<!--INDICADOR DEL ESTILOS PARA ESTA VISTA DEL CORREO-->
@section('extra_styles')

@endsection

<!--INDICADOR DE CONTENIDO DEL CORREO--->
@section('content_email')
    <h2>Hola, {{ $nombre }}</h2>

    <p>Se ha creado una cuenta de acceso para ti, dentro de la plataforma.</p>

    <div class="contenedor-credenciales">
        <p>
            <b>Correo electrónico:</b> {{ $email }}
        </p>
        <p>
            <b>Contraseña:</b> {{ $password }} <span class="informacion-extra-dato">(por seguridad cambia la contraseña al iniciar sesión)</span>.
        </p>
    </div>

    <div class="contenedor-botones">
        <a href="{{ $loginUrl }}">
            Iniciar sesión
        </a>
    </div>

    <p class="informacion-final">
        Si tú no esperabas este registro, contacta al personal administrativo.
    </p>
@endsection