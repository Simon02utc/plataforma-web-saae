<!--ESTRUCTURA DEL CORREO-->
@extends('layouts.emails.estructura_email')

<!--TITULO DE LA VISTA DEL CORREO-->
@section('title', 'Actualización de datos de cuenta')

<!--INDICADOR DEL ESTILOS PARA ESTA VISTA DEL CORREO-->
@section('extra_styles')

@endsection

<!--INDICADOR DE CONTENIDO DEL CORREO--->
@section('content_email')
    <h2>Hola, {{ $nombre }}</h2>

    <p>Se han actualizado los siguientes datos de tu cuenta.</p>

    <div class="contenedor-credenciales">
        @if (!empty($cambiosRealizados))

                @foreach ($cambiosRealizados as $cambio)
                    <p>
                        <b>{{ $cambio['campo'] }}:</b> {{ $cambio['antes'] }} → {{ $cambio['despues'] }}
                    </p>
                @endforeach

        @else
            <p><b>Se realizó una actualización en tu cuenta, pero no se modifico ningun dato.</b></p>
        @endif
    </div>

    <div class="contenedor-botones">
        <a href="{{ $loginUrl }}">
            Iniciar sesión
        </a>
    </div>

    <p class="informacion-final">
        Si no reconoces este cambio, comunícate de inmediato con el personal administrativo.
    </p>
@endsection