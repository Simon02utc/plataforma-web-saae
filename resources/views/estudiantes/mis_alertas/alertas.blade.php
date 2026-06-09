<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.estudiante.estructura_web_estudiante')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tarjetas_resumen.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Alertas | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Alertas de asistencia</h1>
        </div>

        <div class="contenedor">

            <p class="texto-contenedor">
                Consulta las alertas generadas por tu asistencia. Estas alertas permiten dar seguimiento a faltas o situaciones importantes dentro del periodo escolar.
            </p>

            <div class="contenedores-informacion">

                <div class="contenedor-tarjetas-resumen">
                    <div class="tarjeta-resumen-asistencia naranja">
                        <p class="titulo-tarjeta-resumen">Pendientes</p>
                        <p class="valor-tarjeta-resumen" id="resumen-alertas-pendientes">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia azul">
                        <p class="titulo-tarjeta-resumen">Normales</p>
                        <p class="valor-tarjeta-resumen" id="resumen-alertas-normales">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia azul">
                        <p class="titulo-tarjeta-resumen">Especiales</p>
                        <p class="valor-tarjeta-resumen" id="resumen-alertas-especiales">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">Atendidas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-alertas-atendidas">0</p>
                    </div>
                </div>

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-alertas">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-alertas" class="input-buscar-tabla"
                                placeholder="Buscar por tipo, estado u observación">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-alertas">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    <div class="contenedor-filtros-tabla">
                        <select class="select-buscar-tabla" id="filtro-periodo-alertas">
                            <option class="option-input-field-select" value="">-- Todos los periodos --</option>
                            @foreach($periodos as $periodo)
                                <option class="option-input-field-select" value="{{ $periodo->id }}">
                                    {{ $periodo->nombre }}
                                </option>
                            @endforeach
                        </select>

                        <select class="select-buscar-tabla" id="filtro-tipo-alertas">
                            <option class="option-input-field-select" value="">-- Todos los tipos --</option>
                            <option value="FALTA_ACUMULADA">Falta acumulada</option>
                            <option value="SUSPENSION_BECA_ESCOLAR">Suspensión de beca escolar</option>
                        </select>

                        <select class="select-buscar-tabla" id="filtro-estado-alertas">
                            <option class="option-input-field-select" value="">-- Todos los estados --</option>
                            <option class="option-input-field-select" value="PENDIENTE">Pendiente</option>
                            <option class="option-input-field-select" value="ATENDIDA">Atendida</option>
                            <option class="option-input-field-select" value="CERRADA">Cerrada</option>
                        </select>

                        <select class="select-buscar-tabla" id="filtro-per-page-alertas">
                            <option class="option-input-field-select" value="10">10</option>
                            <option class="option-input-field-select" value="20" selected>20</option>
                            <option class="option-input-field-select" value="50">50</option>
                            <option class="option-input-field-select" value="100">100</option>
                        </select>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-alertas"
                                data-url-tabla-alertas="{{ route('grup_estudiante.grup_alertas.name_alertas_tabla') }}"
                                data-url-resumen-alertas="{{ route('grup_estudiante.grup_alertas.name_alertas_resumen') }}"
                                data-url-ver-mi-alerta="{{ route('grup_estudiante.grup_alertas.name_ver_alerta', ['id' => '__ID__']) }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Periodo</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Fecha referencia</th>
                                        <th>Fecha disparo</th>
                                        <th>Estado</th>
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
                        <button type="button" id="btn-anterior-alertas" class="btn-pagina-tabla">
                            <i class="fa-solid fa-angle-left"></i> Anterior
                        </button>

                        <span id="texto-pagina-alertas" class="texto-pagina-tabla">
                            Página 1 de 1
                        </span>

                        <button type="button" id="btn-siguiente-alertas" class="btn-pagina-tabla">
                            Siguiente <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>

                    <div class="contenedor-resumen-tabla">
                        <p id="info-paginacion-alertas">
                            Mostrando 0 a 0 de 0 alertas
                        </p>
                    </div>

                </div>
            </div>
        </div>

    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/estudiantes/mis_alertas/funcion_tabla_alertas.js') }}"></script>
@endpush