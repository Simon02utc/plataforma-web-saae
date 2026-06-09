<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_contenedor_presentacion_pagina.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Sobre SAAE')

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
        <div class="content-introduccion">

            <div class="intro-div">
                <h3><span>¿Qué es SAAE?</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        SAAE (Sistema de Seguimiento y Análisis de Asistencia Escolar) es una plataforma web desarrollada para el Centro Nacional de Investigación y Desarrollo Tecnológico (CENIDET), con el propósito de digitalizar y automatizar los procesos relacionados con el control de asistencia escolar.
                        <br>
                        Centraliza la información académica de estudiantes y personal, permitiendo un seguimiento ordenado, confiable y accesible desde cualquier navegador web.
                    </p>
                    <img src="{{ asset('img_plataforma/que_es_la_plataforma_saae.png') }}" alt="Descripción general SAAE">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>¿Qué problema resuelve?</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        Antes de SAAE, el seguimiento de asistencia se realizaba de forma manual, lo que generaba errores, pérdida de información y dificultades para detectar a tiempo situaciones de riesgo académico.
                        <br>
                        La plataforma elimina estos problemas al integrar en un solo lugar el registro, consulta, gestión de justificantes, generación de alertas y exportación de datos de asistencia.
                    </p>
                    <img src="{{ asset('img_plataforma/que_resuelve.png') }}" alt="Problema que resuelve">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>¿Cómo funciona?</span></h3>
                <div class="contenido-intro-div">
                    <p>
                        La plataforma importa los registros de asistencia generados por el reloj checador institucional y los organiza por estudiante, periodo y área académica.
                        <br>
                        A partir de esa información, el personal autorizado puede consultar asistencias, revisar y validar justificantes, recibir alertas ante inasistencias críticas y exportar reportes en formatos estándar como Excel o CSV.
                    </p>
                    <img src="{{ asset('img_plataforma/como_funciona.png') }}" alt="Cómo funciona SAAE">
                </div>
            </div>

        </div>
    </div>
    
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->