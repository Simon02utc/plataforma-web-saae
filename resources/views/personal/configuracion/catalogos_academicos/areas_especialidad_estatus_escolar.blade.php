<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Catálogos académicos | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Catálogos académicos</h1>
        </div>

        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Listado de áreas de especialidad</span></h2>

            <p class="texto-contenedor">Se muestra una tabla con las áreas de especialidad disponibles.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-registro-tabla con-texto btn-abrir-modal-formulario-registro" data-modal-formulario-registro="modal-formulario-registrar-area-especialidad">
                            <i class="fa-solid fa-plus"></i> Registrar área
                        </button>

                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-areas-especialidad">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" class="input-buscar-tabla" id="input-buscar-areas-especialidad" placeholder="Buscar por ID, Clave o Nombre">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-areas-especialidad">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-areas-especialidad"
                                data-url-tabla-listado-areas-especialidad="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_listado_areas_especialidad') }}"
                                data-url-tabla-ver-area-especialidad="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_ver_area_especialidad', ['id' => '__ID__']) }}"
                                data-url-tabla-editar-area-especialidad="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_editar_area_especialidad', ['id' => '__ID__']) }}"
                                data-url-tabla-eliminar-area-especialidad="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_eliminar_area_especialidad', ['id' => '__ID__']) }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Clave</th>
                                        <th>Nombre</th>
                                        <th>Estado</th>
                                        <th>Fechas de</th>
                                        <th>Editar</th>
                                        <th class="th-ultimo">Eliminar</th>
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


        <!--Modal del formulario registrar area de especialidad-->
        <div class="modal-formulario" id="modal-formulario-registrar-area-especialidad">

            <div class="formulario-container" id="formulario-container">

                <div class="botones-superiores-modal-formulario">
                    <span class="btn-cerrar-modal-fomulario" id="btn-cerrar-modal-fomulario"><i class="fa-solid fa-xmark"></i></span>
                </div>

                <div class="col col-1">
                </div>
                

                <div class="col col-2">

                    <form id="form-registrar-area-especialidad" method="POST" action="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_registrar_areas_especialidad') }}" accept-charset="UTF-8">
                        @csrf <!--Esto es obligatorio en POST/PUT/PATCH/DELETE. protege contra ataques CSRF-->
                        <div class="form-0">

                            <div class="form-title">
                                <span>Registrar área de especialidad</span>
                            </div>

                            <div class="form-inputs">
                                <div class="scroll">

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="clave-area-input" name="clave"  placeholder="Clave del área (usar _ en espacios, ej: inteligencia_artificial)" pattern="^[a-z]+(?:_[a-z]+)*$" title="Solo letras minúsculas y guion bajo '_' como separador. Ej: inteligencia_artificial" autocomplete="off" autocapitalize="none" required>
                                        <i class="ri-key-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="nombre-area-input" name="nombre" placeholder="Nombre del área" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: INTELIGENCIA ARTIFICIAL" autocomplete="off" autocapitalize="words" required>
                                        <i class="ri-font-family icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input type="hidden" name="activo" value="0">
                                        <label class="input-field switch-estado">
                                            <input type="checkbox" name="activo" value="1" checked>
                                            <span class="slider-switch-estado"></span>
                                            <span class="texto-switch-estado">Activar área</span>
                                        </label>
                                    </div>

                                </div>

                                <!-- <div class="forgot-pass">
                                    <a id="btn-ayuda-registro" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                                </div> -->

                                <div class="botones-formulario">
                                    <button type="button" class="btn-cancelar-borrar btn-limpiar-formulario" data-limpiar-formulario="form-registrar-area-especialidad">
                                        <span>Cancelar</span>
                                    </button>

                                    <button type="submit" class="btn-guardar-enviar">
                                        <span>Enviar</span>
                                        <span class="spinner"></span>
                                        <span class="texto-spinner">Espera</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>

                </div>
            </div>

        </div>


        <div class="contenedor">

            <h2 class="titulo-contenedor"><span>Listado de estatus escolares</span></h2>

            <p class="texto-contenedor">Se muestra una tabla con los estatus escolares disponibles.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-registro-tabla con-texto btn-abrir-modal-formulario-registro" data-modal-formulario-registro="modal-formulario-registrar-estatus-escolar">
                            <i class="fa-solid fa-plus"></i> Registrar estatus
                        </button>

                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-estatus-escolares">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" class="input-buscar-tabla" id="input-buscar-estatus-escolares" placeholder="Buscar por ID, Clave o Nombre">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-estatus-escolares">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-estatus-escolares"
                                data-url-tabla-listado-estatus-escolares="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_listado_estatus_escolares') }}"
                                data-url-tabla-ver-estatus-escolar="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_ver_estatus_escolar', ['id' => '__ID__']) }}"
                                data-url-tabla-editar-estatus-escolar="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_editar_estatus_escolar', ['id' => '__ID__']) }}"
                                data-url-tabla-eliminar-estatus-escolar="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_eliminar_estatus_escolar', ['id' => '__ID__']) }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Clave</th>
                                        <th>Nombre</th>
                                        <th>Descripcion</th>
                                        <th>Estado</th>
                                        <th>Fechas de</th>
                                        <th>Editar</th>
                                        <th class="th-ultimo">Eliminar</th>
                                    </tr>
                                </thead>

                                <tbody>
                                        <tr><td colspan="8" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>

                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>


        
        <!--Modal del formulario registrar estatus escolar-->
        <div class="modal-formulario" id="modal-formulario-registrar-estatus-escolar">

            <div class="formulario-container" id="formulario-container">
                <span class="btn-cerrar-modal-fomulario">&times;</span>

                <div class="col col-1">
                </div>


                <div class="col col-2">

                    <form id="form-registrar-estatus-escolar" method="POST" action="{{ route('grup_admin.grup_configuracion.grup_catalogos_academicos.name_registrar_estatus_escolares') }}" accept-charset="UTF-8">
                        @csrf
                        <div class="form-0">

                            <div class="form-title">
                                <span>Registrar estatus escolar</span>
                            </div>

                            <div class="form-inputs">
                                <div class="scroll">

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="clave-estatus-input" name="clave"  placeholder="Clave del estatus (usar _ en espacios, ej: baja_temporal)" pattern="^[a-z]+(?:_[a-z]+)*$" title="Solo letras minúsculas y guion bajo '_' como separador. Ej: baja_temporal" autocomplete="off" autocapitalize="none" required>
                                        <i class="ri-key-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="nombre-estatus-input" name="nombre" placeholder="Nombre del estatus" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: BAJA TEMPORAL" autocomplete="off" autocapitalize="words" required>
                                        <i class="ri-font-family icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <textarea class="input-field" id="descripcion-estatus-input" name="descripcion" placeholder="Descripción del estatus" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" autocomplete="off"></textarea>
                                        <i class="ri-text-block icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input type="hidden" name="activo" value="0">
                                        <label class="input-field switch-estado">
                                            <input type="checkbox" name="activo" value="1" checked>
                                            <span class="slider-switch-estado"></span>
                                            <span  class="texto-switch-estado">Activar parser</span>
                                        </label>
                                    </div>

                                </div>

                                <!-- <div class="forgot-pass">
                                    <a id="btn-ayuda-registro" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                                </div> -->

                                <div class="botones-formulario">
                                    <button type="button" class="btn-cancelar-borrar btn-limpiar-formulario" data-limpiar-formulario="form-registrar-estatus-escolar">
                                        <span>Cancelar</span>
                                    </button>

                                    <button type="submit" class="btn-guardar-enviar">
                                        <span>Enviar</span>
                                        <span class="spinner"></span>
                                        <span class="texto-spinner">Espera</span>
                                    </button>
                                </div>
                            </div>

                        </div>
                    </form>

                </div>
            </div>

        </div>


        <!-- INFORMACION DE CATALOGOS ACADEMICOS -->
        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de Catalogos académicos</span></h2>

            <div class="contenedores-informacion">
                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Catálogos académicos] podra gestionar la complementación de valores e información de los estudiantes, para ser utilizados en otras secciones y/o modulos (importaciones, registros, filtros, etc.)</p>

                <div class="informacion-importante contenedor-informacion-leer">    
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>    

                    <h3 class="titulo-contenedores-informacion texto-objetivo-leer">INFORMACIÓN GENERAL SOBRE LOS CÁTALOGOS ACADÉMICOS:</h3>    
                    <p class="texto texto-objetivo-leer">
                        Los catálogos académicos son una parte fundamental y de apoyo para la plataforma, ya que permiten organizar y estandarizar la información escolar de los estudiantes. En esta sección se administran los valores oficiales que serán utilizados en distintos procesos del sistema, como el registro manual de estudiantes, la importación de datos escolares, la consulta de información y la aplicación de filtros en los listados.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        Actualmente, los catálogos académicos ya formalizados y en uso son las Áreas de especialidad y los Estatus escolares, por lo que su gestión debe realizarse con especial cuidado.
                    </p>

                </div>

                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">SOBRE LAS ÁREAS DE ESPECIALIDAD. <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">

                            <p class="texto texto-objetivo-leer">
                                Las áreas de especialidad representan la línea académica o campo de formación al que pertenece cada estudiante. Estas áreas permiten clasificar correctamente a los alumnos dentro de la plataforma y facilitan la organización de su información escolar.
                            </p>
                            
                            <p class="titulo-texto texto-objetivo-leer texto-objetivo-leer">
                                Las áreas actualmente formalizadas son:
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Cómputo Inteligente y Ciencia de Datos</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Tecnologías Inteligentes de Software</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Ingeniería de Software</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Inteligencia Artificial</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Sistemas Distribuidos</span>.
                                </li>
                            </ul>

                        </div>
                    </div>
                </div>

                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">SOBRE LOS ESTATUS ESCOLARES. <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">

                            <p class="texto texto-objetivo-leer">
                                Los estatus escolares permiten identificar la situación académica actual de cada estudiante dentro de la institución. Su función es reflejar de manera clara el estado en el que se encuentra el alumno en relación con su trayectoria escolar.
                            </p>
                            
                            <p class="titulo-texto texto-objetivo-leer texto-objetivo-leer">
                                Los estatus actualmente formalizados son:
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Inscrito</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">No inscrito</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Titulado</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Extemporáneo</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Baja temporal</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Baja</span>.
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">No asistió a clases</span>.
                                </li>
                            </ul>

                        </div>
                    </div>

                    <p class="texto texto-objetivo-leer">
                        Es importante que cualquier modificación sobre estos catálogos se realice con precaución, ya que estos registrados son importantes para los estudiantes ya existentes dentro de la plataforma. Un cambio no controlado podría afectar los registros y/o integridad de la información académica almacenada.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        Por ello, se recomienda que estos catálogos sean administrados únicamente por personal autorizado y que cualquier alta, edición o desactivación se haga respetando los criterios ya establecidos.
                    </p>

                </div>
            </div>
        </div>

    </div>
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/configuracion/catalogos_academicos/funcion_formulario_registrar_areas_especialidad_estatus_academicos.js') }}"></script>
    <script src="{{ asset('js/personal/configuracion/catalogos_academicos/funcion_listado_areas_especialidad_estatus_academicos.js') }}"></script>

    <script src="{{ asset('js/personal/animacion_cambio_formulario.js') }}"></script>
@endpush