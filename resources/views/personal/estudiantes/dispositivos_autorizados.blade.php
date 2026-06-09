<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Fuentes de datos y Parsers | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Dispositivos autorizados</h1>
        </div>

        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de esta sección</span></h2>

            <div class="contenedores-informacion">
                <p class="texto"><span class="palabra-resaltada">Proximamente disponible.</span></p>
                

                <div class="contenedor-img">
                    <img src="{{ asset('img_plataforma/aprendiendo.png') }}" alt="">
                </div>
            </div>
        </div>


    </div>
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/animacion_cambio_formulario.js') }}"></script>
@endpush