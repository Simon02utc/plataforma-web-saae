<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tarjetas_resumen.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Alertas de asistencia | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Alertas de asistencia</h1>
        </div>

        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Tabla de alertas</span></h2>

            <p class="texto-contenedor">Se muestran las alertas de asistencia de los estudiantes que tienes asignados.</p>
            <p class="texto-contenedor">Cada falta genera una alerta normal y, al alcanzar 3 faltas acumuladas en el periodo, se genera una alerta especial una sola vez.</p>

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
                            <input type="text" id="input-buscar-alertas" class="input-buscar-tabla" placeholder="Buscar por No. control o Nombre">
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

                        <select class="select-buscar-tabla" id="filtro-tipo-alerta">
                            <option class="option-input-field-select" value="">-- Todos los tipos --</option>
                            <option class="option-input-field-select" value="FALTA_ACUMULADA">Falta acumulada</option>
                            <option class="option-input-field-select" value="SUSPENSION_BECA_ESCOLAR">Suspensión de beca escolar</option>
                        </select>

                        <select class="select-buscar-tabla" id="filtro-estado-alerta">
                            <option class="option-input-field-select" value="PENDIENTE" selected>PENDIENTE</option>
                            <option class="option-input-field-select" value="ATENDIDA">ATENDIDA</option>
                            <option class="option-input-field-select" value="CERRADA">CERRADA</option>
                        </select>

                        <select id="filtro-per-page-alertas" class="select-buscar-tabla">
                            <option value="20" selected>20</option>
                            <option value="50">50</option>
                            <option value="100">100</option>
                        </select>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table
                                class="tabla-contenido"
                                id="tabla-alertas-asistencia"
                                data-url-tabla-alertas="{{ route('grup_personal.grup_alertas.name_alertas_tabla') }}"
                                data-url-resumen-alertas="{{ route('grup_personal.grup_alertas.name_alertas_resumen') }}"
                                data-url-ver-alerta="{{ route('grup_personal.grup_alertas.name_ver_alerta', ['id' => '__ID__']) }}"
                                data-url-atender-alerta="{{ route('grup_personal.grup_alertas.name_atender_alerta', ['id' => '__ID__']) }}"
                                data-url-cerrar-alerta="{{ route('grup_personal.grup_alertas.name_cerrar_alerta', ['id' => '__ID__']) }}">
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
                                        <th>Estado de correo</th>
                                        <th>Detalles</th>
                                        <th class="th-ultimo">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="11" class="td-estado-tabla">Cargando contenido…</td></tr>
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


                <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de Alertas</span></h2>


            <div class="contenedores-informacion">
                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Alertas] podrá monitorear e identificar automáticamente situaciones importantes relacionadas con la asistencia de los estudiantes asignados a el.:</p>
                
                <div class="informacion-importante contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h3 class="titulo-contenedores-informacion texto-objetivo-leer">SOBRE LAS ALERTAS:</h3> 
                    <p class="texto texto-objetivo-leer">
                        <b>1.</b> Al realizar importaciones de asistencia se analiza consecutivamente la dicha asistencia de los estudiantes y genera alertas cuando detecta faltas.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>2.</b> Cada falta genera una alerta de seguimiento.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>3.</b> El botón de <span class="palabra-resaltada"> Y cuando un estudiante acumula 3 faltas en un periodo, se genera una alerta especial que indica una situación crítica.
                    </p>

                    <p class="titulo-contenedores-informacion texto-objetivo-leer"><b>TIPOS DE ALERTAS:</b></p>
                    <ul class="texto texto-objetivo-leer">
                        <li><strong>Alerta de falta acumulada:</strong> Se genera por cada falta registrada..</li>
                        <li><strong>Alerta especial (suspensión de beca):</strong> Se genera cuando el estudiante alcanza 3 faltas en el periodo..</li>
                    </ul>

                    <p class="titulo-contenedores-informacion texto-objetivo-leer"><b>ACCIONES DISPONIBLES:</b></p>
                    <ul class="texto texto-objetivo-leer">
                        <li><strong>Ver detalles:</strong> Consultar información completa.</li>
                        <li><strong>Atender:</strong> Indicar que la alerta ya fue revisada.</li>
                        <li><strong>Cerrar</strong> Finalizar el seguimiento de la alerta.</li>
                    </ul>

                    <p class="titulo-contenedores-informacion texto-objetivo-leer"><b>CONSIDERACIONES IMPORTANTES:</b></p>
                    <ul class="texto texto-objetivo-leer">
                        <li>Solo puedes ver alertas de estudiantes que tienes asignados.</li>
                        <li>Las alertas no se eliminan, solo cambian de estado</li>
                        <li>Las alertas especiales solo se generan una vez por periodo</li>
                    </ul>

                    <p class="texto texto-objetivo-leer">
                        <b>Importante:</b> Esta tabla funciona como una vista de monitoreo rápido.
                    </p>
                </div>
    
            </div>
        </div>

    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/alertas/funcion_tabla_alertas.js') }}"></script>
@endpush