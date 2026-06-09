<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Personal | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

<div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Personal de SAAE</h1>
        </div>

        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información para ti</span></h2>


            <div class="contenedores-informacion">
                <p class="texto">Hola <b>{{ auth('personal')->user()?->nombre ?? '' }}</b>, ya has sido registrado con exito a la plataforma SAAE. Sin embargo, no se te asigno ningun rol, espera a que el personal administrativo de SAAE te asigne un rol.</p>
                
                <div class="contenedor-img">
                    <img src="{{ asset('img_plataforma/tiempo.png') }}" alt="">
                </div>
            </div>
        </div>
    </div>

</div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/---.js') }}"></script>
@endpush