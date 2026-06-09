<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pie_pagina/estilos_informacion_legal.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Aviso de privacidad | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="content-informacion-legal-pie-pagina">
        <div class="informacion-legal">
            <h1>Aviso de Privacidad</h1>
            <p class="ultima-actualizacion"><span>Última actualización:</span> 03 de febrero del 2026</p>

            <div class="div-info-legal">
                <p>
                SAAE trata datos personales para fines de gestión académica y estadística de asistencia. Como referencia, los sujetos obligados se rigen por la Ley General de Protección de Datos Personales en Posesión de Sujetos Obligados. 
                </p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">1. Datos personales que podrían recabarse</h3>
                <ul class="lista">
                    <li>Identificación académica: nombre, matrícula/número de control, correo institucional.</li>
                    <li>Información de asistencia: registros de entrada/salida, incidencias, historial por periodo.</li>
                    <li>Información técnica: identificadores de dispositivo autorizados (ej. MAC) y registros de conexión a red institucional (cuando aplique).</li>
                    <li>Justificantes: documentos cargados por el estudiante y su estatus de validación.</li>
                </ul>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">2. Finalidades</h3>
                <p>
                Los datos podrán ser consultados por personal autorizado (docentes, coordinaciones, jefaturas y administradores)
                conforme a sus atribuciones y necesidades académicas/administrativas.
                </p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">3. Transferencias</h3>
                <ul class="lista">
                    <li>El sistema puede registrar asistencia mediante reloj checador y/o mecanismos de presencia asociados a la red WiFi institucional.</li>
                    <li>Solo se considerarán dispositivos previamente autorizados por la institución.</li>
                    <li>El uso del sistema es exclusivamente académico y estadístico.</li>
                </ul>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">4. Derechos ARCO</h3>
                <p>
                El titular puede ejercer sus derechos de Acceso, Rectificación, Cancelación y Oposición en los términos aplicables.
                Las guías institucionales suelen alinearse a lineamientos y criterios del INAI. 
                </p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">5. Conservación</h3>
                <p>
                Los datos se conservarán durante el tiempo necesario para cumplir finalidades académicas y obligaciones institucionales
                (definidas por normativa interna y disposiciones aplicables).
                </p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">6. Medidas de seguridad</h3>
                <p>
                Se aplican medidas administrativas, técnicas y físicas para proteger la confidencialidad e integridad de la información.
                </p>
            </div>


        </div>
    </div>
    
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->