<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
<link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Gestion de estudiantes | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Gestión de estudiantes</h1>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Listado de estudiantes</span></h2>

            <p class="texto-contenedor">Se muestra una tabla de todos los estudiantes.</p>

            <div class="contenedores-informacion">

                <div class="contenedor-tablas-contenido">

                    <div class="contenedor-botones-accion-tabla">
                        <button type="button" class="btn-registro-tabla con-texto btn-abrir-modal-formulario-registro" data-modal-formulario-registro="modal-formulario-registrar-estudiante">
                            <i class="fa-solid fa-plus"></i> Registrar estudiante
                        </button>

                        <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-estudiantes">
                            <i class="fa-solid fa-rotate"></i>
                            <span class="spinner-tabla"></span>
                        </button>

                        <div class="contenedor-buscador-tabla">
                            <input type="text" id="input-buscar-estudiantes" class="input-buscar-tabla"
                                placeholder="ID, No. control, Nombre y Correo">
                            <button type="button" class="btn-buscar-tabla" id="btn-buscar-estudiantes">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>
                    </div>

                    <div class="contenedor-filtros-tabla">
                        <select id="filtro-area-estudiantes" class="select-buscar-tabla">
                            <option value="">Todas las especialidades</option>
                            @foreach($areaEspecialidad as $area)
                                <option value="{{ $area->id }}">{{ $area->nombre }}</option>
                            @endforeach
                        </select>

                        <select id="filtro-estatus-estudiantes" class="select-buscar-tabla">
                            <option value="">Todos los estatus</option>
                            @foreach($estatusEscolares as $estatus)
                                <option value="{{ $estatus->id }}">{{ $estatus->nombre }}</option>
                            @endforeach
                        </select>

                        <select id="filtro-activo-estudiantes" class="select-buscar-tabla">
                            <option value="">Todos los estados</option>
                            <option value="1">Activados</option>
                            <option value="0">Desactivados</option>
                        </select>

                        <select id="filtro-per-page-estudiantes" class="select-buscar-tabla">
                            <option value="20">20</option>
                            <option value="50" selected>50</option>
                            <option value="100">100</option>
                            <option value="200">200</option>
                            <option value="300">300</option>
                            <option value="400">400</option>
                            <option value="500" disabled>500 (proximamente)</option>
                        </select>

                        <button type="button" class="btn-accion-filtro limpiar-filtros" id="btn-limpiar-filtros-estudiantes">
                            <span><i class="fa-solid fa-eraser"></i> Limpiar</span>
                        </button>
                    </div>


                    
                    <div class="tabla-scroll">
                        <div class="contenedor-tabla">
                            <table class="tabla-contenido" id="tabla-listado-estudiantes"
                                data-url-table-listado-estudiantes="{{ route('grup_personal.grup_estudiantes.name_tabla_listado_estudiante') }}"

                                data-url-table-generar-correo-institucional="{{ route('grup_personal.grup_estudiantes.name_generar_correo_institucional_estudiante', ['id' => '__ID__']) }}"
                                data-url-table-generar-correos-institucionales="{{ route('grup_personal.grup_estudiantes.name_generar_correos_institucionales_pendientes') }}"
                                data-url-table-reenviar-activacion="{{ route('grup_personal.grup_estudiantes.name_reenviar_activacion_cuenta_estudiante', ['id' => '__ID__']) }}"
                                data-url-table-reenviar-activaciones="{{ route('grup_personal.grup_estudiantes.name_reenviar_activaciones_cuentas_pendientes') }}"

                                data-url-table-ver-datos-escolares-estudiante="{{ route('grup_personal.grup_estudiantes.name_ver_datos_escolares_estudiante', ['id' => '__ID__']) }}"

                                data-url-table-ver-asignaciones-estudiante="{{ route('grup_personal.grup_estudiantes.name_ver_asignaciones_estudiante', ['id' => '__ID__']) }}"
                                data-url-table-guardar-asignacion-estudiante="{{ route('grup_personal.grup_estudiantes.name_guardar_asignacion_estudiante', ['id' => '__ID__']) }}"
                                data-url-table-desactivar-asignacion-estudiante="{{ route('grup_personal.grup_estudiantes.name_desactivar_asignacion_estudiante', ['id' => '__ID__']) }}"
                                data-url-table-reactivar-asignacion-estudiante="{{ route('grup_personal.grup_estudiantes.name_reactivar_asignacion_estudiante', ['id' => '__ID__']) }}"
                                data-url-table-eliminar-asignacion-estudiante="{{ route('grup_personal.grup_estudiantes.name_eliminar_asignacion_estudiante', ['id' => '__ID__']) }}"

                                data-url-table-ver-estudiante="{{ route('grup_personal.grup_estudiantes.name_ver_estudiante', ['id' => '__ID__']) }}"
                                data-url-table-editar-estudiante="{{ route('grup_personal.grup_estudiantes.name_editar_estudiante', ['id' => '__ID__']) }}"
                                data-url-table-eliminar-estudiante="{{ route('grup_personal.grup_estudiantes.name_eliminar_estudiante', ['id' => '__ID__']) }}"
                                
                                data-url-table-exportar-estudiantes-excel="{{ route('grup_personal.grup_estudiantes.name_exportar_estudiantes_excel') }}">
                                <thead>
                                    <tr>
                                        <th class="th-primero">ID</th>
                                        <th>No. Control</th>
                                        <th>Nombre y apellidos</th>
                                        <th>Correo</th>
                                        <th>Telefono</th>
                                        <th>Datos escolares</th>
                                        <th>Estado de cuenta</th>
                                        <th>Ultimo acceso</th>
                                        <th>Fechas de</th>
                                        <th>Acciones</th>
                                        <th class="th-ultimo">Eliminar</th>
                                    </tr>
                                </thead>

                                <tbody>
                                        <tr><td colspan="11" class="td-estado-tabla">Cargando contenido…</td></tr>
                                </tbody>

                            </table>
                        </div>
                    </div>

                    <div class="contenedor-botones-accion-tabla">

                        <button type="button" class="btn-accion-tabla con-texto" id="btn-generar-correos-institucionales-pendientes">
                            <span><i class="fa-solid fa-envelope-circle-check"></i> Generar correos pendientes</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Generando correos pendientes</span>
                        </button>
                        
                        <button type="button" class="btn-accion-tabla con-texto" id="btn-reenviar-activaciones-estudiantes">
                            <span><i class="fa-solid fa-paper-plane"></i> Reenviar activaciones</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Reenviando activaciones</span>
                        </button>

                        <button type="button" class="btn-accion-tabla con-texto" id="btn-exportar-estudiantes-excel">
                            <span><i class="fa-solid fa-file-excel"></i> Exportar Excel</span>
                            <span class="spinner-tabla"></span>
                            <span class="texto-spinner-tabla">Exportando</span>
                        </button>

                    </div>

                    <div class="contenedor-paginacion-tabla">
                        <button type="button" id="btn-anterior-estudiantes" class="btn-pagina-tabla">
                            <i class="fa-solid fa-angle-left"></i> Anterior
                        </button>

                        <span id="texto-pagina-estudiantes" class="texto-pagina-tabla">
                            Página 1 de 1
                        </span>

                        <button type="button" id="btn-siguiente-estudiantes" class="btn-pagina-tabla">
                            Siguiente <i class="fa-solid fa-angle-right"></i>
                        </button>
                    </div>

                    <div class="contenedor-resumen-tabla">
                        <p id="info-paginacion-estudiantes">
                            Mostrando 0 a 0 de 0 estudiantes
                        </p>
                    </div>

                </div>

            </div>
        </div>


        
        <!--Modal del formulario para registrar estudianets-->
        <div class="modal-formulario" id="modal-formulario-registrar-estudiante">

            <div class="formulario-container" id="formulario-container">
                <span class="btn-cerrar-modal-fomulario" id="btn-cerrar-modal-fomulario">&times;</span>

                <div class="col col-1">
                    <!--contenido que ya no se utiliza-->
                </div>


                <div class="col col-2">

                    <form id="form-registrar-estudiante" method="POST" action="{{ route('grup_personal.grup_estudiantes.name_registrar_estudiante.registrar_estudiante') }}" accept-charset="UTF-8">
                        @csrf <!--Esto es obligatorio en POST/PUT/PATCH/DELETE. protege contra ataques CSRF-->
                        <div class="form-0">

                            <div class="form-title">
                                <span>Registrar estudiante</span>
                            </div>

                            <div class="form-inputs">

                                <div class="scroll">

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="numero-control-input" name="numero_control" placeholder="Numero de control" pattern="^[A-Z][0-9]{2}[A-Z]{2}[0-9]{3}$" maxlength="8" title="Solo 8 caracteres: 1 letra mayúscula, 2 números, 2 letras mayúsculas y 3 números. Ejemplo: M01CE001" autocomplete="off" required>
                                        <i class="ri-id-card-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="text" id="nombre-input" name="nombre" placeholder="Nombre" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ejem: Fernando" autocomplete="off" autocapitalize="words" required>

                                        <input class="input-field" type="text" id="apellidos-input" name="apellidos" placeholder="Apellidos" pattern="^[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+(?:\s+[A-Za-zÁÉÍÓÚÜÑáéíóúüñ]+)*$" title="Solo letras y espacios. Ejem: Cuevas Cuevas" autocomplete="off" autocapitalize="words" required>
                                        <i class="ri-user-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="email" id="email-input" name="email" value="{{ old('email') }}" placeholder="Correo institucional" autocomplete="off" autocapitalize="none" spellcheck="false" required>
                                        <i class="ri-mail-line icon"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="tel" id="telefono-input" name="telefono" value="{{ old('telefono') }}" placeholder="Numero de telefono" pattern="[0-9]{10}" title="Tu numero debe de ser valido (10 digitos)" autocomplete="off" required>
                                        <i class="ri-phone-line icon"></i>
                                    </div>
                                    
                                    <div class="input-box">
                                        <input class="input-field" type="password" id="password-input" name="password" placeholder="Contraseña" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minimo 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)"  autocomplete="off" required>
                                        <i class="ri-lock-2-line icon" id="togglePassword"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="password" id="confirm-password-input" name="password_confirmation" placeholder="Repetir contraseña" pattern="^(?=.*[A-Z])(?=.*[0-9])(?=.*[!@#$%^&*])[A-Za-z0-9!@#$%^&*]{6,}$" title="Minimo 6 caracteres (1 letra mayúscula, 1 número y 1 símbolo)"  autocomplete="off" required>
                                        <i class="ri-lock-2-line icon" id="toggleConfirmPassword"></i>
                                    </div>

                                    <div class="input-box">
                                        <input class="input-field" type="number" id="anio-ingreso-input" name="anio_ingreso" value="{{ old('anio_ingreso') }}" placeholder="Año de ingreso" min="2000" max="{{ now()->year }}" required>
                                        <i class="ri-calendar-line icon"></i>

                                        <select class="input-field" name="mes_ingreso" id="mes-ingreso-id" required>
                                            <option class="option-input-field-select" value="">-- Su mes de ingreso --</option>
                                            <option class="option-input-field-select" value="1" {{ old('mes_ingreso') == 1 ? 'selected' : '' }}>Enero</option>
                                            <option class="option-input-field-select" value="2" {{ old('mes_ingreso') == 2 ? 'selected' : '' }}>Febrero</option>
                                            <option class="option-input-field-select" value="3" {{ old('mes_ingreso') == 3 ? 'selected' : '' }}>Marzo</option>
                                            <option class="option-input-field-select" value="4" {{ old('mes_ingreso') == 4 ? 'selected' : '' }}>Abril</option>
                                            <option class="option-input-field-select" value="5" {{ old('mes_ingreso') == 5 ? 'selected' : '' }}>Mayo</option>
                                            <option class="option-input-field-select" value="6" {{ old('mes_ingreso') == 6 ? 'selected' : '' }}>Junio</option>
                                            <option class="option-input-field-select" value="7" {{ old('mes_ingreso') == 7 ? 'selected' : '' }}>Julio</option>
                                            <option class="option-input-field-select" value="8" {{ old('mes_ingreso') == 8 ? 'selected' : '' }}>Agosto</option>
                                            <option class="option-input-field-select" value="9" {{ old('mes_ingreso') == 9 ? 'selected' : '' }}>Septiembre</option>
                                            <option class="option-input-field-select" value="10" {{ old('mes_ingreso') == 10 ? 'selected' : '' }}>Octubre</option>
                                            <option class="option-input-field-select" value="11" {{ old('mes_ingreso') == 11 ? 'selected' : '' }}>Noviembre</option>
                                            <option class="option-input-field-select" value="12" {{ old('mes_ingreso') == 12 ? 'selected' : '' }}>Diciembre</option>
                                        </select>
                                    </div>

                                    <div class="input-box">
                                        <select class="input-field" name="area_id" id="area-id" required>
                                            <option class="option-input-field-select" value="">-- Selecciona su especialidad --</option>
                                            @foreach($areaEspecialidad as $area)
                                                <option class="option-input-field-select" value="{{ $area->id }}">
                                                    {{ $area->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="input-box">
                                        <select class="input-field" name="estatus_id" id="estatus-id" required>
                                            <option class="option-input-field-select" value="">-- Selecciona su estatus --</option>
                                            @foreach($estatusEscolares as $estatus)
                                                <option class="option-input-field-select" value="{{ $estatus->id }}">
                                                    {{ $estatus->nombre }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="input-box">
                                        <input type="hidden" name="activo" value="0">
                                        <label class="input-field switch-estado">
                                            <input type="checkbox" name="activo" value="1" checked>
                                            <span class="slider-switch-estado"></span>
                                            <span class="texto-switch-estado">Activar cuenta</span>
                                        </label>
                                    </div>
                                    
                                    

                                </div>

                                <div class="informacion-extra-formulario">
                                    <p>Procure no guardar las credenciales en el navegador de su dispositivo.</p>
                                </div>

                                <!-- <div class="forgot-pass">
                                    <a id="btn-ayuda-registro" class="pass-ayuda" href="#">¿Necesitas ayuda?</a>
                                </div> -->

                                <div class="botones-formulario">
                                    <button type="button" class="btn-cancelar-borrar btn-limpiar-formulario" data-limpiar-formulario="form-registrar-estudiante">
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


        <!-- INFORMACION DE LA GESTION DE ESTUDIANTES -->
        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de Gestión de estudiantes</span></h2>

            <div class="contenedores-informacion">
                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Gestión de estudiantes] podrá monitorear, registrar, actualizar e incluso a eliminar a estudiantes. A continuación se da una breve información que es importante saber:</p>
                
                <div class="informacion-importante contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h3 class="titulo-contenedores-informacion texto-objetivo-leer">SOBRE EL ESTUDIANTE:</h3> 
                    <p class="texto texto-objetivo-leer">
                        <b>1.</b> Se gestionara el registro general de los estudiantes dentro de la plataforma SAAE. Desde aquí el personal autorizado puede consultar, buscar, editar y eliminar registros, además de registrar estudiantes manualmente cuando sea necesario.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>2.</b> El botón de <span class="palabra-resaltada">Registrar estudiantes</span> permite dar de alta estudiantes directamente en la plataforma. Este formulario debe utilizarse cuando el estudiante aún no exista en el sistema o cuando sea necesario capturar su información de forma individual.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>3.</b> Las importaciones de Asistencia y/o de Datos escolares pueden registrar a los estudiantes de manera automatica, con la ayuda de su numero de control, sin embargo, sus demas datos no se registraran. Por ello, se requiere de intervención del personal para completar su registro y con ello activar sus cuentas.
                    </p>

                    <p class="titulo-contenedores-informacion titulos-siguientes texto-objetivo-leer">CONSIDERACIONES IMPORTANTES:</p>
                    <ul class="texto texto-objetivo-leer">
                        <li>El número de control debe capturarse correctamente, ya que será la base para futuras operaciones del estudiante.</li>
                        <li>El correo institucional no siempre se genera al momento del registro del estudiante por medio de importaciones de Asistencia o Datos escolares; puede generarse posteriormente desde la tabla de estudiantes.</li>
                        <li>La generación de correo institucional solo aplica a estudiantes con número de control formalizado, sin correo registrado y con cuenta inactiva.</li>
                        <li>Si el estudiante ya tiene correo institucional pero aún no activa su cuenta, se puede utilizar la opción de <strong>reenviar activación</strong>.</li>
                        <li>El enlace de activación enviado al estudiante tiene una vigencia limitada y, si vence o deja de ser válido, debe generarse o reenviarse uno nuevo según el caso.</li>
                        <li>Las funciones de generación y reenvío de activación dependen del sistema de colas de Laravel, por lo que el worker debe estar en ejecución para procesar correctamente los envíos.</li>
                    </ul>

                    <p class="texto texto-objetivo-leer">
                        <b>Importante:</b> Antes de generar o reenviar enlaces de activación, verifica que la información del estudiante sea correcta, especialmente el número de control y el correo registrado en el sistema.
                    </p>
                </div>

                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>  
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">ACCIONES QUE SE PODRÁN REALIZAR. <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">
                            <h3 class="titulo-texto texto-objetivo-leer">Formulario de registro para el estudiante:</h3>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">1.</span> Al presionar el botón <i class="fa-solid fa-plus"></i>, se abrirá un modal con dicho formulario. Se utiliza para dar de alta a un estudiante de forma individual dentro del sistema, cuando sea necesario capturar su información directamente.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">2.</span> Al registrar un estudiante, se deberán llenar los siguientes datos: <span class="palabra-resaltada">Número de control</span>, <span class="palabra-resaltada">Nombre y apellidos</span>, <span class="palabra-resaltada">Correo institucional</span>, <span class="palabra-resaltada">Número de teléfono</span>, <span class="palabra-resaltada">Contraseña</span>, <span class="palabra-resaltada">Año y mes de ingreso</span>, <span class="palabra-resaltada">Área de especialidad</span>, <span class="palabra-resaltada">Estatus académico</span> y <span class="palabra-resaltada">Activar su cuenta</span>.
                            </p>

                            <h3 class="titulo-texto titulos-siguientes-texto texto-objetivo-leer">Listado de estudiantes (tabla):</h3>
                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">1.</span> Permite consultar y administrar los registros almacenados, además de ejecutar acciones relacionadas con el estado de la cuenta y el proceso de activación.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">2. Caja de búsqueda</span> <i class="fa-solid fa-magnifying-glass"></i>, ya sea por ID, No. de control, nombre o correo.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">3. Filtros de estudiantes</span>, donde al seleccionar uno o varios, se mostrará el resultado con dicha selección. El filtro con numeración indica la cantidad de alumnos que se mostrarán en la tabla. El botón de <i class="fa-solid fa-eraser"></i> limpiar remueve los filtros seleccionados, incluida la caja de búsqueda.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">4.</span> En la columna de correo aparecerán 2 tipos de botones: <i class="fa-solid fa-at"></i> para generar el correo del estudiante a partir de su número de control, siempre que su cuenta no esté activa, y que a su vez envía un enlace para la activación de la cuenta; y <i class="fa-solid fa-paper-plane"></i> para reenviar el enlace de activación de cuenta.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">5. El botón</span> <i class="fa-regular fa-eye"></i> mostrará los demás datos escolares: <span class="palabra-resaltada">Año y mes de ingreso</span>, <span class="palabra-resaltada">Área de especialidad</span> y <span class="palabra-resaltada">Estatus académico</span>. Además, indicará si esos datos fueron asignados por una importación o manualmente.
                            </p>
                            
                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">6. El botón para editar</span> <i class="fa-solid fa-pen-to-square"></i> mostrará nuevamente un formulario con sus datos actuales, el cual permitirá editarlos y guardar los cambios.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">7. El botón para eliminar</span> <i class="fa-solid fa-trash"></i> pedirá una confirmación de eliminación. Se recomienda <span class="palabra-resaltada">NO ELIMINAR</span> a ningún estudiante que tenga registros de eventos o acciones en la plataforma, como asistencias o subida de justificantes. Solo en casos muy específicos y necesarios se podrá eliminar.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">8.</span> Los botones de <span class="palabra-resaltada">Generar correos pendientes</span> y <span class="palabra-resaltada">Reenviar activaciones</span> realizan la misma acción descrita en la columna de correo, pero con la diferencia de que se ejecuta de forma masiva; es decir, si hay 10, 100 o más estudiantes sin correo o que solicitan nuevamente el enlace de activación de cuenta, estos se procesarán.
                            </p>

                            <p class="texto texto-objetivo-leer">
                                <span class="palabra-resaltada">9. Los botones de paginación</span> ayudan a mostrar cierta cantidad de estudiantes por página, hasta llegar al último registro.
                            </p>
                        </div>
                    </div>

                    <p class="texto texto-objetivo-leer">Entendida la información anterior, ya podrás gestionar a los estudiantes.</p>
                </div>
            </div>
        </div>


    </div>

@endsection <!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
    <script src="{{ asset('js/personal/estudiantes/funcion_formulario_registrar_estudiantes.js') }}"></script>
    <script src="{{ asset('js/personal/estudiantes/funcion_listado_estudiantes.js') }}"></script>
    <script src="{{ asset('js/personal/ver_verificar_contrasena_formulario.js') }}"></script>
    <script src="{{ asset('js/personal/animacion_cambio_formulario.js') }}"></script>
@endpush