<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_contenedor_presentacion_pagina.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Objetivo academico | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">OBJETIVO ACADEMICO</h1>
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
                <h3><span>Propósito de la plataforma</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        SAAE tiene como propósito principal apoyar el control académico y administrativo de la asistencia escolar dentro del CENIDET, automatizando los procesos de seguimiento, análisis, gestión de justificantes y generación de alertas.
                        <br>
                        Su desarrollo busca eficientizar la manera en que la institución gestiona y toma decisiones basadas en la asistencia de sus estudiantes.
                    </p>
                    <img src="{{ asset('img_plataforma/saae_para_personal.png') }}" alt="Propósito académico">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Seguimiento de asistencia</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        La plataforma permite a los docentes y personal autorizado dar seguimiento a la asistencia de los estudiantes de forma digital y en tiempo real, a partir de los registros importados del reloj checador institucional.
                        <br>
                        Esto facilita identificar patrones de inasistencia y tomar decisiones oportunas para cada caso.
                    </p>
                    <img src="{{ asset('img_plataforma/informacion_asistencia.png') }}" alt="Seguimiento de asistencia">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Gestión de justificantes</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        Los estudiantes pueden enviar justificantes de manera digital desde la plataforma, los cuales son revisados y validados por el personal autorizado.
                        <br>
                        Las faltas justificadas se reflejan correctamente en el historial de asistencia, asegurando un registro centralizado y confiable.
                    </p>
                    <img src="{{ asset('img_plataforma/subir_justificante_2.png') }}" alt="Gestión de justificantes">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Alertas automáticas</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        El sistema genera alertas automáticas cuando un estudiante alcanza un umbral de faltas predefinido, notificando a coordinadores, directores de tesis y personal involucrado para una intervención oportuna.
                        <br>
                        Este proceso ocurre sin intervención manual, garantizando que ninguna situación crítica pase desapercibida.
                    </p>
                    <img src="{{ asset('img_plataforma/notificaciones_alertas.png') }}" alt="Alertas automáticas">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Exportación de datos</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        La plataforma permite exportar la información de asistencia en formatos estándar como Excel o CSV, facilitando su uso en otras herramientas administrativas de la institución.
                        <br>
                        Esto apoya la toma de decisiones y permite al personal trabajar con los datos fuera de la plataforma cuando sea necesario.
                    </p>
                    <img src="{{ asset('img_plataforma/informacion.png') }}" alt="Exportación de datos">
                </div>
            </div>

        </div>
    
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->