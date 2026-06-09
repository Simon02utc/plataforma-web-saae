<!--ESTRUCTURA DEL CORREO-->
@extends('layouts.emails.estructura_email')

<!--TITULO DE LA VISTA DEL CORREO-->
@section('title', 'Contraseña restablecida correctamente en la plataforma SAAE')

<!--INDICADOR DEL ESTILOS PARA ESTA VISTA DEL CORREO-->
@section('extra_styles')

@endsection

<!--INDICADOR DE CONTENIDO DEL CORREO--->
@section('content_email')
    <h2>Hola, {{ $nombre }}</h2>

    <p>Te informamos que la contraseña de tu cuenta en la plataforma SAAE fue restablecida correctamente.</p>

    <div class="contenedor-credenciales">
        <p>
            <b>Si realizaste este cambio, ya puedes iniciar sesión con tu nueva contraseña.</b>
        </p>
        <p>
            <b>Si tú no realizaste esta acción, comunícate de inmediato con el personal administrativo.</b>
        </p>
    </div>

    <div class="contenedor-botones">
        <a href="{{ $loginUrl }}">
            Iniciar sesión
        </a>
    </div>

    <p class="informacion-final">
        Por seguridad, evita compartir tu contraseña y no la guardes en navegadores de uso público.
    </p>
@endsection