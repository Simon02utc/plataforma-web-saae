<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.web')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/estudiantes/estilos_contacto.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Desarrolador de SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <h1>Sobre SAAE/Desarrolador SAAE</h1>
    
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->