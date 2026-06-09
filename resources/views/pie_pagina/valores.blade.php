<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/pie_pagina/estilos_sobre_nosotros.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Valores | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="content-sobre-nosotros-pie-pagina">
        <div class="sobre-nosotros">
            <h1>Valores</h1>
            <p class="ultima-actualizacion"><span>Última actualización:</span> 03 de febrero del 2026</p>

            <div class="div-sobre-nost">
                <h3 class="sub-titulo">Respeto</h3>
                <p>
                Respetar el criterio ajeno, sea éste igual o divergente del nuestro. Aplicar respeto total a la verdad, y no ser usuario del engaño y la mentira, ofrecer respeto total al desempeño del trabajo, y al derecho legal ajeno, cuidar que nuestros actos no sean causa de daños a nada ni a nadie. Respetar el medio ambiente y los recursos naturales.
                </p>
            </div>

            <div class="div-sobre-nost">
                <h3 class="sub-titulo">Honestidad</h3>
                <p>
                Tener siempre una actitud correcta de respeto a las personas y a los hechos verdaderos. Rechazar el uso de la mentira y el engaño.
                </p>
            </div>

            <div class="div-sobre-nost">
                <h3 class="sub-titulo">Compromiso</h3>
                <p>
                Principalmente con el objetivo de mejorar nuestra situación competitiva en materia de formación de recursos humanos en investigación y mantener la calidad en el servicio a nuestros clientes y mantener un ambiente laboral excelente.
                </p>
            </div>

            <div class="div-sobre-nost">
                <h3 class="sub-titulo">Responsabilidad</h3>
                <p>
                Actuar con probidad, aplicando la normatividad, obligación con el deber cumpliendo la misión y la moral, y contribuir activa y voluntariamente al mejoramiento social, económico y ambiental.
                </p>

                <p>
                Eficaz y Agradable, se encuentran las firmas que han encontrado el equilibrio perfecto entre sus competencias técnicas y su estrategia de servicio al cliente, son organizaciones que se enfocan en el cliente porque saben que es él de quien dependen, están conscientes de la fuerte competencia y sus perspectivas apuntan al liderazgo.
                </p>
            </div>

            <div class="div-sobre-nost">
                <h3 class="sub-titulo">Actitud de Servicio</h3>
                <p>
                Ser Eficaz y agradable, encontrando el equilibrio perfecto entre nuestras competencias técnicas y estrategias de servicio al cliente, como institución enfocarnos al cliente cumpliendo sus perspectivas y que ellos nos distingan por el liderazgo.
                </p>
            </div>

            <div class="div-sobre-nost-content-img">
                <img class="img-ilustrativa" src="{{ asset('img_plataforma/valores.png') }}" alt="">
            </div>

        </div>
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->