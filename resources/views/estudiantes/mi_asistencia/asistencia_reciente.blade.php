<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.estudiante.estructura_web_estudiante')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tarjetas_resumen.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Asistencias recientes | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Asistencias recientes</h1>
        </div>

        <div class="contenedor">

            <p class="texto-contenedor">
                Consulta tu asistencia más reciente dentro del periodo seleccionado. También puedes revisar tu detalle general del periodo.
            </p>

            <div class="contenedores-informacion">

                <div class="contenedor-tarjetas-resumen">
                    <div class="tarjeta-resumen-asistencia azul">
                        <p class="titulo-tarjeta-resumen">Registros</p>
                        <p class="valor-tarjeta-resumen" id="resumen-total-registros">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">Presentes</p>
                        <p class="valor-tarjeta-resumen" id="resumen-presentes">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia rojo">
                        <p class="titulo-tarjeta-resumen">Faltas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-faltas">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia morado">
                        <p class="titulo-tarjeta-resumen">Faltas justificadas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-faltas-justificadas">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia gris">
                        <p class="titulo-tarjeta-resumen">No aplica</p>
                        <p class="valor-tarjeta-resumen" id="resumen-no-aplica">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">% asistencia</p>
                        <p class="valor-tarjeta-resumen" id="resumen-porcentaje-asistencia">0%</p>
                    </div>
                </div>

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-mi-asistencia-reciente">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>
                    </div>

                    <div class="contenedor-filtros-tabla">
                        <select class="select-buscar-tabla" id="filtro-periodo-mi-asistencia-reciente">
                            @foreach($periodos as $periodo)
                                <option class="option-input-field-select" value="{{ $periodo->id }}">
                                    {{ $periodo->nombre }}
                                </option>
                            @endforeach
                        </select>

                        <select class="select-buscar-tabla" id="filtro-estatus-mi-asistencia-reciente">
                            <option class="option-input-field-select" value="">Todos los estatus</option>
                            <option class="option-input-field-select" value="PRESENTE">PRESENTE</option>
                            <option class="option-input-field-select" value="FALTA">FALTA</option>
                            <option class="option-input-field-select" value="JUSTIFICADA">FALTA JUSTIFICADA</option>
                            <option class="option-input-field-select" value="NO_APLICA">NO APLICA</option>
                            <option class="option-input-field-select" value="SIN_REGISTRO">SIN REGISTRO</option>
                        </select>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-mi-asistencia-reciente"
                                data-url-tabla-mi-asistencia-reciente="{{ route('grup_estudiante.grup_asistencia_estudiante.name_tabla_asistencia_reciente') }}"
                                data-url-resumen-mi-asistencia-reciente="{{ route('grup_estudiante.grup_asistencia_estudiante.name_resumen_asistencia_reciente') }}"
                                data-url-detalle-mi-asistencia-estudiante="{{ route('grup_estudiante.grup_asistencia_estudiante.name_detalle_asistencia_estudiante') }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">Fecha</th>
                                        <th>Estatus</th>
                                        <th>Fuente</th>
                                        <th>Primera entrada</th>
                                        <th>Última salida</th>
                                        <th>Marcaciones</th>
                                        <th class="th-ultimo">Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="7" class="td-estado-tabla">Cargando contenido…</td></tr>
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
    <script src="{{ asset('js/estudiantes/mi_asistencia/funcion_tabla_asistencia_reciente.js') }}"></script>
@endpush