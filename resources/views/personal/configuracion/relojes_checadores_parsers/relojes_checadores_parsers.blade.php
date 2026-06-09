<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Relojes y Parsers | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Relojes checadores y parsers</h1>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Listado de relojes checadores</span></h2>

            <p class="texto-contenedor">Se muestra una tabla de los relojes disponibles.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-registro-tabla con-texto btn-abrir-modal-formulario-registro" data-modal-formulario-registro="modal-formulario-registrar-reloje-checador">
                            <i class="fa-solid fa-plus"></i> Registrar relojes
                        </button>

                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-relojes">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" class="input-buscar-tabla" id="input-buscar-relojes" placeholder="Buscar reloj por ID o nombre">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-relojes">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-relojes"
                                data-url-tabla-listado-relojes="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_listado_relojes') }}"
                                data-url-tabla-ver-parsers-reloj="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_ver_parsers_reloj', ['id' => '__ID__']) }}"
                                data-url-tabla-ver-reloj="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_ver_reloj', ['id' => '__ID__']) }}"
                                data-url-tabla-editar-reloj="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_editar_reloj', ['id' => '__ID__']) }}"
                                data-url-tabla-eliminar-reloj="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_eliminar_reloj', ['id' => '__ID__']) }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Nombre</th>
                                        <th>Ubicación</th>
                                        <th>Parser asignado</th>
                                        <th>Estado</th>
                                        <th>Creado en</th>
                                        <th>Editado en</th>
                                        <th>Editar</th>
                                        <th class="th-ultimo">Eliminar</th>
                                    </tr>
                                </thead>

                                <tbody>
                                        <tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>

                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>


        <!--Modal del formulario registrar relojes checadores-->
        <div class="modal-formulario" id="modal-formulario-registrar-reloje-checador">

            <div class="formulario-container" id="formulario-container">
                <span class="btn-cerrar-modal-fomulario">&times;</span>

                <div class="col col-1">
                </div>


                <div class="col col-2">

                    <form id="form-registrar-reloj-checador" method="POST" action="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_registrar_reloj_checador') }}" accept-charset="UTF-8">
                        @csrf <!--Esto es obligatorio en POST/PUT/PATCH/DELETE. protege contra ataques CSRF-->
                        <div class="form-0">

                            <div class="form-title">
                                <span>Registrar reloj checador</span>
                            </div>

                            <div class="form-inputs">
                                <div class="scroll">

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="nombre-reloj-input" name="nombre" placeholder="Nombre del rol" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: Reloj checador On The Minute" autocomplete="off" autocapitalize="words" required>
                                        <i class="ri-font-family icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <textarea class="input-field" id="ubicacion-reloj-input" name="ubicacion" placeholder="Ubicacion" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" autocomplete="off"></textarea>
                                        <i class="ri-text-block icon"></i>
                                    </div>


                                    <div class="input-box" id="box-periodo">
                                        <select class="input-field" name="parser_id" id="parser-id" required>
                                            <option class="option-input-field-select" value="">-- Selecciona un parser --</option>
                                            @foreach($parsers as $parser)
                                                <option class="option-input-field-select" value="{{ $parser->id }}">
                                                    {{ $parser->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="input-box">
                                        <input type="hidden" name="activo" value="0">
                                        <label class="input-field switch-estado">
                                            <input type="checkbox" name="activo" value="1" checked>
                                            <span class="slider-switch-estado"></span>
                                            <span class="texto-switch-estado">Activar reloj</span>
                                        </label>
                                    </div>

                                </div>

                                <!-- <div class="forgot-pass">
                                    <a id="btn-ayuda-registro" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                                </div> -->

                                <div class="botones-formulario">
                                    <button type="button" class="btn-cancelar-borrar btn-limpiar-formulario" data-limpiar-formulario="form-registrar-reloj-checador">
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


        <!-- TABLA DE PARSERS -->
        <div class="contenedor">

            <h2 class="titulo-contenedor"><span>Listado de parsers</span></h2>

            <p class="texto-contenedor">Se muestra una tabla de los parsers disponibles.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-registro-tabla con-texto btn-abrir-modal-formulario-registro" data-modal-formulario-registro="modal-formulario-registrar-parser">
                            <i class="fa-solid fa-plus"></i> Registrar parser
                        </button>
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-parsers">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" class="input-buscar-tabla" id="input-buscar-parsers" placeholder="Buscar parser por ID, clave o nombre">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-parsers">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-parsers"
                                data-url-tabla-listado-parsers="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_listado_parsers') }}"
                                data-url-tabla-ver-parser="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_ver_parser', ['id' => '__ID__']) }}"
                                data-url-tabla-editar-parser="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_editar_parser', ['id' => '__ID__']) }}"
                                data-url-tabla-eliminar-parser="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_eliminar_parser', ['id' => '__ID__']) }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Clave</th>
                                        <th>Nombre</th>
                                        <th>Descripcion</th>
                                        <th>Estado</th>
                                        <th>Creado en</th>
                                        <th>Editado en</th>
                                        <th>Editar</th>
                                        <th class="th-ultimo">Eliminar</th>
                                    </tr>
                                </thead>

                                <tbody>
                                        <tr><td colspan="9" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>

                            </table>
                        </div>
                    </div>

                </div>

            </div>
        </div>


        <!--Modal del formulario registrar parsers-->
        <div class="modal-formulario" id="modal-formulario-registrar-parser">

            <div class="formulario-container" id="formulario-container">
                <span class="btn-cerrar-modal-fomulario">&times;</span>

                <div class="col col-1">
                </div>

                <div class="col col-2">

                    <form id="form-registrar-parser" method="POST" action="{{ route('grup_admin.grup_configuracion.grup_relojes_checadores_parsers.name_registrar_parser') }}" accept-charset="UTF-8">
                        @csrf
                        <div class="form-0">

                            <div class="form-title">
                                <span>Registrar parser</span>
                            </div>

                            <div class="form-inputs">
                                <div class="scroll">

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="clave-parser-input" name="clave"  placeholder="Clave del parser (usar _ en espacios, ejem: reloj_on_the_minute)" pattern="^[a-z]+(?:_[a-z]+)*$" title="Solo letras minúsculas y guion bajo '_' como separador. Ejemplo: reloj_on_the_minute" autocomplete="off" autocapitalize="none" required>
                                        <i class="ri-key-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="nombre-parser-input" name="nombre" placeholder="Nombre del parser" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: Parser del Reloj On The Minute" autocomplete="off" autocapitalize="words" required>
                                        <i class="ri-font-family icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <textarea class="input-field" id="descripcion-rol-input" name="descripcion" placeholder="Descripción del parser" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" autocomplete="off"></textarea>
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
                                    <button type="button" class="btn-cancelar-borrar btn-limpiar-formulario" data-limpiar-formulario="form-registrar-parser">
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


        <!-- INFORMACION DE RELOJES CHECADORES Y SUS PARSERS -->
        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de Relojes checadores y parsers</span></h2>

            <div class="contenedores-informacion">
                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Relojes checadores] podrá registrar los relojes checadores que tenga o llegue a tener la institución. Ya que la plataforma SAAE necesita de la información que ellos brindan. A continuación se da una breve información que es importante saber:</p>

                <div class="informacion-importante contenedor-informacion-leer">    
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>    

                    <h3 class="titulo-contenedores-informacion texto-objetivo-leer">REGLA PARA LOS RELOJES Y PARSERS:</h3>    
                    <p class="texto texto-objetivo-leer">
                        <b>1.</b> Cuando se implementó este proyecto, se ejecutaron migraciones y seeders para el registro de datos iniciales. Algunos de esos datos fueron:
                    </p>
                    <ul class="texto texto-objetivo-leer">
                        <li>El primer <b>Parser (analizador)</b> para el reloj "On the minute", que está actualmente.</li>
                        <li>El primer <b>reloj checador</b> (On the minute), que se utiliza actualmente. Dicho reloj brinda archivos con información (Periodo, Turnos y Asistencias) para alimentar la plataforma.</li>
                    </ul>

                    <p class="texto texto-objetivo-leer">
                        <b>2.</b> Los relojes checadores que se registren deberán tener obligatoriamente los siguientes datos: <span class="palabra-resaltada">Nombre</span>, <span class="palabra-resaltada">Ubicación</span>, <span class="palabra-resaltada">Activo</span> y un <span class="palabra-resaltada">Parser</span>.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>3.</b> Al querer registrar un reloj checador nuevo, deberá solicitar la ayuda del desarrollador para configurar y adaptar dicho reloj a la plataforma, debido a que cada reloj tiene un parser (analizador) que lo ayuda a funcionar dentro del módulo de importación de asistencia.
                    </p>
                </div>

                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">¿QUÉ ES EL PARSER DE UN RELOH CHECADOR Y CÓMO SE RELACIONA?<i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">
                            <p class="texto texto-objetivo-leer">
                                Los parsers (analizadores) de un reloj son herramientas o componentes que ayudan a transformar datos estructurados de un formato (como JSON, XML o CSV) a un formato manejable para la plataforma. Es decir, si un reloj tiene un parser asignado, este podrá extraer los datos de su archivo para posteriormente ser capturados en el módulo de importación de asistencia.
                            </p>
                        </div>
                    </div>
                </div>

                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">ACCIONES QUE SE PODRÁN REALIZAR. <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">
                            <h3 class="titulo-texto texto-objetivo-leer">Formulario para el registro de Relojes checadores y sus Parsers (analizadores):</h3>
                            <p class="texto texto-objetivo-leer">
                                Al presionar el botón <i class="fa-solid fa-plus"></i>, abrirá un modal con formularios para el registro que se requiera.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">1.- Relojes:</span><span class="palabra-resaltada"> Nombre</span> de su marca y/o modelo, o un nombre que sea legible, <span class="palabra-resaltada">Ubicación</span> de donde se encontrará dicho reloj, y estado <span class="palabra-resaltada">Activo</span> para ser utilizado en el módulo de importación, y un <span class="palabra-resaltada">Parser</span> que utilizará para extraer la información de su archivo.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">2.- Parsers (identificadores):</span><span class="palabra-resaltada"> Clave</span> de identificación para la clase del parser a utilizar, que también se debe asignar manualmente dentro de la estructura de la plataforma, <span class="palabra-resaltada">Nombre</span> legible para identificar el parser, <span class="palabra-resaltada">Descripción</span> sobre dicho parser (a qué reloj va destinado, su función y/o elementos).
                            </p>

                            <h3 class="titulo-texto titulos-siguientes-texto texto-objetivo-leer">Listado de relojes y parsers (tablas):</h3>
                            <p class="texto texto-objetivo-leer">
                                <b>1.</b> Botón para refrescar <i class="fa-solid fa-rotate"></i> (cargar nuevamente el contenido de la tabla).
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <b>2.</b> Caja de búsqueda <i class="fa-solid fa-magnifying-glass"></i>, ya sea por su ID, clave (solo el parser) o nombre.
                            </p>
                            
                            <p class="texto texto-objetivo-leer">
                                <b>3.</b> Botón para editar <i class="fa-solid fa-pen-to-square"></i>, ya sea un reloj o parser, el cual abrirá un formulario para modificar sus datos. Como se mencionó anteriormente, los <span class="palabra-resaltada">Parsers</span> ayudan a extraer los datos del archivo a importar; por ello, se recomienda NO EDITARLOS, solo en casos muy específicos y necesarios. En cambio, los <span class="palabra-resaltada">Relojes</span> se podrán editar, siempre y cuando se requieran cambios pertinentes.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <b>4.</b> Botón para eliminar <i class="fa-solid fa-trash"></i>, ya sea un reloj o parser seleccionado. Al presionarlo, se pedirá la confirmación de eliminación. Se recomienda NO ELIMINAR ningún reloj o parser, solo en casos muy específicos y necesarios.
                            </p>
                        </div>
                    </div>
                </div>

                <p class="texto">Entendida la infomación anterior, ya podras registrar Relojes checadores y sus Parsers (analizadores).</p>
            </div>
        </div>


    </div>
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/configuracion/relojes_checadores_parsers/funcion_formulario_registrar_relojes_checadores_parsers.js') }}"></script>
    <script src="{{ asset('js/personal/configuracion/relojes_checadores_parsers/funcion_listado_relojes_checadores_parsers.js') }}"></script>
    <script src="{{ asset('js/personal/animacion_cambio_formulario.js') }}"></script>
@endpush