<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_restablecer_contraseña.css') }}">
    <!--Comparte CSS con input y boton de los CSS del formulario principal-->
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Restablecer contraseña | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">RESTABLECER CONTRASEÑA</h1>
            <p>Recuerda que solo tienes 3 intentos por día. Y no guardes tus contraseñas en tu navegador.</p>
            <p class="info-ext"></p>
        </div>
        <img class="img-banner" src="{{ asset('img_plataforma/siluete_buho.png') }}" alt="silueta de buho">
        <a class="btn-accion-banner" href="{{ route('grup_estudiante.name_login_estudiante') }}">
            Iniciar sesión
        </a>
    </div>


    <div class="contenedor-restablecer-contrasena">

        <div class="box-tiempo-restante">
            <h3 class="titulo-contador-restablecer">Tiempo restante del enlace:</h3>
            <p id="contador-restablecer" data-segundos-restantes="{{ $segundosRestantes }}">
                00:00
            </p>
            <p id="mensaje-expirado" style="display:none;">
                El enlace expiró. Ya no puedes actualizar la contraseña desde esta página.
            </p>
        </div>

        <form id="form-restablecer-contrasena-estudiante" method="POST" action="{{ route('grup_estudiante.actualizar_contrasena_estudiante') }}">
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div class="input-box">
                <input type="email" class="input-field" name="email" value="{{ old('email', $email) }}" required readonly>
            </div>

            <div class="input-box">
                <input type="password" id="password-input" class="input-field" name="password" placeholder="Nueva contraseña" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minino 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)" required>
                <i class="ri-lock-2-line icon" id="togglePassword"></i>
            </div>

            <div class="input-box">
                <input type="password" id="confirm-password-input" class="input-field" name="password_confirmation" placeholder="Confirmar contraseña" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minino 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)" required>
                <i class="ri-lock-2-line icon" id="toggleConfirmPassword"></i>
            </div>

            <button type="submit" id="btn-actualizar-contrasena" class="input-submit acceder-enviar">
                <span>Actualizar contraseña</span>
                <span class="spinner"></span>
                <span class="texto-spinner">Espera</span>
            </button>

            <div id="mensaje-formulario-restablecer" class="alerta" style="display: none;"></div>

        </form>

    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/estudiantes/restablecer_contrasena_estudiante/funcion_restablecer_contrasena_estudiante.js') }}"></script>
    <script src="{{ asset('js/ver_verificar_contrasena_login.js') }}"></script>
@endpush