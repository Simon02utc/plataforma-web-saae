<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Reportes y graficos | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')


Personal con Rol

Permiso de: reportes_exportacion.ver


@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/---.js') }}"></script>
@endpush