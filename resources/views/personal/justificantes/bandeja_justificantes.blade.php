<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

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
            <h2 class="titulo-contenedor"><span>Justificantes de estudiantes</span></h2>

            <p class="texto-contenedor">
                Consulta, revisa, aprueba o rechaza los justificantes enviados por los estudiantes asignados.
                Al aprobar un justificante, las faltas seleccionadas serán marcadas como justificadas.
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
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-justificantes-personal">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-justificantes-personal" class="input-buscar-tabla"
                                placeholder="Buscar por folio, estudiante, No. control o motivo">

                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-justificantes-personal">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    <div class="contenedor-filtros-tabla">
                        <select class="select-buscar-tabla" id="filtro-estado-justificantes-personal">
                            <option value="">-- Todos los estados --</option>
                            <option value="PENDIENTE">Pendiente</option>
                            <option value="APROBADO">Aprobado</option>
                            <option value="RECHAZADO">Rechazado</option>
                            <option value="CANCELADO">Cancelado</option>
                        </select>

                        <select class="select-buscar-tabla" id="filtro-per-page-justificantes-personal">
                            <option value="10">10</option>
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-justificantes-personal"
                                data-url-tabla-justificantes="{{ route('grup_personal.grup_justificantes.name_tabla_justificantes') }}"
                                data-url-ver-detalles-justificante="{{ route('grup_personal.grup_justificantes.name_ver_justificante', ['id' => '__ID__']) }}"
                                data-url-aprobar-justificante="{{ route('grup_personal.grup_justificantes.name_aprobar_justificante', ['id' => '__ID__']) }}"
                                data-url-rechazar-justificante="{{ route('grup_personal.grup_justificantes.name_rechazar_justificante', ['id' => '__ID__']) }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Folio</th>
                                        <th>No. control</th>
                                        <th>Estudiante</th>
                                        <th>Periodo</th>
                                        <th>Motivo</th>
                                        <th>Fechas</th>
                                        <th>Estado</th>
                                        <th class="th-ultimo">Revisar / inspeccionar</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="contenedor-paginacion-tabla">
                        <button type="button" id="btn-anterior-justificantes-personal" class="btn-pagina-tabla">
                            <i class="fa-solid fa-angle-left"></i> Anterior
                        </button>

                        <span id="texto-pagina-justificantes-personal" class="texto-pagina-tabla">
                            Página 1 de 1
                        </span>

                        <button type="button" id="btn-siguiente-justificantes-personal" class="btn-pagina-tabla">
                            Siguiente <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>

                    <div class="contenedor-resumen-tabla">
                        <p id="info-paginacion-justificantes-personal">
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
    <script src="{{ asset('js/personal/justificantes/funcion_tabla_justificantes_personal.js') }}"></script>
@endpush