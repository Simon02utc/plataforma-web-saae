<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pie_pagina/estilos_sobre_nosotros.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Mision y Visión | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="content-sobre-nosotros-pie-pagina">
        <div class="sobre-nosotros">
            <h1>Misión</h1>

            <div class="div-sobre-nost">
                <p>
                Formar investigadores e innovadores tecnológicos competitivos internacionalmente que aporten soluciones tecnológicas, mediante un ejercicio responsable y ético.
                </p>
            </div>

            
            <h1>Visión</h1>

            <div class="div-sobre-nost">
                <p>
                Ser una institución reconocida nacional e internacionalmente por su calidad en la investigación y la formación integral de investigadores e innovadores tecnológicos, que contribuyan al desarrollo pertinente y sustentable.
                </p>
            </div>

            <p class="ultima-actualizacion"><span>Última actualización:</span> 03 de febrero del 2026</p>

            <div class="div-sobre-nost-content-img">
                <img class="img-ilustrativa" src="{{ asset('img_plataforma/mision_vision.png') }}" alt="">
            </div>

        </div>
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->