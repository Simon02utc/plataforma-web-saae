<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pie_pagina/estilos_informacion_legal.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Política de cookies | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="content-informacion-legal-pie-pagina">
        <div class="informacion-legal">
            <h1>Política de Cookies</h1>
            <p class="ultima-actualizacion"><span>Última actualización:</span> 03 de febrero del 2026</p>

            <div class="div-info-legal">
                <h3 class="sub-titulo">¿Qué son las cookies?</h3>
                <p>
                Son archivos pequeños que ayudan a que el sitio funcione correctamente y, en algunos casos,
                a medir uso del sistema.
                </p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">Cookies que podrían usarse</h3>
                <ul class="lista">
                    <li><span>Estrictamente necesarias:</span> sesión, autenticación, seguridad (ej. CSRF), preferencias básicas.</li>
                    <li><span>Analíticas (opcionales):</span> métricas de uso para mejorar el sistema (si la institución las habilita).</li>
                </ul>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">Gestión</h3>
                <p>
                Puede administrar cookies desde su navegador. Si desactiva cookies necesarias, algunas funciones (inicio de sesión, paneles)
                pueden no funcionar correctamente.
                </p>
            </div>


        </div>
    </div>
    
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->