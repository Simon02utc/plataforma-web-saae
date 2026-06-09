<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tarjetas_resumen.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Historial / consulta de asistencia | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')


    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Historial de asistencia</h1>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Tabla de historial de asistencia</span></h2>

            <p class="texto-contenedor">Consulta el historial de asistencia de tus estudiantes asignados.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tarjetas-resumen">
                    <div class="tarjeta-resumen-asistencia azul">
                        <p class="titulo-tarjeta-resumen">Total registros</p>
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
                        <p class="titulo-tarjeta-resumen">Faltas justificadas</p>
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

                    <div class="contenedor-filtros-tabla">
                        <div class="contenedor-buscador-informacion">
                            <input  type="text" id="input-buscar-estudiante-historial-asistencia" class="select-buscar-tabla filtro-input-field-select" placeholder="Buscar por No. de control o Nombre" autocomplete="off">

                            <input type="hidden" id="filtro-estudiante-historial-asistencia" value="">

                            <div id="resultados-busqueda-estudiante-historial" class="contenedor-resultados-busqueda" style="display: none;"></div>
                        </div>

                        <select class="select-buscar-tabla" id="filtro-periodo-historial-asistencia">
                            <option class="option-input-field-select" value="">-- Selecciona un periodo --</option>
                            @foreach($periodos as $periodo)
                                <option class="option-input-field-select" value="{{ $periodo->id }}">
                                    {{ $periodo->nombre }}
                                </option>
                            @endforeach
                        </select>

                        <button type="button" class=" btn-accion-filtro buscar-contenido-filtros" id="btn-consultar-historial-asistencia">
                            <i class="fa-solid fa-magnifying-glass"></i>
                            <span class="spinner-tabla"></span>
                        </button>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-historial-asistencia"
                                data-url-listado-estudiantes-asignados="{{ route('grup_personal.grup_asistencia_estudiantes.name_listado_estudiantes_asignados_tabla_historial_asistencia') }}"
                                data-url-tabla-detalle-historial-asistencia-estudiante="{{ route('grup_personal.grup_asistencia_estudiantes.name_detalle_tabla_historial_asistencia_estudiante', ['id' => '__ID__']) }}" 
                                data-url-exportar-historial-asistencia-excel="{{ route('grup_personal.grup_asistencia_estudiantes.name_exportar_historial_asistencia_excel') }}">
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
                                    <tr><td colspan="6" class="td-estado-tabla">Selecciona un estudiante y un periodo.</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>


                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla con-texto" id="btn-exportar-historial-asistencia-excel">
                            <span><i class="fa-solid fa-file-excel"></i> Exportar Excel</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Exportando</span>
                        </button>
                    </div>

                </div>

            </div>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información del Historial de asistencia</span></h2>

            <div class="contenedores-informacion">
                <p class="texto">El personal podra consultar el historial detallado de asistencia de los estudiantes que tiene asignados. A continuación se presenta información importante para su correcto uso:</p>
                
                <div class="informacion-importante contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h3 class="titulo-contenedores-informacion texto-objetivo-leer">SOBRE EL HISTORIAL DE ASISTENCIA:</h3> 

                    <p class="texto texto-objetivo-leer">
                        <b>1.</b> Esta sección permite consultar el historial completo de asistencia de un estudiante dentro de un periodo específico previamente seleccionado.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>2.</b> Para visualizar la información, es necesario seleccionar un <span class="palabra-resaltada">estudiante</span> y un <span class="palabra-resaltada">periodo</span>. Sin estos dos elementos, no se mostrará ningún resultado en la tabla.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>3.</b> La tabla muestra el detalle diario de asistencia, incluyendo fecha, estatus, primera entrada, última salida y número de marcaciones registradas por el sistema.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>4.</b> Los indicadores superiores presentan un resumen del periodo seleccionado, permitiendo identificar rápidamente el total de registros, asistencias, faltas y el porcentaje de asistencia del estudiante.
                    </p>

                    <p class="titulo-contenedores-informacion texto-objetivo-leer"><b>CONSIDERACIONES IMPORTANTES:</b></p>
                    <ul class="texto texto-objetivo-leer">
                        <li>El historial mostrado depende directamente de los datos previamente importados y procesados en la plataforma.</li>
                        <li>Los horarios (entrada y salida) se muestran conforme fueron registrados en el sistema de control de asistencia.</li>
                        <li>El estatus <strong>PRESENTE</strong> indica asistencia registrada correctamente.</li>
                        <li>El estatus <strong>FALTA</strong> indica que el estudiante no registró asistencia en un día esperado.</li>
                        <li>El estatus <strong>NO APLICA</strong> indica que el registro no se considera dentro del control de asistencia para ese día.</li>
                        <li>Si no existen registros para el periodo seleccionado, la tabla mostrará un mensaje indicando que no hay información disponible.</li>
                    </ul>

                    <p class="texto texto-objetivo-leer">
                        <b>Importante:</b> Esta sección está diseñada para consulta detallada. Para un monitoreo rapido del perido mas reciente, redirigite a Asistencia recientes.
                    </p>
                </div>

            </div>
        </div>

    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/asistencia_estudiantes/funcion_tabla_historial_consultas_asistencia.js') }}"></script>
@endpush