<!--ESTRUCTURA DEL CORREO-->
@extends('layouts.emails.estructura_email')

<!--TITULO DE LA VISTA DEL CORREO-->
@section('title', 'Registro de estudiante en la plataforma SAAE')

<!--INDICADOR DEL ESTILOS PARA ESTA VISTA DEL CORREO-->
@section('extra_styles')

@endsection

<!--INDICADOR DE CONTENIDO DEL CORREO--->
@section('content_email')
    <h2>Estudiante, {{ $nombre }}</h2>

    <p>Has sido registrado manualmente dentro de la plataforma. Ya puedes iniciar sesión.</p>
    <p>Verifica que tus datos escolares esten correctos, si no es así, comunicate con el personal administrativo de SAAE.</p>

    <div class="contenedor-credenciales">
        <h3 class="titulo-contenedor-credenciales">Datos de tu cuenta:</h3>

        <p>
            <b>Numero de control:</b> {{ $numero_control }}
        </p>
        <p>
            <b>Nombre y apellidos:</b> {{ $nombre }}
        </p>
        <p>
            <b>Correo electrónico:</b> {{ $email }}
        </p>
        <p>
            <b>Numero de telefono:</b> {{ $telefono }}
        </p>
        <p>
            <b>Contraseña:</b> {{ $password }} <span class="informacion-extra-dato">(por seguridad cambia la contraseña al iniciar sesión)</span>.
        </p>
        <p>
            <b>Estado:</b> {{ $estado_cuenta }}
        </p>

        <div class="separador-contenedor-credenciale"></div>

        <h3 class="titulo-contenedor-credenciales">Tus datos escolares:</h3>

        <p>
            <b>Mes y año de ingreso:</b> {{ $mes_ingreso }} del {{ $anio_ingreso }} 
        </p>
        <p>
            <b>Área de especialidad:</b> {{ $especialidad }}
        </p>
        <p>
            <b>Estatus académico:</b> {{ $estatus }}
        </p>
    </div>

    <div class="contenedor-botones">
        <a href="{{ $loginUrl }}">
            Iniciar sesión
        </a>
    </div>

    <p class="informacion-final">
        Si tú no esperabas este registro, puedes ignorar este correo o contactar al personal administrativo.
    </p>
@endsection