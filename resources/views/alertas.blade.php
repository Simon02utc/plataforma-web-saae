<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_contenedor_presentacion_pagina.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Alertas | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">ALERTAS DE ASISTENCIA</h1>
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

        <img class="img-presentacion-pagina" src="{{ asset('img_plataforma/alertas_saae.png') }}" alt="presentacion_pagina">

        <div class="content-introduccion">

            <div class="intro-div">
                <h3><span>¿Qué son las alertas?</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        Las alertas son notificaciones internas que la plataforma genera de forma automática cuando un estudiante alcanza un número crítico de faltas dentro de un periodo académico.
                        <br>
                        Su objetivo es facilitar la detección temprana de situaciones de riesgo académico y promover una intervención oportuna por parte del personal responsable.
                    </p>

                    <img src="{{ asset('img_plataforma/notificaciones_alertas.png') }}" alt="Qué son las alertas">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>¿A quién van dirigidas?</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        Las alertas son visibles para coordinadores, directores de tesis y personal autorizado dentro de la plataforma, quienes pueden consultarlas y dar seguimiento a los casos que requieran atención.
                        <br>
                        Los estudiantes también pueden visualizar las alertas relacionadas con su propia asistencia, permitiéndoles estar informados sobre su situación académica.
                    </p>

                    <img src="{{ asset('img_plataforma/a_quien_ava_dirigido.png') }}" alt="A quién van dirigidas las alertas">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>¿Cómo se generan?</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        La plataforma evalúa automáticamente los registros de asistencia importados y, cuando se detecta que un estudiante ha superado el umbral de faltas establecido, genera una alerta interna de forma inmediata.
                        <br>
                        Este proceso ocurre sin intervención manual, garantizando que ninguna situación crítica pase desapercibida dentro de la plataforma.
                    </p>

                    <img src="{{ asset('img_plataforma/faltas.png') }}" alt="Cómo se generan las alertas">
                </div>
            </div>

        </div>
    
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->