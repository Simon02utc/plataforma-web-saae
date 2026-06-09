<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_restablecer_contraseña.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Restablecimiento de contraseña | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')
    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">RESTABLECIMIENTO DE CONTRASEÑA INVALIDO</h1>
            <p>{{ $titulo ?? 'Enlace inválido' }}. {{ $mensaje ?? 'El enlace no es válido o ya no puede utilizarse.' }}</p>
            <p class="info-ext"></p>
        </div>
        <img class="img-banner" src="{{ asset('img_plataforma/siluete_buho.png') }}" alt="silueta de buho">
        <a class="btn-accion-banner" href="{{ route('grup_estudiante.name_login_estudiante') }}">
            Iniciar sesión
        </a>
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/ver_verificar_contrasena_login.js') }}"></script>
    <script src="{{ asset('js/animaciones_login.js') }}"></script>
@endpush