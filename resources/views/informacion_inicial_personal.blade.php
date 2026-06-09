<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Login del Personal | SAAE')

<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_contenedor_presentacion_pagina.css') }}">
@endpush

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')
    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">INFORMACIÓN INICIAL</h1>
            <p>Todo el personal registrado en la plataforma <b>SAAE</b>, podra iniciar sesión aqui</p>
            <p class="info-ext"></p>
        </div>
        <img class="img-banner" src="{{ asset('img_plataforma/siluete_buho.png') }}" alt="silueta de buho">

        <div class="banner-btns">
            <a class="btn-accion-banner btn-secundario-banner" href="{{ route('grup_personal.name_login_personal') }}">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Iniciar sesión Personal
            </a>
        </div>
    </div>


    <div class="content-presentacion-pagina">

        <img class="img-presentacion-pagina" src="{{ asset('img_plataforma/personal_saae.png') }}" alt="presentacion_pagina">

        <div class="content-introduccion">

            <div class="intro-div">
                <h3><span>SAAE para el personal</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        El personal autorizado podrá acceder a la plataforma <strong>SAAE</strong> para gestionar y dar seguimiento a la asistencia escolar de los estudiantes asignados.
                        Desde esta sección podrá consultar información académica relevante, revisar alertas, validar justificantes y apoyar en el seguimiento del desempeño del estudiante.
                        <br>
                        La plataforma facilita la organización de la información y permite al personal contar con herramientas digitales para una mejor toma de decisiones dentro del ámbito académico.
                    </p>

                    <img src="{{ asset('img_plataforma/saae_para_personal.png') }}" alt="SAAE para personal">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Consulta de asistencia</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        El personal podrá consultar la asistencia reciente e histórica de los estudiantes asignados, visualizando información como fechas, estatus de asistencia (presente, falta, justificada, entre otros) y detalles de registros.
                        <br>
                        Esta consulta permite identificar patrones de asistencia, detectar inasistencias recurrentes y dar seguimiento oportuno a cada estudiante dentro del periodo académico correspondiente.
                    </p>

                    <img src="{{ asset('img_plataforma/informacion_asistencia.png') }}" alt="Consulta de asistencia">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Gestión de justificantes</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        El personal podrá revisar los justificantes enviados por los estudiantes desde la bandeja correspondiente.
                        Cada justificante incluye información del estudiante, motivo y archivo adjunto para su validación.
                        <br>
                        El personal tendrá la capacidad de aprobar o rechazar justificantes, lo que impactará directamente en el estado de la asistencia del estudiante dentro del sistema.
                        Este proceso permite mantener un control claro y validado de las faltas justificadas.
                    </p>

                    <img src="{{ asset('img_plataforma/subir_justificante_2.png') }}" alt="Gestión de justificantes">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Alertas de asistencia</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        La plataforma genera alertas internas cuando se detectan faltas o situaciones relevantes en la asistencia de los estudiantes.
                        El personal podrá consultar estas alertas, revisar su detalle y dar seguimiento a los casos que requieran atención.
                        <br>
                        Estas alertas permiten una intervención oportuna, facilitando la identificación de estudiantes en riesgo académico debido a inasistencias.
                    </p>

                    <img src="{{ asset('img_plataforma/notificaciones_alertas.png') }}" alt="Alertas de asistencia">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Gestión de información</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        El personal también cuenta con herramientas para la gestión de estudiantes, periodos académicos, catálogos y procesos de importación de datos.
                        <br>
                        Estas funcionalidades permiten mantener la información actualizada, organizada y alineada con los procesos institucionales, garantizando un manejo eficiente de los datos dentro de la plataforma.
                    </p>

                    <img src="{{ asset('img_plataforma/informacion.png') }}" alt="Gestión de información">
                </div>
            </div>

        </div>
    
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/---.js') }}"></script>
@endpush