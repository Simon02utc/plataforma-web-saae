<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Login del Personal | SAAE')

<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_informacion_inicial_personal.css') }}">
    <link rel="stylesheet" href="{{ asset('css/estilos_modal_recuperar_contraseña.css') }}">
@endpush

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')
    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">LOGIN PARA EL PERSONAL</h1>
            <p>¡Bienvenido, por favor ingresa tus datos!</p>
            <p class="info-ext"></p>
        </div>
        <img class="img-banner" src="{{ asset('img_plataforma/siluete_buho.png') }}" alt="silueta de buho">
        <!--<a class="btn-accion-banner" href="#">
            Ir como docente
        </a>-->
    </div>

    <div class="content-form-login">

        <div class="form-container">
            <div class="col col-1">
                <div class="image-layer">
                    <a href="{{ route('name_inicio') }}" class="form-imagen-pc">
                        <img src="{{ asset('img_plataforma/tec_transparente.png') }}" class="form-imagen-main">
                    </a>
                    <img src="{{ asset('img_plataforma/') }}" id="img2" class="form-imagen dots">
                </div>
                <div class="featured-words">TecNM/CENIDET
                    <span></span>
                </div>
            </div>


            <div class="col col-2">

                <!-- Contenedor para la forma del LOGIN -->
                <form id="form-login-personal" method="POST" action="{{ route('grup_personal.name_iniciar_sesion_personal') }}" accept-charset="UTF-8">
                    @csrf <!--Esto es obligatorio en POST/PUT/PATCH/DELETE. protege contra ataques CSRF-->

                    <div class="login-form">

                        <div class="form-title">
                            <span>Iniciar sesión</span>
                        </div>

                        <div class="form-inputs">
                            <div class="input-box">
                                <input type="email" id="email-login" name="email" value="{{ old('email') }}" class="input-field" placeholder="Correo electronico" autocomplete="off" required>
                                <i class="ri-mail-line icon"></i>
                            </div>

                            <div class="input-box">
                                <input type="password" id="password-login" name="password" placeholder="Contraseña" class="input-field" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minino 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)"  autocomplete="off" required>
                                <i class="ri-lock-2-line icon" id="togglePasswordLogin"></i>
                            </div>

                            <div class="input-box">
                                <label class="checkbox-input">
                                    <input type="checkbox" name="remember" value="1">
                                    Recuérdame
                                </label>
                            </div>

                            <div class="forgot-pass">
                                <a href="#" id="recuperar-contrasena">¿Has olvidado tu contraseña?</a>
                            </div>

                            <!-- <div class="forgot-pass">
                                <a id="btn-ayuda" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                            </div> -->

                            <div class="input-box">
                                <button type="submit" class="input-submit acceder-enviar">
                                    <span>Acceder</span>
                                    <i class="ri-arrow-right-long-line flecha"></i>
                                    <span class="spinner-login"></span>
                                    <span class="texto-spinner-login">Espera</span>
                                </button>
                            </div>

                            <p class="texto-informativo-login">Procura no guardar las credenciales en el navegador de su dispositivo.</p>
                        </div>

                        <div class="image-movil">
                            <a href="../inicio.php" class="nav__logo">
                                <img src="{{ asset('img_plataforma/tec_transparente.png') }}" class="form-imagen-movil">
                            </a>
                        </div>

                    </div>    
                </form>

            </div>
        </div>

    </div>
    
    <!--MODAL DE RECUPERAR LA CONTRASEÑA-->
    @include('layouts.modal_recuperar_contraseña')

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/ver_verificar_contrasena_login.js') }}"></script>
    <script src="{{ asset('js/personal/funcion_formulario_login_personal.js') }}"></script>
    <script src="{{ asset('js/personal/mostrar_modal_recuperar_contraseña.js') }}"></script>
    <script src="{{ asset('js/personal/restablecer_contrasena_personal/funcion_modal_recuperar_contrasena_personal.js') }}"> </script>
@endpush