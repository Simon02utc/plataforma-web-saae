<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/modulo_importacion/estilos_modulo_importacion.css') }}">
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Historial del Modulo de importación | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Historial del modulo de importación</h1>
        </div>

        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de esta sub sección</span></h2>

            <div class="contenedores-informacion">

                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Historial del modulo de importaciones] podrá ver los registros de todas las importaciones de Asistencia y de Datos escolares.</p> 

                <p class="texto">Si deseas registrar mas Relojes checadores o Fuentes de datos, solicita apoyo del personal Administrativo de la plataforma.</p>
            </div>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Importaciones de asistencia</span></h2>

            <div class="contenedor-importacion">
                <p class="texto-contenedor">Se muestra el historial completo de las importaciones de asistencia.</p>

                <div class="contenedores-informacion">

                    <div class="contenedor-tablas-contenido">

                        <div class="contenedor-botones-accion-tabla">
                            <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-historial-importaciones-asistencia">
                                <span><i class="fa-solid fa-rotate"></i></span>
                                <span class="spinner-tabla"></span>
                            </button>

                            <div class="contenedor-buscador-tabla">
                                <input type="text" id="input-buscar-tabla-importacion-asistencia" class="input-buscar-tabla"
                                    placeholder="Buscar por ID, archivo, reloj, tipo o estado">
                                <button type="button" class="btn-buscar-tabla" id="btn-buscar-tabla-importacion-asistencia">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <span class="spinner-tabla"></span>
                                </button>
                            </div>
                        </div>

                        <div class="contenedor-filtros-tabla">
                            <select id="filtro-per-page-historial-importaciones-asistencia" class="select-buscar-tabla">
                                <option value="20">20</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                            </select>
                        </div>
                        
                        <div class="tabla-scroll">
                            <div class="contenedor-tabla">
                                <table class="tabla-contenido" id="tabla-historial-importaciones-asistencia"
                                    data-url-tabla-listado-historial-importaciones-asistencia="{{ route('grup_personal.grup_auditoria_seguridad.name_historial_importaciones_asistencia') }}"
                                    data-url-tabla-ver-detalles-importacion-asistencia="{{ route('grup_personal.grup_auditoria_seguridad.name_ver_detalles_importacion_asistencia', ['id' => '__ID__']) }}"
                                    data-url-tabla-descargar-archivo-importacion-asistencia="{{ route('grup_personal.grup_auditoria_seguridad.name_descargar_archivo_importacion_asistencia', ['id' => '__ID__']) }}">
                                    <thead>
                                        <tr>
                                            <th class="th-primero">ID</th>
                                            <th>Archivo</th>
                                            <th>Periodo</th>
                                            <th>Reloj</th>
                                            <th>Tipo de importación</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Detalles</th>
                                            <th class="th-ultimo">Descargar</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                            <tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>
                                    </tbody>

                                </table>
                            </div>
                        </div>

                        <div class="contenedor-paginacion-tabla">
                            <button type="button" id="btn-anterior-historial-importaciones-asistencia" class="btn-pagina-tabla">
                                <i class="fa-solid fa-angle-left"></i> Anterior
                            </button>

                            <span id="texto-pagina-historial-importaciones-asistencia" class="texto-pagina-tabla">
                                Página 1 de 1
                            </span>

                            <button type="button" id="btn-siguiente-historial-importaciones-asistencia" class="btn-pagina-tabla">
                                Siguiente <i class="fa-solid fa-angle-right"></i>
                            </button>
                        </div>

                        <div class="contenedor-resumen-tabla">
                            <p id="info-paginacion-historial-importaciones-asistencia">
                                Mostrando 0 a 0 de 0 importaciones
                            </p>
                        </div>

                    </div>

            </div>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Importaciones de datos escolares</span></h2>

            <div class="contenedor-importacion">
                <p class="texto-contenedor">Se muestra el historial completo de las importaciones de datos escolares.</p>

                <div class="contenedores-informacion">

                    <div class="contenedor-tablas-contenido">

                        <div class="contenedor-botones-accion-tabla">
                            <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-historial-importaciones-datos-escolares">
                                <span><i class="fa-solid fa-rotate"></i></span>
                                <span class="spinner-tabla"></span>
                            </button>

                            <div class="contenedor-buscador-tabla">
                                <input type="text" id="input-buscar-tabla-importacion-datos-escolares" class="input-buscar-tabla"
                                    placeholder="Buscar por ID, archivo, fuente, tipo o estado">
                                <button type="button" class="btn-buscar-tabla" id="btn-buscar-tabla-importacion-datos-escolares">
                                    <i class="fa-solid fa-magnifying-glass"></i>
                                    <span class="spinner-tabla"></span>
                                </button>
                            </div>
                        </div>
                        
                        <div class="contenedor-filtros-tabla">
                            <select id="filtro-per-page-historial-importaciones-datos-escolares" class="select-buscar-tabla">
                                <option value="20">20</option>
                                <option value="50" selected>50</option>
                                <option value="100">100</option>
                                <option value="200">200</option>
                            </select>
                        </div>
                        
                        <div class="tabla-scroll">
                            <div class="contenedor-tabla">
                                <table class="tabla-contenido" id="tabla-historial-importaciones-datos-escolares"
                                    data-url-tabla-listado-historial-importaciones-datos-escolares="{{ route('grup_personal.grup_auditoria_seguridad.name_historial_importaciones_datos_escolares') }}"
                                    data-url-tabla-ver-detalles-importacion-datos-escolares="{{ route('grup_personal.grup_auditoria_seguridad.name_ver_detalles_importacion_datos_escolares', ['id' => '__ID__']) }}"
                                    data-url-tabla-descargar-archivo-importacion-datos-escolares="{{ route('grup_personal.grup_auditoria_seguridad.name_descargar_archivo_importacion_datos_escolares', ['id' => '__ID__']) }}">
                                    <thead>
                                        <tr>
                                            <th class="th-primero">ID</th>
                                            <th>Archivo</th>
                                            <th>Fuente</th>
                                            <th>Tipo de importación</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Detalles</th>
                                            <th class="th-ultimo">Descargar</th>
                                        </tr>
                                    </thead>

                                    <tbody>
                                            <tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>
                                    </tbody>

                                </table>
                            </div>
                        </div>

                        <div class="contenedor-paginacion-tabla">
                            <button type="button" id="btn-anterior-historial-importaciones-datos-escolares" class="btn-pagina-tabla">
                                <i class="fa-solid fa-angle-left"></i> Anterior
                            </button>

                            <span id="texto-pagina-historial-importaciones-datos-escolares" class="texto-pagina-tabla">
                                Página 1 de 1
                            </span>

                            <button type="button" id="btn-siguiente-historial-importaciones-datos-escolares" class="btn-pagina-tabla">
                                Siguiente <i class="fa-solid fa-angle-right"></i>
                            </button>
                        </div>

                        <div class="contenedor-resumen-tabla">
                            <p id="info-paginacion-historial-importaciones-datos-escolares">
                                Mostrando 0 a 0 de 0 importaciones
                            </p>
                        </div>


                    </div>

            </div>
        </div>
    

    

    </div>

@endsection 
<!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
<script src="{{ asset('js/personal/auditoria_seguridad/funcion_historial_modulo_importacion_asistencia_datos_escolares.js') }}"></script>
@endpush