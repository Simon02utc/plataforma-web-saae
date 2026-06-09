<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/estilos_contenedor_presentacion_pagina.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Estudiantes | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')
    <!--BANNER DE PAGINA-->
    <div class="content-banner-pagina">
        <div class="banner-info">
            <h1 class="title-banner">INFORMACIÓN INICIAL</h1>
            <p>¡Bienvenidos a <b>SAAE</b>, estudiante! Tu plataforma de apoyo institucional</p>
            <p class="info-ext"></p>
        </div>
        <img class="img-banner" src="{{ asset('img_plataforma/siluete_buho.png') }}" alt="silueta de buho">
        
        <div class="banner-btns">
            <a class="btn-accion-banner" href="{{ route('grup_estudiante.name_login_estudiante') }}">
                <i class="fa-solid fa-arrow-right-to-bracket"></i> Iniciar sesión Estudiante
            </a>
        </div>
    </div>


    <div class="content-presentacion-pagina">

        <img class="img-presentacion-pagina" src="{{ asset('img_plataforma/estudiante.jpg') }}" alt="presentacion_pagina">

        <div class="content-introduccion">

            <div class="intro-div">
                <h3><span>SAAE para el estudiante</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        El estudiante podrá iniciar sesión en la plataforma <strong>SAAE</strong> para consultar de forma clara y organizada su información de asistencia escolar.
                        Desde esta sección podrá revisar sus registros, dar seguimiento a sus faltas, consultar alertas generadas por la plataforma y enviar justificantes digitales cuando sea necesario.
                        <br>
                        La plataforma tiene como objetivo facilitar el seguimiento académico del estudiante, permitiéndole conocer oportunamente su situación de asistencia y atender cualquier observación registrada.
                    </p>

                    <img src="{{ asset('img_plataforma/saae_para_estudiante.png') }}" alt="SAAE para estudiante">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Información de su asistencia</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        La asistencia escolar es un elemento importante dentro del seguimiento académico del estudiante. En esta plataforma, el alumno podrá consultar sus registros de asistencia por periodo, identificando los días en los que se registró como presente, falta, falta justificada o sin registro.
                        <br>
                        Esta información permite al estudiante revisar su historial y detectar oportunamente cualquier situación que requiera aclaración. En caso de presentar una falta, deberá atenderla mediante el envío de un justificante, de acuerdo con los criterios establecidos por la institución.
                    </p>

                    <img src="{{ asset('img_plataforma/informacion_asistencia.png') }}" alt="Información de asistencia">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Registros de asistencia</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        Los registros de asistencia se generan a partir de la información capturada por el reloj checador institucional y posteriormente son procesados dentro de la plataforma <strong>SAAE</strong>.
                        <br>
                        Una vez importados los registros, el estudiante podrá consultar su asistencia de manera organizada, visualizando fechas, estatus y detalles relacionados con sus entradas o salidas registradas.
                        La plataforma no sustituye los lineamientos institucionales, sino que funciona como una herramienta de consulta y seguimiento.
                    </p>

                    <img src="{{ asset('img_plataforma/registros_asistencia_reloj_monitoreo.png') }}" alt="Registros de asistencia">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Subir justificante</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        Cuando el estudiante tenga una falta que requiera aclaración, podrá enviar un justificante digital desde la plataforma. El archivo deberá ser claro, legible y corresponder al motivo indicado.
                        <br>
                        Los justificantes podrán ser revisados por el personal correspondiente y su estado podrá aparecer como pendiente, aprobado o rechazado. En caso de ser aprobado, la asistencia relacionada podrá reflejarse como falta justificada dentro del sistema.
                        <br>
                        <span>
                            Los documentos enviados deben ser auténticos y utilizados únicamente con fines académicos o administrativos. El uso de documentos falsificados, alterados o ajenos puede derivar en una revisión institucional o sanción conforme a los lineamientos aplicables.
                        </span>
                    </p>

                    <img src="{{ asset('img_plataforma/subir_justificante_2.png') }}" alt="Subir justificante">
                </div>
            </div>

            <div class="intro-div">
                <h3><span>Alertas de asistencia</span></h3>

                <div class="contenido-intro-div">
                    <p>
                        La plataforma puede generar alertas internas cuando se detecten faltas o situaciones que requieran atención por parte del estudiante.
                        Estas alertas permiten conocer oportunamente posibles riesgos relacionados con la asistencia y dar seguimiento a los casos que necesiten revisión.
                        <br>
                        El estudiante podrá consultar sus alertas dentro de la plataforma, revisar su estado y atenderlas conforme a las indicaciones correspondientes. Estas notificaciones tienen como finalidad apoyar el seguimiento académico y fomentar una atención temprana.
                    </p>

                    <img src="{{ asset('img_plataforma/notificaciones_alertas.png') }}" alt="Alertas de asistencia">
                </div>
            </div>

        </div>


    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->