<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_modal_recuperar_contraseña.css') }}">
    
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Login de estudiantes | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">LOGIN PARA EL ESTUDIANTE</h1>
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
                <!--<img src="imagenes/puntero.png" class="form-imagen coin">
                    <img src="imagenes/puntero.png" class="form-imagen spring">
                    <img src="imagenes/puntero.png" class="form-imagen rocket">
                    <img src="imagenes/puntero.png" class="form-imagen cloud">
                    <img src="imagenes/puntero.png" class="form-imagen stars">-->
                </div>
                <div class="featured-words">TecNM/CENIDET
                    <span></span>
                </div>
            </div>


            <div class="col col-2">
                <!-- <div class="btn-box">
                    <button class="btn btn-1" id="login">Sesión</button>
                    <button class="btn btn-2" id="register">Cuenta</button>
                </div> -->

                <!--FORMULARIO 1-->
                <form id="form-login-estudiante" method="POST" action="{{ route('grup_estudiante.name_iniciar_sesion_estudiante') }}" accept-charset="UTF-8">
                    @csrf

                    <div class="login-form">

                        <div class="form-title">
                            <span>Iniciar sesión</span>
                        </div>

                        <div class="form-inputs">
                            <div class="input-box">
                                <input type="email" id="email-login" name="email" class="input-field" placeholder="Correo institucional" autocomplete="off" required>
                                <i class="ri-mail-line icon"></i>
                            </div>

                            <div class="input-box">
                                <input type="password" id="password-login" name="password" placeholder="Contraseña" class="input-field" autocomplete="off" required>
                                <i class="ri-lock-2-line icon" id="togglePasswordLogin"></i>
                            </div>

                            <div class="input-box">
                                <label class="checkbox-input">
                                    <input type="checkbox" name="remember" value="1">
                                    Recuérdame
                                </label>
                            </div>

                            <div class="forgot-pass">
                                <a href="#" id="recuperar-contrasena-estudiante">¿Has olvidado tu contraseña?</a>
                            </div>

                            <!-- <div class="forgot-pass">
                                <a id="btn-ayuda" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                            </div> -->


                            <div class="input-box">
                                <button type="submit" class="input-submit acceder-enviar">
                                    <span>Acceder</span>
                                    <i class="ri-arrow-right-long-line flecha"></i>
                                    <span class="spinner"></span>
                                    <span class="texto-spinner">Espera</span>
                                </button>
                            </div>

                            <p class="texto-informativo-login">Procura no guardar las credenciales en el navegador de su dispositivo.</p>
                        </div>

                        <!--<div class="social-login">
                            <i class="fa-brands fa-google" data-tooltip="No disponible"></i>
                            <i class="fa-brands fa-facebook-f" data-tooltip="No disponible"></i>
                            <i class="fa-brands fa-x-twitter" data-tooltip="No disponible"></i>
                            <i class="fa-brands fa-instagram" data-tooltip="No disponible"></i>
                        </div>-->

                        <div class="image-movil">
                            <a href="../inicio.php" class="nav__logo">
                                <img src="{{ asset('img_plataforma/tec_transparente.png') }}" class="form-imagen-movil">
                            </a>
                        </div>

                    </div>    
                </form>

                <!-- FORMULARIO 2-->
                <!-- <form id="register-form" method="POST" accept-charset="UTF-8">
                    <div class="register-form">

                        <div class="form-title" id="title2">
                            <span>Solicitar enlace de activación</span>
                        </div>

                        <div class="form-inputs">
                            <div class="scroll">

                                <div class="input-box">
                                    <input type="text" id="numero-control" name="numero_control" class="input-field input-numero-cotrol" placeholder="Clave de activación" pattern="^[A-Za-z]{1}[0-9]{2}[A-Za-z]{2}[0-9]{3}$" title="Formato válido: M25CE039 (1 letra, 2 números, 2 letras, 3 números)"  autocomplete="off" required>
                                    <i class="ri-key-line icon"></i>
                                </div>

                                <div class="input-box">
                                    <input type="email" id="email" name="email" class="input-field" placeholder="Correo institucional" title="El correo debe ser valido" autocomplete="off" required>
                                    <i class="ri-mail-line icon"></i>
                                </div>

                                <div class="input-box">
                                    <input type="password" id="password-register" name="password" class="input-field" placeholder="Contraseña" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{5,}$" title="Debe tener al menos 5 caracteres, incluyendo al menos 1 letra mayúscula, 1 número y 1 símbolo" autocomplete="off" required>
                                    <i class="ri-lock-2-line icon" id="togglePassword"></i>
                                </div>

                                <div class="input-box">
                                    <input type="password" id="confirm-password-register" name="confirm_password" class="input-field" placeholder="Repite la contraseña" title="Debe tener al menos 5 caracteres, incluyendo al menos 1 letra mayúscula, 1 número y 1 símbolo" autocomplete="off" required>
                                    <i class="ri-lock-2-line icon" id="toggleConfirmPassword"></i>
                                </div>

                                <div class="input-box">
                                    <label class="checkbox-input">
                                        <input type="checkbox" id="terminos_condiciones" name="terminos_condiciones" title="El correo debe ser valido" required>
                                        Acepto los términos y condiciones
                                    </label>
                                </div>
                                
                            </div>

                            <div class="forgot-pass">
                                <a id="btn-ayuda-registro" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                            </div>

                            <div class="input-box">
                                <button type="button" class="input-submit cancel-btn" id="cancel-btn">
                                    <i class="ri-arrow-left-long-line flecha"></i>
                                    <span>Cancelar</span>
                                </button>

                                <button type="submit" class="input-submit acceder-enviar">
                                    <span>Enviar</span>
                                    <i class="ri-arrow-right-long-line flecha"></i>
                                    <span class="spinner"></span>
                                    <span class="texto-spinner">Espera</span>
                                </button>
                            </div>

                            <p class="texto-informativo-login">Procure no guardar las credenciales en el navegador de su dispositivo.</p>
                        </div>

                        <div class="social-login">
                            <i class="fa-brands fa-google" data-tooltip="No disponible"></i>
                            <i class="fa-brands fa-facebook-f" data-tooltip="No disponible"></i>
                            <i class="fa-brands fa-x-twitter" data-tooltip="No disponible"></i>
                            <i class="fa-brands fa-instagram" data-tooltip="No disponible"></i>
                        </div>


                    </div>
                </form> -->

            </div>
        </div>

    </div>

    <!--MODAL DE RECUPERAR LA CONTRASEÑA-->
    @include('layouts.modal_recuperar_contrasena_estudiante')

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/ver_verificar_contrasena_login.js') }}"></script>
    <script src="{{ asset('js/estudiantes/funcion_formulario_login_estudiantes.js') }}"></script>
    <script src="{{ asset('js/estudiantes/mostrar_modal_recuperar_contraseña_estudiante.js') }}"></script>
    <script src="{{ asset('js/estudiantes/restablecer_contrasena_estudiante/funcion_modal_recuperar_contrasena_estudiante.js') }}"></script>
@endpush