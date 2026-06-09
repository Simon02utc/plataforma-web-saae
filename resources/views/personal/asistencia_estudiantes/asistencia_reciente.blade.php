<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

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
            <h2 class="titulo-contenedor"><span>Tabla asistencias</span></h2>

            <p class="texto-contenedor">Se muestra la ultima asistencia de los estudiantes que tiene asignados, acorde al periodo mas reciente.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tarjetas-resumen">
                    <div class="tarjeta-resumen-asistencia azul">
                        <p class="titulo-tarjeta-resumen">Total asignados</p>
                        <p class="valor-tarjeta-resumen" id="resumen-total-asignados">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">Total presentes</p>
                        <p class="valor-tarjeta-resumen" id="resumen-presentes">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia rojo">
                        <p class="titulo-tarjeta-resumen">Total faltas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-faltas">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia morado">
                        <p class="titulo-tarjeta-resumen">Total faltas jutificadas</p>
                        <p class="valor-tarjeta-resumen" id="resumen-faltas-justificadas">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia gris">
                        <p class="titulo-tarjeta-resumen">Total aplica</p>
                        <p class="valor-tarjeta-resumen" id="resumen-no-aplica">0</p>
                    </div>

                    <div class="tarjeta-resumen-asistencia verde">
                        <p class="titulo-tarjeta-resumen">% Toal de asistencia</p>
                        <p class="valor-tarjeta-resumen" id="resumen-porcentaje">0%</p>
                    </div>
                </div>

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-asistencia-reciente">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-asistencia-reciente" class="input-buscar-tabla"
                                placeholder="Buscar por No. control o Nombre">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-asistencia-reciente">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    <div class="contenedor-filtros-tabla">
                        <select class="select-buscar-tabla select-principal" id="filtro-periodo-asistencia-reciente">
                            <!--<option class="option-input-field-select" value="">-- Selecciona un periodo --</option>-->
                            @foreach($periodos as $periodo)
                                <option class="option-input-field-select" value="{{ $periodo->id }}">
                                    {{ $periodo->nombre }}
                                </option>
                            @endforeach
                            <option class="option-input-field-select" value="">-- Selecciona un periodo --</option>
                        </select>

                        <select class="select-buscar-tabla" id="filtro-estatus-asistencia-reciente">
                            <option class="option-input-field-select" value="">Todos los estados de asistencia</option>
                            <option class="option-input-field-select" value="PRESENTE">PRESENTE</option>
                            <option class="option-input-field-select" value="FALTA">FALTA</option>
                            <option class="option-input-field-select" value="JUSTIFICADA">FALTA JUSTIFICADA</option>
                            <option class="option-input-field-select" value="NO_APLICA">NO APLICA</option>
                            <option class="option-input-field-select" value="SIN_REGISTRO">SIN REGISTRO</option>
                        </select>

                        <select id="filtro-area-asistencia-reciente" class="select-buscar-tabla">
                            <option value="">Todas las especialidades</option>
                            @foreach ($areasEspecialidad as $area)
                                <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                            @endforeach
                        </select>

                        <select id="filtro-estatus-escolar-asistencia-reciente" class="select-buscar-tabla">
                            <option value="">Todas los estatus escolares</option>
                            @foreach ($estatusEscolar as $estus)
                                <option value="{{ $estus->id }}">{{ $estus->nombre }}</option>
                            @endforeach
                        </select>

                        <select id="filtro-per-page-asistencia-reciente" class="select-buscar-tabla">
                            <option value="20">20</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                        </select>
                    </div>

                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-asistencia-reciente"
                                data-url-tabla-asistencia-reciente="{{ route('grup_personal.grup_asistencia_estudiantes.name_tabla_asistencia_reciente') }}"
                                data-url-resumen-asistencia-reciente="{{ route('grup_personal.grup_asistencia_estudiantes.name_resumen_asistencia_reciente') }}"
                                data-url-detalle-asistencia-estudiante="{{ route('grup_personal.grup_asistencia_estudiantes.name_detalle_asistencia_estudiante', ['id' => '__ID__']) }}"
                                data-url-exportar-asistencia-reciente-excel="{{ route('grup_personal.grup_asistencia_estudiantes.name_exportar_asistencia_reciente_excel') }}"
                                data-url-exportar-historial-completo-asistencia-excel="{{ route('grup_personal.grup_asistencia_estudiantes.name_exportar_historial_completo_asistencia_excel') }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>No. Control</th>
                                        <th>Nombre</th>
                                        <th>Fecha</th>
                                        <th>Estatus</th>
                                        <th>Fuente</th>
                                        <th>Primera entrada</th>
                                        <th>Última salida</th>
                                        <th>Marcaciones</th>
                                        <th class="th-ultimo">Todas las asistencias</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr><td colspan="10" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                
                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla con-texto" id="btn-exportar-asistencia-reciente-excel">
                            <span><i class="fa-solid fa-file-excel"></i> Exportar asistencia reciente</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Exportando asistencia reciente</span>
                        </button>

                        <button type="button" class="btn-accion-tabla con-texto" id="btn-exportar-historial-completo-asistencia-excel">
                            <span><i class="fa-solid fa-file-excel"></i> Exportar historial completo</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Exportando historial completo</span>
                        </button>
                    </div>


                    <div class="contenedor-paginacion-tabla">
                        <button type="button" id="btn-anterior-asistencia-reciente" class="btn-pagina-tabla">
                            <i class="fa-solid fa-angle-left"></i> Anterior
                        </button>

                        <span id="texto-pagina-asistencia-reciente" class="texto-pagina-tabla">
                            Página 1 de 1
                        </span>

                        <button type="button" id="btn-siguiente-asistencia-reciente" class="btn-pagina-tabla">
                            Siguiente <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>

                    <div class="contenedor-resumen-tabla">
                        <p id="info-paginacion-asistencia-reciente">
                            Mostrando 0 a 0 de 0 estudiantes
                        </p>
                    </div>


                </div>

            </div>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de Asistencias recientes</span></h2>

            <div class="contenedores-informacion">
                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Asistencias recientes] podrá monitorear la asistencia diaria de sus alumnos asignados. A continuación se da una breve información que es importante saber:</p>
                
                <div class="informacion-importante contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h3 class="titulo-contenedores-informacion texto-objetivo-leer">SOBRE LA ASISTENCIA DEL ESTUDIANTE:</h3> 
                    <p class="texto texto-objetivo-leer">
                        <b>1.</b> En esta sección se muestra la asistencia reciente de los estudiantes que tienes asignados dentro del periodo seleccionado. Su objetivo es ofrecer una vista rápida del estado actual de asistencia de cada estudiante.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>2.</b> La tabla principal presenta el registro más reciente de asistencia encontrado para cada estudiante, incluyendo su estatus, primera entrada, última salida y cantidad de marcaciones registradas.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>3.</b> El botón de <span class="palabra-resaltada">ver detalle</span> permite consultar el historial de asistencia del estudiante dentro del periodo seleccionado, junto con métricas generales como presentes, faltas y porcentaje de asistencia.
                    </p>

                    <p class="titulo-contenedores-informacion texto-objetivo-leer"><b>CONSIDERACIONES IMPORTANTES:</b></p>
                    <ul class="texto texto-objetivo-leer">
                        <li>El filtro de periodo es necesario para consultar correctamente la asistencia mostrada en la tabla y en el detalle del estudiante.</li>
                        <li>El estatus <strong>PRESENTE</strong> indica que el estudiante tiene asistencia registrada en la fecha mostrada.</li>
                        <li>El estatus <strong>FALTA</strong> indica que el estudiante tenía asistencia esperada, pero no registró entradas o salidas válidas.</li>
                        <li>El estatus <strong>NO APLICA</strong> indica que ese registro no se considera dentro del conteo normal de asistencia.</li>
                        <li>El estatus <strong>SIN REGISTRO</strong> indica que no se encontró información de asistencia para ese estudiante en la fecha reciente tomada por la tabla.</li>
                        <li>Los datos mostrados dependen de los registros de asistencia previamente importados y procesados dentro de la plataforma.</li>
                    </ul>

                    <p class="texto texto-objetivo-leer">
                        <b>Importante:</b> Esta tabla funciona como una vista de monitoreo rápido. Para revisar el comportamiento completo de las asistencias de un estudiante, utiliza la opción de <span class="palabra-resaltada">Mas asistencias</span>.
                    </p>
                </div>
    
            </div>
        </div>

    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/asistencia_estudiantes/funcion_tabla_asistencia_reciente.js') }}"></script>
@endpush