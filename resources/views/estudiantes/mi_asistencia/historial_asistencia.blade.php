<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.estudiante.estructura_web_estudiante')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tarjetas_resumen.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Historial de asistencia | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')


    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Historial de asistencia</h1>
        </div>


        <div class="contenedor">
            <p class="texto-contenedor">
                Consulta el historial completo de tu asistencia por periodo escolar. Aquí podrás revisar tus registros diarios, entradas, salidas y número de marcaciones.
            </p>

            <div class="contenedores-informacion">

                <div class="contenedor-tarjetas-resumen">
                    <div class="tarjeta-resumen-asistencia azul">
                        <p class="titulo-tarjeta-resumen">Registros</p>
                        <p class="valor-tarjeta-resumen" id="resumen-total-registros-historial">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">Presentes</p>
                        <p class="valor-tarjeta-resumen" id="resumen-presentes-historial">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia rojo">
                        <p class="titulo-tarjeta-resumen">Faltas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-faltas-historial">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia morado">
                        <p class="titulo-tarjeta-resumen">Faltas jutificadas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-faltas-justificadas-historial">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia gris">
                        <p class="titulo-tarjeta-resumen">No aplica</p>
                        <p class="valor-tarjeta-resumen" id="resumen-no-aplica-historial">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">% asistencia</p>
                        <p class="valor-tarjeta-resumen" id="resumen-porcentaje-historial">0%</p>
                    </div>
                </div>

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla" id="btn-consultar-mi-historial-asistencia">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>
                    </div>

                    <div class="contenedor-filtros-tabla">
                        <select class="select-buscar-tabla" id="filtro-periodo-mi-historial-asistencia">
                            @foreach($periodos as $periodo)
                                <option class="option-input-field-select" value="{{ $periodo->id }}">
                                    {{ $periodo->nombre }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-mi-historial-asistencia"
                                data-url-tabla-mi-historial-asistencia="{{ route('grup_estudiante.grup_asistencia_estudiante.name_tabla_historial_asistencia_estudiante') }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">Fecha</th>
                                        <th>Estatus</th>
                                        <th>Fuente</th>
                                        <th>Primera entrada</th>
                                        <th>Última salida</th>
                                        <th class="th-ultimo">Marcaciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="6" class="td-estado-tabla">Selecciona un periodo y consulta tu historial.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/estudiantes/mi_asistencia/funcion_tabla_historial_asistencia.js') }}"></script>
@endpush