<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Personal, rol y permisos | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Listado del personal</h1>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Listado del personal</span></h2>

            <p class="texto-contenedor">Se muestra las cuentas de todo el personal.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-registro-tabla con-texto btn-abrir-modal-formulario-registro" data-modal-formulario-registro="modal-formulario-registrar-personal">
                            <i class="fa-solid fa-plus"></i> Registrar personal
                        </button>

                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-personal">
                            <span><i class="fa-solid fa-rotate"></i></span>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-personal" class="input-buscar-tabla"
                                placeholder="Buscar por ID, Nombre o Correo">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-personal">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>
                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-personal"
                                data-url-table-listado-personal="{{ route('grup_admin.grup_personal_acceso.name_tabla_listado_personal') }}"
                                
                                data-url-table-ver-roles-personal="{{ route('grup_admin.grup_personal_acceso.name_ver_roles_personal', ['id' => '__ID__']) }}"
                                
                                data-url-tabla-ver-estudiantes-asignados-personal="{{ route('grup_admin.grup_personal_acceso.name_ver_estudiantes_asignados_personal', ['id' => '__ID__']) }}"
                                data-url-tabla-desactivar-asignacion-personal="{{ route('grup_admin.grup_personal_acceso.name_desactivar_asignacion_personal', ['id' => '__ID__']) }}"
                                data-url-tabla-reactivar-asignacion-personal="{{ route('grup_admin.grup_personal_acceso.name_reactivar_asignacion_personal', ['id' => '__ID__']) }}"
                                data-url-tabla-eliminar-asignacion-personal="{{ route('grup_admin.grup_personal_acceso.name_eliminar_asignacion_personal', ['id' => '__ID__']) }}"
                                
                                data-url-table-ver-personal="{{ route('grup_admin.grup_personal_acceso.name_ver_personal', ['id' => '__ID__']) }}"
                                data-url-table-editar-personal="{{ route('grup_admin.grup_personal_acceso.name_editar_personal', ['id' => '__ID__']) }}"
                                data-url-table-eliminar-personal="{{ route('grup_admin.grup_personal_acceso.name_eliminar_personal', ['id' => '__ID__']) }}"
                                
                                data-url-table-exportar-personal-excel="{{ route('grup_admin.grup_personal_acceso.name_exportar_personal_excel') }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>Nombre y apellidos</th>
                                        <th>Correo</th>
                                        <th>Telefono</th>
                                        <th>Roles asignados</th>
                                        <th>Estado</th>
                                        <th>Ultimo acceso</th>
                                        <th>Fechas de</th>
                                        <th>Acciones</th>
                                        <th class="th-ultimo">Eliminar</th>
                                    </tr>
                                </thead>

                                <tbody>
                                        <tr><td colspan="10" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>

                            </table>
                        </div>
                    </div>

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-accion-tabla con-texto" id="btn-exportar-personal-excel">
                            <span><i class="fa-solid fa-file-excel"></i> Exportar Excel</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Exportando</span>
                        </button>
                    </div>

                </div>

            </div>
        </div>


        <!--Modal del formulario crear permisos-->
        <div class="modal-formulario" id="modal-formulario-registrar-personal">

            <div class="formulario-container" id="formulario-container">
                
                <div class="botones-superiores-modal-formulario">
                    <span class="btn-cerrar-modal-fomulario" id="btn-cerrar-modal-fomulario"><i class="fa-solid fa-xmark"></i></span>
                </div>

                <div class="col col-1">
                    <!--contenido que ya no se utiliza-->
                </div>

                <div class="col col-2">

                    <!-- <div class="btn-box"> hay un JS para hacer los cambios de formulario animacion_cambio_formulario.js
                        <button class="btn btn-1" id="btn-formulario-1">Formu 1</button>
                        <button class="btn btn-2" id="btn-formulario-2">Formu 2</button>
                        <button class="btn btn-3" id="btn-formulario-3">Formu 3</button>
                    </div> -->

                    <form id="form-registrar-personal" method="POST" action="{{ route('grup_admin.grup_personal_acceso.name_registrar_personal.registrar_personal') }}" accept-charset="UTF-8">
                        @csrf <!--Esto es obligatorio en POST/PUT/PATCH/DELETE. protege contra ataques CSRF-->
                        <div class="form-0">

                            <div class="form-title">
                                <span>Registrar personal</span>
                            </div>

                            <div class="form-inputs">

                                <!--<div class="form-doble">
                                    
                                </div>-->

                                <div class="scroll">

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="nombre-input" name="nombre" placeholder="Nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ejem: Fernando" autocomplete="off" autocapitalize="words" required>

                                        <input class="input-field" type="text" id="apellidos-input" name="apellidos" placeholder="Apellidos" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ejem: Cuevas Cuevas" autocomplete="off" autocapitalize="words" required>
                                        <i class="ri-user-2-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="email" id="email-input" name="email" value="{{ old('email') }}" placeholder="Correo electronico" autocomplete="off" autocapitalize="none" spellcheck="false" required>
                                        <i class="ri-mail-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="tel" id="telefono-input" name="telefono" value="{{ old('telefono') }}" placeholder="Numero de telefono" pattern="[0-9]{10}" title="Tu numero debe de ser valido (10 digitos)" autocomplete="off" required>
                                        <i class="ri-phone-line icon"></i>
                                    </div>
                                    
                                    <div class="input-box"><!--Laravel tiene una regla de validacion de contrañas, entonces el name= tienes que decir "password"-->
                                        <input class="input-field" type="password" id="password-input" name="password" placeholder="Contraseña" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minino 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)"  autocomplete="off" required>
                                        <i class="ri-lock-2-line icon" id="togglePassword"></i>
                                    </div>

                                    <div class="input-box"><!--Laravel tiene una regla de validacion de contrañas, entonces el name= tienes que decir "password_confirmation"-->
                                        <input class="input-field" type="password" id="confirm-password-input" name="password_confirmation" placeholder="Repetir contraseña" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minino 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)"  autocomplete="off" required>
                                        <i class="ri-lock-2-line icon" id="toggleConfirmPassword"></i>
                                    </div>

                                    <div class="input-box">
                                        <input type="hidden" name="activo" value="0">
                                        <label class="input-field switch-estado">
                                            <input type="checkbox" name="activo" value="1" checked>
                                            <span class="slider-switch-estado"></span>
                                            <span class="texto-switch-estado">Activar cuenta</span>
                                        </label>
                                    </div>
                                    
                                    <div class="separador-formulario"></div>

                                    <div  class="contenedor-elementos-extra-form">
                                        <p class="subtitulo-elementos-extra-form">Asignar rol</p>

                                        @if($roles->count() === 0)
                                            <p class="sin-elementos-extra-form">No hay roles registrados. Primero crea roles.</p>
                                        @else

                                            <div id="roles-container">
                                                @foreach($roles as $rol)
                                                    <label class="elementos-checkbox">
                                                        <input type="checkbox" name="roles[]" value="{{ $rol->id }}" @if(($rol->clave ?? '') === 'admin' && !empty($adminOcupado)) disabled @endif>
                                                        <span class="circulo-checkbox"></span>
                                                        <span class="texto-checkbox"><b>{{ $rol->nombre }}</b> ({{ $rol->clave }})</span>
                                                    </label>
                                                @endforeach
                                            </div>

                                        @endif
                                    </div>

                                </div>


                                <div class="informacion-extra-formulario">
                                    <p>Procure no guardar las credenciales en el navegador de su dispositivo.</p>
                                </div>

                                <!-- <div class="forgot-pass">
                                    <a id="btn-ayuda-registro" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                                </div> -->

                                <div class="botones-formulario">
                                    <button type="button" class="btn-cancelar-borrar btn-limpiar-formulario" data-limpiar-formulario="form-registrar-personal">
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


        <!-- INFORMACION DEL PERSONAL -->
        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información del Listado del personal</span></h2>

            <div class="contenedores-informacion">
                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Listado del personal] podrá ver a todo el personal registrado en la plataforma SAAE. Se mostrará la información de las cuentas (nombre y apellidos, correo, teléfono, estado, roles asignados, fechas de acceso, registro y edición) para llevar un control de estas. Por ello, maneja esta información con cuidado y no la compartas.</p>

                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">ACCIONES QUE SE PODRÁN REALIZAR. <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">
                            <p class="texto texto-objetivo-leer">
                                <b>1.</b> Botón <i class="fa-solid fa-rotate"></i> para refrescar (cargar nuevamente el contenido de la tabla).
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <b>2.</b> Caja de búsqueda  <i class="fa-solid fa-magnifying-glass"></i> para el personal, ya sea por su ID, nombre o correo.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <b>3.</b> Botón para ver <i class="fa-regular fa-eye"></i> los roles que tiene asignados, el cual abrirá un modal para mostrar el listado de roles de dicho personal.
                            </p>
                            
                            <p class="texto texto-objetivo-leer">
                                <b>4.</b> Botón para editar <i class="fa-solid fa-pen-to-square"></i> la cuenta del personal seleccionado, el cual abrirá un formulario para modificar sus datos. Los datos más importantes para el correcto funcionamiento y acceso son la Activación de la cuenta y sus Roles asignados: si no está seleccionada la opción "Activar cuenta", ese personal no podrá iniciar sesión en la plataforma; y si no tiene por lo menos 1 rol asignado, no podrá realizar acciones dentro de la plataforma.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <b>5.</b> Botón para eliminar <i class="fa-solid fa-trash"></i> a un personal. Al presionarlo, se pedirá la confirmación de eliminación. Se recomienda <span class="palabra-resaltada">NO ELIMINAR a ningún personal si ha realizado acciones dentro de la plataforma</span>; en caso contrario, no habrá problemas. Si se desea que ese personal ya no acceda a la plataforma, basta con desactivar su cuenta y cambiar datos.
                            </p>
                        </div>
                    </div>

                </div>

                <p class="texto">Si deseas registrar roles o permisos, ve a: <a href="{{ route('grup_admin.grup_personal_acceso.name_roles_permisos') }}">[<i class="fa-solid fa-caret-right"></i> Personal, roles y permisos]</a></p>
            </div>
        </div>


    </div>
@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/personal_acceso/funcion_formulario_registrar_personal_crear_rol.js') }}"></script>
    <script src="{{ asset('js/personal/personal_acceso/funcion_listado_personal.js') }}"></script>
    <script src="{{ asset('js/personal/ver_verificar_contrasena_formulario.js') }}"></script>
@endpush