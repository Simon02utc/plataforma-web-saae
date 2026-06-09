<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tarjetas_resumen.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Historial de alertas | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Historial de alertas</h1>
        </div>

        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Historial de alertas</span></h2>

            <p class="texto-contenedor">Se muestran las alertas de asistencia que ya fueron atendidas o cerradas.</p>
            <p class="texto-contenedor">Esta sección permite consultar el seguimiento realizado sobre alertas anteriores de los estudiantes que tienes asignados.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tarjetas-resumen">
                    <div class="tarjeta-resumen-asistencia azul">
                        <p class="titulo-tarjeta-resumen">Históricas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-historial-total">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">Atendidas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-historial-atendidas">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia gris">
                        <p class="titulo-tarjeta-resumen">Cerradas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-historial-cerradas">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">Gestionadas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-historial">0</p>
                    </div>
                </div>

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-historial-alertas">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input
                                type="text"
                                id="input-buscar-historial-alertas"
                                class="input-buscar-tabla"
                                placeholder="Buscar por No. control o Nombre"
                            >
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-historial-alertas">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    <div class="contenedor-filtros-tabla">
                        <select class="select-buscar-tabla" id="filtro-periodo-historial-alertas">
                            <option class="option-input-field-select" value="">-- Todos los periodos --</option>
                            @foreach($periodos as $periodo)
                                <option class="option-input-field-select" value="{{ $periodo->id }}">
                                    {{ $periodo->nombre }}
                                </option>
                            @endforeach
                        </select>

                        <select class="select-buscar-tabla" id="filtro-tipo-historial-alertas">
                            <option class="option-input-field-select" value="">-- Todos los tipos --</option>
                            <option class="option-input-field-select" value="FALTA_ACUMULADA">Falta acumulada</option>
                            <option class="option-input-field-select" value="SUSPENSION_BECA_ESCOLAR">Suspensión de beca escolar</option>
                        </select>

                        <select class="select-buscar-tabla" id="filtro-estado-historial-alertas">
                            <option class="option-input-field-select" value="">-- Atendidas y cerradas --</option>
                            <option class="option-input-field-select" value="ATENDIDA">ATENDIDA</option>
                            <option class="option-input-field-select" value="CERRADA">CERRADA</option>
                        </select>

                        <select id="filtro-per-page-historial-alertas" class="select-buscar-tabla">
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table
                                class="tabla-contenido"
                                id="tabla-historial-alertas"
                                data-url-tabla-historial-alertas="{{ route('grup_personal.grup_alertas.name_historial_alertas_tabla') }}"
                                data-url-resumen-historial-alertas="{{ route('grup_personal.grup_alertas.name_historial_alertas_resumen') }}"
                                data-url-ver-alerta="{{ route('grup_personal.grup_alertas.name_ver_alerta', ['id' => '__ID__']) }}"
                            >
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>No. Control</th>
                                        <th>Nombre</th>
                                        <th>Tipo</th>
                                        <th>Valor</th>
                                        <th>Fecha ref.</th>
                                        <th>Disparo</th>
                                        <th>Estado</th>
                                        <th>Atendida por</th>
                                        <th>Fecha atención</th>
                                        <th class="th-ultimo">Detalles</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="11" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <div class="contenedor-paginacion-tabla">
                        <button type="button" id="btn-anterior-historial-alertas" class="btn-pagina-tabla">
                            <i class="fa-solid fa-angle-left"></i> Anterior
                        </button>

                        <span id="texto-pagina-historial-alertas" class="texto-pagina-tabla">
                            Página 1 de 1
                        </span>

                        <button type="button" id="btn-siguiente-historial-alertas" class="btn-pagina-tabla">
                            Siguiente <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>

                    <div class="contenedor-resumen-tabla">
                        <p id="info-paginacion-historial-alertas">
                            Mostrando 0 a 0 de 0 alertas
                        </p>
                    </div>

                </div>

            </div>
        </div>



</div>


    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/alertas/funcion_tabla_historial_alertas.js') }}"></script>
@endpush