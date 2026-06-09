<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pie_pagina/estilos_informacion_legal.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Información institucional | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="content-informacion-legal-pie-pagina">
        <div class="informacion-legal">
            <h1>Información Institucional</h1>
            <p class="ultima-actualizacion"><span>Última actualización:</span> 03 de febrero del 2026</p>

            <div class="div-info-legal">
                <h3 class="sub-titulo">Titular / Institución</h3>
                <ul class="lista">
                    <li><span>Institución:</span> Centro Nacional de Investigación y Desarrollo Tecnológico (CENIDET) — TecNM</li>
                    <li><span>Domicilio:</span> Interior Internado Palmira S/N, Col. Palmira, C.P. 62490, Cuernavaca, Morelos.</li>
                    <li><span>Contacto:</span> contacto@cenidet.tecnm.mx</li>
                    <li><span>Conmutador:</span> 777 362 7770</li>
                </ul>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">Área responsable</h3>
                <p>---</p>
            </div>

            <div class="div-info-legal">
                <h3 class="sub-titulo">Google Maps</h3>
            
                <!-- Mapa -->
                <div class="location-map">
                    <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d7550.475636456437!2d-99.219949!3d18.876528!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85cddf3a4772c8c3%3A0xca05f1ffbc24908!2sTecNM%20Cenidet!5e0!3m2!1ses!2smx!4v1770253382284!5m2!1ses!2smx" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>

                    <iframe src="https://www.google.com/maps/embed?pb=!1m14!1m8!1m3!1d7550.344507068308!2d-99.221402!3d18.879438!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x85cddf3b114d22ad%3A0xae54f8c7010a6b5d!2sCenidet%20campus%20Ing.%20Mecanica!5e0!3m2!1ses!2smx!4v1770253473013!5m2!1ses!2smx" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
            </div>

        </div>
    </div>
    
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->