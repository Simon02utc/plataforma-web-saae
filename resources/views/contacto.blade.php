<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_contenedor_presentacion_pagina.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Contacto | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">CONTACTO</h1>
            <p>Los usuarios registrados en la plataforma <b>SAAE</b>, podra iniciar sesión aqui.</p>
            <p class="info-ext"></p>
        </div>
        <img class="img-banner" src="{{ asset('img_plataforma/siluete_buho.png') }}" alt="silueta de buho">
        
        <div class="banner-btns">
            <a class="btn-accion-banner btn-secundario-banner" href="{{ route('grup_personal.name_login_personal') }}">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Login del Personal
            </a>
            <a class="btn-accion-banner" href="{{ route('grup_estudiante.name_login_estudiante') }}">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Login del Estudiante
            </a>
        </div>
    </div>


    <div class="content-presentacion-pagina">

        <!-- <img class="img-presentacion-pagina" src="{{ asset('img_plataforma/alertas.png') }}" alt="presentacion_pagina"> -->

        <div class="content-introduccion">

            <div class="intro-div">
                <h3><span>Información de contacto</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        Para cualquier duda, reporte de problema o solicitud relacionada con el uso de la plataforma SAAE, puedes comunicarte con el área responsable del sistema dentro del Centro Nacional de Investigación y Desarrollo Tecnológico (CENIDET).
                        <br>
                        El equipo de soporte atenderá tu solicitud a la brevedad posible.
                    </p>
                    <img src="{{ asset('img_plataforma/informacion_contacto.png') }}" alt="Contacto SAAE">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>¿Cuándo contactarnos?</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        Puedes contactarnos si tienes problemas para acceder a la plataforma, si detectas información incorrecta en tus registros de asistencia, o si tienes dudas sobre el uso de alguna funcionalidad como la carga de justificantes o la consulta de alertas.
                        <br>
                        También puedes reportar cualquier comportamiento inesperado del sistema para ayudarnos a mejorarlo.
                    </p>
                    <img src="{{ asset('img_plataforma/advertencia.png') }}" alt="Cuándo contactar">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Institución</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        Centro Nacional de Investigación y Desarrollo Tecnológico (CENIDET).
                        <br>
                        Interior Internado Palmira s/n, Col. Palmira, Cuernavaca, Morelos, México. C.P. 62490.
                    </p>
                    <img src="{{ asset('img_plataforma/nuestra_historia.png') }}" alt="Institución CENIDET">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Acceso a la plataforma</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        El acceso a SAAE se realiza mediante credenciales asignadas por el administrador del sistema. Si aún no cuentas con un usuario registrado o tienes problemas para iniciar sesión, comunícate con el área responsable para solicitar o recuperar tu acceso.
                        <br>
                        Cada usuario visualizará únicamente la información correspondiente a su perfil dentro de la plataforma.
                    </p>
                    <img src="{{ asset('img_plataforma/tarjeta_identificacion.png') }}" alt="Acceso a la plataforma">
                </div>
            </div>

        </div>
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->