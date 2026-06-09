<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.estudiante.estructura_web_estudiante')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tarjetas_resumen.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Bandeja de justificantes | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Bandeja de justificantes</h1>
        </div>

        <div class="contenedor">

            <p class="texto-contenedor">
                Consulta y envía justificantes para tus faltas registradas. Solo aparecerán disponibles las faltas que aún no han sido justificadas ni tienen una solicitud pendiente.
            </p>

            <div class="contenedores-informacion">

                <div class="contenedor-tarjetas-resumen">
                    <div class="tarjeta-resumen-asistencia naranja">
                        <p class="titulo-tarjeta-resumen">Pendientes</p>
                        <p class="valor-tarjeta-resumen" id="resumen-justificantes-pendientes">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">Aprobados</p>
                        <p class="valor-tarjeta-resumen" id="resumen-justificantes-aprobados">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia rojo">
                        <p class="titulo-tarjeta-resumen">Rechazados</p>
                        <p class="valor-tarjeta-resumen" id="resumen-justificantes-rechazados">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia gris">
                        <p class="titulo-tarjeta-resumen">Cancelados</p>
                        <p class="valor-tarjeta-resumen" id="resumen-justificantes-cancelados">0</p>
                    </div>
                </div>

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-justificantes">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <button type="button" class="btn-registro-tabla con-texto-icono btn-abrir-modal-formulario-registro" id="btn-abrir-modal-crear-justificante">
                            <span><i class="fa-solid fa-file-circle-plus"></i> Justificar falta</span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-justificantes" class="input-buscar-tabla"
                                placeholder="Buscar por folio, motivo o estado">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-justificantes">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    <div class="contenedor-filtros-tabla">
                        <select class="select-buscar-tabla" id="filtro-estado-justificantes">
                            <option value="">-- Todos los estados --</option>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="APROBADO">Aprobado</option>
                            <option value="RECHAZADO">Rechazado</option>
                            <option value="CANCELADO">Cancelado</option>
                        </select>

                        <select class="select-buscar-tabla" id="filtro-per-page-justificantes">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-justificantes-estudiante"
                                data-url-tabla-justificantes="{{ route('grup_estudiante.grup_justificantes.name_tabla_justificantes') }}"
                                data-url-faltas-disponibles="{{ route('grup_estudiante.grup_justificantes.name_faltas_disponibles_justificante') }}"
                                data-url-guardar-justificante="{{ route('grup_estudiante.grup_justificantes.name_guardar_enviar_justificante') }}"
                                data-url-ver-justificante="{{ route('grup_estudiante.grup_justificantes.name_ver_justificante', ['id' => '__ID__']) }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Folio</th>
                                        <th>Periodo</th>
                                        <th>Motivo</th>
                                        <th>Fechas</th>
                                        <th>Estado</th>
                                        <th>Revisión</th>
                                        <th class="th-ultimo">Detalle</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="8" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="contenedor-paginacion-tabla">
                        <button type="button" id="btn-anterior-justificantes" class="btn-pagina-tabla">
                            <i class="fa-solid fa-angle-left"></i> Anterior
                        </button>

                        <span id="texto-pagina-justificantes" class="texto-pagina-tabla">
                            Página 1 de 1
                        </span>

                        <button type="button" id="btn-siguiente-justificantes" class="btn-pagina-tabla">
                            Siguiente <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>

                    <div class="contenedor-resumen-tabla">
                        <p id="info-paginacion-justificantes">
                            Mostrando 0 a 0 de 0 justificantes
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/estudiantes/justificantes/funcion_tabla_justificantes_estudiante.js') }}"></script>
@endpush