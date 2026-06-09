<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pie_pagina/estilos_informacion_legal.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Terminos y condiciones | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="content-informacion-legal-pie-pagina">
        <div class="informacion-legal">
            <h1>Términos y Condiciones</h1>
            <p class="ultima-actualizacion"><span>Última actualización:</span> 03 de febrero del 2026</p>

            <div class="div-info-legal">
                <h3 class="sub-titulo">1. Objeto</h3>
                <p>
                La plataforma SAAE (seguimiento y análisis de asistencia escolar) tiene como finalidad
                digitalizar, automatizar y monitorear la asistencia de la comunidad estudiantil con fines académicos y estadísticos.
                </p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">2. Usuarios y roles</h3>
                <ul class="lista">
                    <li>Estudiantes: Consulta de asistencia, carga de justificantes.</li>
                    <li>Docentes/Coordinadores/Directores/Jefaturas: Gestión de justificantes, estudiantes y asistencia.</li>
                    <li>Administradores: Gestión general de la pltaforma</li>
                </ul>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">3. Acceso y autenticación</h3>
                <p> El acceso puede requerir credenciales institucionales y/o mecanismos de verificación definidos por la institución.
                El usuario es responsable del uso y resguardo de sus credenciales.</p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">4. Registro de asistencia</h3>
                <ul class="lista">
                    <li>La plataforma captura los registros de asistencia provenientes del reloj checador institucional.</li>
                    <li>El uso de la plataforma es exclusivamente académico y estadístico.</li>
                </ul>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">5. Justificantes</h3>
                <ul class="lista">
                    <li>El estudiante podrá cargar documentos justificantes conforme a los lineamientos internos.</li>
                    <li>El personal autorizado podrá revisar, validar o rechazar justificantes, registrando el resultado.</li>
                </ul>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">6. Uso aceptable</h3>
                <ul class="lista">
                    <li>Queda prohibido manipular información, suplantar identidad o intentar acceder a cuentas ajenas.</li>
                    <li>Queda prohibido interferir con el funcionamiento del sistema o realizar ataques informáticos.</li>
                </ul>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">7. Disponibilidad</h3>
                <p> La plataforma está diseñada para operar desde cualquier navegador las 24 horas del día, para garantizar la disponibilidad continua de la plataforma. Sin embargo, puede haber interrupciones por mantenimiento,
                fallas de conectividad o causas ajenas. Se procurará notificar mantenimientos programados cuando sea posible.</p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">8. Propiedad intelectual</h3>
                <p>El código, interfaz y materiales asociados a SAAE se encuentran protegidos por la normativa aplicable y
                por las políticas institucionales correspondientes.</p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">9. Modificaciones</h3>
                <p> Estos términos pueden actualizarse. Las versiones vigentes se pondrán a disposición dentro de la plataforma.</p>
            </div>

        </div>
    </div>
    
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->