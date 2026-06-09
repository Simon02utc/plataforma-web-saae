<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Rol y permisos | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Personal, roles y permisos</h1>
        </div>


        <!-- TABLA DE ROLES -->
        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Listado de roles</span></h2>

            <p class="texto-contenedor">Se muestra una tabla de los roles disponibles.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-registro-tabla con-texto btn-abrir-modal-formulario-registro" data-modal-formulario-registro="modal-formulario-crear-rol">
                            <i class="fa-solid fa-plus"></i> Crear roles
                        </button>

                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-roles">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-roles" class="input-buscar-tabla"
                                placeholder="Buscar rol por ID, clave o nombre">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-roles">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-roles"
                                data-url-tabla-listado-roles="{{ route('grup_admin.grup_personal_acceso.name_listado_roles') }}"
                                data-url-tabla-ver-permisos-rol="{{ route('grup_admin.grup_personal_acceso.name_ver_permisos_rol', ['id' => '__ID__']) }}"
                                data-url-tabla-ver-rol="{{ route('grup_admin.grup_personal_acceso.name_ver_rol', ['id' => '__ID__']) }}"
                                data-url-tabla-editar-rol="{{ route('grup_admin.grup_personal_acceso.name_editar_rol', ['id' => '__ID__']) }}"
                                data-url-tabla-eliminar-rol="{{ route('grup_admin.grup_personal_acceso.name_eliminar_rol', ['id' => '__ID__']) }}"
                                data-url-tabla-exportar-roles-excel="{{ route('grup_admin.grup_personal_acceso.name_exportar_roles_excel') }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Clave</th>
                                        <th>Nombre</th>
                                        <th>Descripcion</th>
                                        <th>Permisos asignados</th>
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

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla con-texto" id="btn-exportar-roles-excel">
                            <span><i class="fa-solid fa-file-excel"></i> Exportar Excel</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Exportando</span>
                        </button>
                    </div>

                </div>

            </div>
        </div>


        <!--Modal del formulario crear roles-->
        <div class="modal-formulario" id="modal-formulario-crear-rol">

            <div class="formulario-container" id="formulario-container">
                <span class="btn-cerrar-modal-fomulario" id="btn-cerrar-modal-fomulario">&times;</span>

                <div class="col col-1">
                    <!--contenido que ya no se utiliza-->
                </div>

                <div class="col col-2">

                    <!-- <div class="btn-box"> hay un JS para hacer los cambios de formulario animacion_cambio_formulario.js
                        <button class="btn btn-1" id="btn-formulario-1">Formu 1</button>
                        <button class="btn btn-2" id="btn-formulario-2">Formu 2</button>
                        <button class="btn btn-3" id="btn-formulario-3">Formu 3</button>
                    </div> -->

                    <form id="form-crear-rol" method="POST" action="{{ route('grup_admin.grup_personal_acceso.name_crear_roles_personal.crear_roles') }}" accept-charset="UTF-8">
                        @csrf
                        <div class="form-0">

                            <div class="form-title">
                                <span>Crear rol</span>
                            </div>

                            <div class="form-inputs">
                                <div class="scroll">

                                    <div class="input-box">
                                        <input class="input-field input-numero-cotrol" type="text" id="clave-rol-input" name="clave"  placeholder="Clave del rol (usar _ en espacios, ejem: director_tesis)" pattern="^[a-z]+(?:_[a-z]+)*$" title="Solo letras minúsculas y guion bajo '_' como separador. Ejemplo: director_tesis" autocomplete="off" autocapitalize="none" required>
                                        <i class="ri-key-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="nombre-rol-input" name="nombre" placeholder="Nombre del rol" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: Director Tesis" autocomplete="off" autocapitalize="words" required>
                                        <i class="ri-font-family icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <textarea class="input-field" id="descripcion-rol-input" name="descripcion" placeholder="Descripción del rol" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" autocomplete="off"></textarea>
                                        <i class="ri-text-block icon"></i>
                                    </div>

                                    <div class="separador-formulario"></div>

                                    <div  class="contenedor-elementos-extra-form">
                                        <p class="subtitulo-elementos-extra-form">Asignar permisos</p>

                                        @if($permisos->count() === 0)
                                            <p class="sin-elementos-extra-form">No hay permisos registrados. Primero crea los permisos.</p>
                                        @else

                                            <div id="permisos-container">
                                                @foreach($permisos as $permiso)
                                                    <label class="elementos-checkbox">
                                                        <input type="checkbox" name="permisos[]" value="{{ $permiso->id }}">
                                                        <span class="circulo-checkbox"></span>
                                                        <span class="texto-checkbox"><b>{{ $permiso->nombre }}</b> ({{ $permiso->clave }})</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                        @endif
                                    </div>

                                </div>

                                <!-- <div class="forgot-pass">
                                    <a id="btn-ayuda-registro" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                                </div> -->

                                <div class="botones-formulario">
                                    <button type="button" class="btn-cancelar-borrar btn-limpiar-formulario" data-limpiar-formulario="form-crear-rol">
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


        <!-- TABLA DE PERMISOS -->
        <div class="contenedor">

            <h2 class="titulo-contenedor"><span>Listado de permisos</span></h2>

            <p class="texto-contenedor">Se muestra la tabla de los permisos disponibles.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-registro-tabla con-texto btn-abrir-modal-formulario-registro" data-modal-formulario-registro="modal-formulario-crear-permiso">
                            <i class="fa-solid fa-plus"></i> Crear permisos
                        </button>
                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-permisos">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-permisos" class="input-buscar-tabla"
                                placeholder="Buscar permiso por ID, clave o nombre">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-permisos">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-permisos"
                                data-url-tabla-listado-permisos="{{ route('grup_admin.grup_personal_acceso.name_listado_permisos') }}"
                                data-url-tabla-ver-permiso="{{ route('grup_admin.grup_personal_acceso.name_ver_permiso', ['id' => '__ID__']) }}"
                                data-url-tabla-editar-permiso="{{ route('grup_admin.grup_personal_acceso.name_editar_permiso', ['id' => '__ID__']) }}"
                                data-url-tabla-eliminar-permiso="{{ route('grup_admin.grup_personal_acceso.name_eliminar_permiso', ['id' => '__ID__']) }}"
                                data-url-tabla-exportar-permisos-excel="{{ route('grup_admin.grup_personal_acceso.name_exportar_permisos_excel') }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Clave</th>
                                        <th>Nombre</th>
                                        <th>Descripcion</th>
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

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla con-texto" id="btn-exportar-permisos-excel">
                            <span><i class="fa-solid fa-file-excel"></i> Exportar Excel</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Exportando</span>
                        </button>
                    </div>

                </div>

            </div>
        </div>


        <!--Modal del formulario crear permisos-->
        <div class="modal-formulario" id="modal-formulario-crear-permiso">

            <div class="formulario-container" id="formulario-container">
                <span class="btn-cerrar-modal-fomulario" id="btn-cerrar-modal-fomulario">&times;</span>

                <div class="col col-1">
                    <!--contenido que ya no se utiliza-->
                </div>

                <div class="col col-2">

                    <!-- <div class="btn-box"> hay un JS para hacer los cambios de formulario animacion_cambio_formulario.js
                        <button class="btn btn-1" id="btn-formulario-1">Formu 1</button>
                        <button class="btn btn-2" id="btn-formulario-2">Formu 2</button>
                        <button class="btn btn-3" id="btn-formulario-3">Formu 3</button>
                    </div> -->

                    <form id="form-crear-permiso" method="POST" action="{{ route('grup_admin.grup_personal_acceso.name_crear_permisos_roles.crear_permisos') }}" accept-charset="UTF-8">
                        @csrf
                        <div class="form-0">

                            <div class="form-title">
                                <span>Crear permisos</span>
                            </div>

                            <div class="form-inputs">
                                <div class="scroll">

                                    <div class="input-box">
                                        <input class="input-field input-numero-cotrol" type="text" id="clave-permiso-input" name="clave"  placeholder="Clave del registro (usar . y _ , ejem: estudiantes.ver)" pattern="^[a-z]+(?:[._][a-z]+)*$" title="Solo letras minúsculas, puntos '.' y guion bajo '_' como separador. Ejemplo: (estudiantes.ver) o (auditoria_seguridad.ver)" autocomplete="off" autocapitalize="none" required>
                                        <i class="ri-key-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="nombre-permiso-input" name="nombre" placeholder="Nombre del permiso" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ej: Ver la seccion de estudiantes" autocomplete="off" autocapitalize="words" required>
                                        <i class="ri-font-family icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <textarea class="input-field" id="descripcion-permiso-input" name="descripcion" placeholder="Descripción del permiso" maxlength="500" title="Solo letras, espacios, y signos de puntuacion" autocomplete="off"></textarea>
                                        <i class="ri-text-block icon"></i>
                                    </div>

                                </div>


                                <!-- <div class="forgot-pass">
                                    <a id="btn-ayuda-registro" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                                </div> -->

                                <div class="botones-formulario">
                                    <button type="button" class="btn-cancelar-borrar btn-limpiar-formulario" data-limpiar-formulario="form-crear-permiso">
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

        
        <!-- INFORMACION DE LA ROLES Y PERMISOS -->
        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de Roles y permisos</span></h2>

            <div class="contenedores-informacion">
                <p class="texto"><b>Solo el actual administrador ({{ auth('personal')->user()?->nombre ?? '' }})</b> tendra disponible esta sección de [<i class="fa-solid fa-users"></i> Personal y acceso]. A continuación se da una breve información que es importante saber:</p>
                
                <div class="informacion-importante contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>           

                    <h3 class="titulo-contenedores-informacion texto-objetivo-leer">DATOS INICIALES:</h3>  
                    <p class="texto texto-objetivo-leer">
                        <b>1.</b> Cuando se implemento este proyecto, se ejecutaron migraciones y seeders, para el registro de datos iniciales. Algunos de esos datos fueron:
                    </p>
                    <ul class="texto texto-objetivo-leer">
                        <li>Los <b>permisos</b> (todos) asociados a los módulos y/o secciones de la plataforma. Ya que estos permisos los necesita la logica de acceso de la plataforma.</li>
                        <li>El primer <b>Rol</b> de la plataforma: <span class="palabra-resaltada">Administrador</span>. El cual tendra acceso a todo.</li>
                        <li>El primer usuario de <b>Personal</b> ({{ auth('personal')->user()?->nombre ?? '' }}), quien cuenta con el rol de Administrador.</li>
                    </ul>


                    <p class="texto texto-objetivo-leer">
                        <b>2.</b> Al ser el primero en registrarse, debera de actualizar su información (nombre y apellidos, correo electronico, telefono y contraseña) en: <a href="{{ route('grup_admin.grup_personal_acceso.name_gestion_administradores') }}">Actualizar mis datos de administrador</a>. Ya que por seguridad, es necesario que usted realice esos cambios.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>3.</b> Al registrar un personal, debera de enviarle sus credenciales de registro (en personal o fisico), ya que por seguridad, la plataforma no los enviara, pero si notificara sobre el registro por via correo electronico.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>4.</b> Cuando se requiera agregar mas módulos y/o secciones, es necesario solicitar del apoyo del desarrollador de la plataforma, o en dado caso de ya saber como es la lógica de acceso de la plataforma, solo se necesitaria agregar dichos permisos y registrarlos. Y por ultimo, tener precaución al querer borrar o editar estos permisos.
                    </p>
                </div>

                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>   
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">¿QUÉ ES UN PERSONAL, ROL Y PERMISO? ¿Y CUAL ES SU FUNCIÓN?<i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">
                            <p class="texto texto-objetivo-leer">
                                Para que la plataforma SAAE tenga un buen control, escalabilidad y flexibilidad en su acceso, se planteo que: Cuando existan más roles (aparte del Administrador), estos tenga acceso a ciertos módulos y/o secciones y funciones de la plataforma. Por ello, el  Administrador controlara esta sección.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">Personal:</span> Persona que tendra acceso a la plataforma, tras haber sido registrado con sus datos iniciales (nombre y apellidos, correo electronico, numero de telefono y contraseña). Este personal puede desempeñar distintos cargos dentro de la institución, como por ejemplo: Asesor, Jefe de Departamento, Docente, entre otros.
                            </p>
                            
                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">Rol:</span> Es el tipo de función o puesto que un personal podra desempeñar dentro de la plataforma. Por ello, un rol tendra ciertos permisos que indican a qué módulos y/o secciones y funcionalidades puede acceder el personal. Por ejemplo, un rol de <span class="palabra-resaltada">Asesor</span> podría tener acceso a ciertos módulos y/o secciones, con relacion a estudiantes.
                            </p>
                            
                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">Permiso:</span> Como su nombre lo dice, es la autorización para acceder o realizar determinadas acciones dentro de la plataforma. Los permisos se asignan a los roles. Por ejemplo, el rol de <b>Asesor</b> podría tener permisos para acceder a las secciones de [<i class="fa-solid fa-user-graduate"></i> Estudiantes], [<i class="fa-solid fa-file-circle-check"></i> Justificantes], entre otros módulos.
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
                            <h3 class="titulo-texto texto-objetivo-leer">Formulario de registro del personal, roles y permisos:</h3>
                            <p class="texto texto-objetivo-leer">
                                Al presionar el botón <i class="fa-solid fa-plus"></i>, se abrirá un modal con los formularios necesarios para realizar el registro que se requiera.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">1. Permisos:</span> Su clave debe escribirse en minúsculas, utilizando guion bajo (_) para los espacios y punto (.) para agregar una acción. Ejemplo: <span class="palabra-resaltada">modulo_importacion.ver</span>, donde "modulo_importacion" <i class="fa-solid fa-arrow-right"></i> indica el módulo, y ".ver" <i class="fa-solid fa-arrow-right"></i> indica la acción de poder ver o acceder al módulo. Su nombre debe escribirse de forma normal y, si es necesario, se puede agregar una descripción.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">2. Roles:</span> Su clave debe escribirse en minúsculas, utilizando guion bajo (_) para los espacios. Ejemplo: <span class="palabra-resaltada">director_tesis</span>. Su nombre debe escribirse de forma normal y, si es necesario, se puede agregar una descripción. Es muy importante que dicho rol tenga asignados sus permisos.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">3. Personal:</span> Sus datos deben ser correctos y legibles: nombre y apellidos, correo electrónico o institucional, número de teléfono, contraseña (mínimo 6 caracteres, 1 letra mayúscula, 1 número y 1 símbolo), y por lo menos 1 rol asignado, ya que sin rol no podrá realizar acciones dentro de la plataforma.
                            </p>

                            <h3 class="titulo-texto titulos-siguientes-texto texto-objetivo-leer">Listado de roles y permisos (tablas):</h3>
                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">1.</span> Botón <i class="fa-solid fa-rotate"></i> para refrescar, es decir, cargar nuevamente el contenido de la tabla.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">2.</span> Caja de búsqueda <i class="fa-solid fa-magnifying-glass"></i>, ya sea por ID, clave o nombre.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">3.</span> Botón para ver detalles o información extra <i class="fa-regular fa-eye"></i> (por el momento se encuentra en el listado de roles), el cual abrirá un modal.
                            </p>
                            
                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">4.</span> Botón para editar <i class="fa-solid fa-pen-to-square"></i>, ya sea un rol o permiso seleccionado, el cual abrirá un formulario para modificar sus datos. Como se mencionó anteriormente, los <span class="palabra-resaltada">permisos</span> son importantes para la lógica de acceso de la plataforma; por ello, se recomienda NO EDITARLOS, salvo en casos muy específicos y necesarios, como al agregar o editar secciones y módulos. En cambio, los <span class="palabra-resaltada">roles</span> sí se podrán editar, siempre y cuando se requieran cambios pertinentes, por ejemplo, por aumento del personal o nuevos roles.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">5.</span> Botón para eliminar <i class="fa-solid fa-trash"></i>, ya sea un rol o permiso seleccionado. Al presionarlo, se pedirá una confirmación de eliminación. Se recomienda NO ELIMINAR ningún permiso, salvo en casos muy específicos y necesarios, como al quitar o cambiar secciones y módulos.
                            </p>
                        </div>
                    </div>

                    <p class="texto texto-objetivo-leer">Entendida la infomación anterior, ya podras registrar personal, crear roles y permisos (si es necesario).</p>
                </div>

            </div>
        </div>


    </div>
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/personal_acceso/funcion_formulario_registrar_personal_crear_rol.js') }}"></script>
    <script src="{{ asset('js/personal/personal_acceso/funcion_listado_roles_permisos.js') }}"></script>
    <script src="{{ asset('js/personal/animacion_cambio_formulario.js') }}"></script>
    <script src="{{ asset('js/personal/ver_verificar_contrasena_formulario.js') }}"></script>
@endpush