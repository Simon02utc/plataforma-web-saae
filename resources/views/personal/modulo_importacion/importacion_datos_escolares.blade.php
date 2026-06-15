<!--ESTRUCTURA DE PAGINA (div, header, main, footer) - junto con css, js principales (o que se repiten)-->
@extends('layouts.personal.estructura_web_personal')

<!--INDICADOR DEL ESTILO PARA ESTA PAGINA-->
@push('styles')
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/modulo_importacion/estilos_modulo_importacion.css') }}">
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_formulario_inputs_botones.css') }}">
    <link rel="stylesheet" href="{{ asset('css/personal_estudiante/estilos_tabla_elementos.css') }}">
@endpush

<!--TITULO DE LA PAGINA AL ESTAR EN ELLA-->
@section('title', 'Importar asistencia | SAAE')

<!--INDICADOR DE CONTENIDO DE LA PAGINA--->
@section('content')

    <div class="contenedor-principal-seccion">

        <div class="contenedor-titulos-secciones">
            <h1>Importación de datos escolares</h1>
        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Importación manual</span></h2>

            <div class="contenedor-importacion">
                <p class="texto-contenedor">Sube el archivo <span class="span-tipo-archivo excel">.xls</span> / <span class="span-tipo-archivo">.xlsx</span>, para registrar y actualizar los datos escolares del estudiante.</p>

                <div class="contenedores-informacion">

                    <form class="formulario-importar-documentos-datos-escolares" id="formulario-importar-documentos-datos-escolares" method="POST"
                        action="{{ route('grup_personal.grup_modulo_importacion.ejecutar_importacion_datos_escolares') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="form-inputs">
                            <div class="input-box">
                                <input class="input-field type-file" id="input-excel" type="file" name="archivo_importacion" accept=".xls,.xlsx" title="Sube el archivo Excel o CSV" required>
                                <i class="ri-file-line icon"></i>
                            </div>

                            <div class="input-box">
                                <select class="input-field" name="fuente_datos_escolares_id" required>
                                    <option class="option-input-field-select" value="">-- Selecciona una fuente de datos escolares --</option>
                                    @foreach($fuentesDatosEscolares as $fuenteDatos)
                                        <option class="option-input-field-select" value="{{ $fuenteDatos->id }}">{{ $fuenteDatos->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="input-box">
                                <select class="input-field" name="tipo_importacion" id="tipo-importacion" required>
                                    <option class="option-input-field-select" value="">-- Selecciona el tipo de importación --</option>
                                    <option class="option-input-field-select" value="COMPLETA">Importación completa</option>
                                    <option class="option-input-field-select" value="SOLO_ESTUDIANTES">Solo estudiantes</option>
                                </select>
                            </div>

                            <div class="informacion-extra-formulario" id="info-requisitos-1"></div>

                            <div class="input-box">
                                <textarea class="input-field" id="input-notas" name="notas" placeholder="Escribe una nota sobre la importación." maxlength="500" title="Solo letras, espacios, y signos de puntuacion"></textarea>
                                <i class="ri-text-block icon"></i>
                            </div>


                            <div class="informacion-extra-formulario">
                                <p>Verifica que el archivo corresponda a la fuente de datos seleccionada antes de importarlo.</p>
                            </div>

                        </div>

                        <div class="botones-formulario">
                            <button class="btn-cancelar-borrar" id="btn-cancelar" type="button">
                                Cancelar
                            </button>

                            <button class="btn-guardar-enviar" id="btn-importar" type="submit">
                                <span>Importar</span> 
                                <span class="spinner"></span>
                                <span class="texto-spinner">Espera</span>
                            </button>

                        </div>
                    </form>

                    {{-- RESULTADO --}}
                    @if(session('resultado'))
                        @php($res = session('resultado'))
                        <div class="contenedor-mensaje-importacion {{ !empty($res['ok']) ? 'ok' : 'alerta' }}">
                            <p class="mensaje-inicial">{{ !empty($res['ok']) ? 'Importación completada' : 'Importación no realizada' }}</p>

                            <div class="contenido-mensaje">
                                <p class="mensaje-servidor">{{ $res['mensaje'] ?? '' }}</p>

                                @if(!empty($res['ok']))
                                    <div class="detalles-mensaje">
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Filas detectadas:</p>
                                            <p class="info-detalle">{{ $res['filas_detectadas'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Filas omitidas sin número de control:</p>
                                            <p class="info-detalle">{{ $res['filas_omitidas_sin_numero_control'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Estudiantes nuevos insertados:</p>
                                            <p class="info-detalle">{{ $res['estudiantes_insertados'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Estudiantes existentes detectados:</p>
                                            <p class="info-detalle">{{ $res['estudiantes_existentes_detectados'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Fichas escolares insertadas:</p>
                                            <p class="info-detalle">{{ $res['fichas_escolares_insertadas'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Fichas escolares actualizadas:</p>
                                            <p class="info-detalle">{{ $res['fichas_escolares_actualizadas'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Áreas de especialidad no reconocidas:</p>
                                            <p class="info-detalle">{{ $res['areas_especialidad_no_reconocidas'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Estatus escolares no reconocidos:</p>
                                            <p class="info-detalle">{{ $res['estatus_escolares_no_reconocidos'] ?? '-' }}</p>
                                        </div>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <details>
                            <summary class="btn-ver-detalles-tecnicos-mensaje">Ver resultados crudos</summary>
                            <pre class="caja-log-mensaje">{{ print_r($res, true) }}</pre>
                        </details>
                    @endif


                    {{-- ERRORES DE VALIDACIÓN --}}
                    @if ($errors->any())
                        <div class="contenedor-mensaje-importacion error">
                            <p class="mensaje-inicial">Error al importar</p>

                            <div class="contenido-mensaje">
                                <p class="mensaje-servidor">Revisa el archivo e inténtalo de nuevo.</p>

                                <ul class="lista-mensaje-error">
                                    @foreach($errors->all() as $e)
                                        <li>{{ $e }}</li>
                                    @endforeach
                                </ul>

                            </div>
                        </div>
                    @endif

                </div>

            </div>

        </div>


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Historial de importaciones</span></h2>

            <div class="contenedor-importacion">
                <p class="texto-contenedor">Se muestra un sencillo historial de importaciones recientes.</p>

                <div class="contenedores-informacion">

                    <div class="contenedor-tablas-contenido">

                        <div class="contenedor-botones-accion-tabla">
                            <button type="button" class="btn-accion-tabla" id="btn-refrescar-tabla-historial-simple-datos-escolares">
                                <span><i class="fa-solid fa-rotate"></i></span>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>

                        
                        <div class="tabla-scroll">
                            <div class="contenedor-tabla">
                                <table class="tabla-contenido" id="tabla-historial-simple-importaciones-datos-escolares"
                                    data-url-tabla-listado-historial-simple-importaciones-datos-escolares="{{ route('grup_personal.grup_modulo_importacion.name_historial_simple_importaciones_datos_escolares') }}"
                                    data-url-tabla-ver-detalles-importacion-datos-escolares-simple="{{ route('grup_personal.grup_modulo_importacion.name_ver_detalles_importacion_datos_escolares', ['id' => '__ID__']) }}"
                                    data-url-tabla-descargar-archivo-importacion-datos-escolares-simple="{{ route('grup_personal.grup_modulo_importacion.name_descargar_archivo_importacion_datos_escolares', ['id' => '__ID__']) }}">
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
                                            <tr><td colspan="8" class="td-estado-tabla">Cargando contenido…</td></tr>
                                    </tbody>

                                </table>
                            </div>
                        </div>

                    </div>

                    <p class="texto-contenedor">Historial completo en <b>Auditoría y seguridad</b>.</p>
                </div>

            </div>
        </div>

        

        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de la importación de datos escolares</span></h2>

            <div class="contenedores-informacion">

                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Importación de datos escolares] podrá realizar la subida de estudiantes a la plataforma, junto a sus datos escolares (Año de generación, Mes de ingreso, Numero de control y Nombre). Y en cuento a su Área de especialidad y su Estatus, estos deberan debe coincidir exactamente con uno de los registros del catálogo academico.</p> 

                <p class="texto">Todo esto por medio de un documento en Excel (<span class="span-tipo-archivo excel">.xls</span> / <span class="span-tipo-archivo">.xlsx</span>) que <b>no debe de exceder de un tamaño de 10 MB, que es igual a 10240 KB.</b> A continuación se da una breve información que es importante saber:</p>

                <div class="informacion-importante contenedor-informacion-leer">    
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>

                    <h3 class="titulo-contenedores-informacion titulo-texto texto-objetivo-leer">REGLA GENERAL DEL ARCHIVO:</h3>    

                    <p class="texto texto-objetivo-leer">
                        <b>1.</b> Debe contener al menos una hoja o ambas, con el nombre: <span class="palabra-resaltada">MAESTRIA</span> y/o <span class="palabra-resaltada">DOCTORADO</span>, en mayúsculas y sin acentos. Si hay otras hojas serán ignoradas. El limite es de 6 hojas.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>2.</b> La información válida debe comenzar en la fila 1 como encabezados (sin acentos) y desde la fila 2 como datos del estudiante.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>3.</b> Una fila = 1 estudiante.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>4.</b> No debe haber celdas combinadas en la tabla de datos.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>5.</b> No debe haber subtítulos, notas, colores especiales ni bloques intermedios dentro de la tabla.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>6.</b> El archivo no debe de exceder de un tamaño de 10 MB, que es igual a 10240 KB.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>7.</b> El archivo no debe de estar mal configurado/optimizado, inflado o sucio internamente.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>8.</b> Si el archivo tiene demasiados estilos internos puede agotar recursos y hacer más lento el proceso.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>9.</b> Si el contenido del archivo es grande, el procesamiento podría ser más lento o incluso no se podra efectuar. Por ello verifica que este en orden.
                    </p>


                    <p class="texto texto-objetivo-leer">
                        <b>A continuación puedes ver y/o descargar la plantilla oficial del Excel. Para posteriormente leer la siguiente información.</b>
                    </p>

                    <div class="caja-botones-archivos-plantilla">
                        <a class="btn-ver-plantilla deshabilitado" >
                            <i class="fa-solid fa-eye"></i> Plantilla
                        </a>

                        <a href="{{ route('grup_personal.name_descargar_plantilla_datos_escolares') }}" class="btn-descargar-plantilla">
                            Plantilla <i class="fa-solid fa-download"></i>
                        </a>
                    </div>

                </div>


                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">ESTRUCTURA OBLIGATORIA POR HOJA. <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">
                            <p class="titulo-texto texto-objetivo-leer texto-objetivo-leer">
                                La hoja <span class="palabra-resaltada">MAESTRIA</span> debe de respetar estas columnas: 
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna B,</span> con el encabezado de nombre "AÑO DE GENERACION".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna C,</span> con el encabezado de nombre "MES DE INGRESO".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna G,</span> con el encabezado de nombre "NO. CONTROL".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna H,</span> con el encabezado de nombre "ALUMNO".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna I,</span> con el encabezado de nombre "ESPECIALIDAD".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna K,</span> con el encabezado de nombre "ESTATUS".
                                </li>
                            </ul>

                            <p class="titulo-texto siguiente texto-objetivo-leer">
                                La hoja <span class="palabra-resaltada">MAESTRIA</span> debe de respetar estas columnas: 
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna B,</span> con el encabezado de nombre "AÑO DE GENERACION".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna C,</span> con el encabezado de nombre "MES DE INGRESO".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna H,</span> con el encabezado de nombre "NO. CONTROL".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna I,</span> con el encabezado de nombre "ALUMNO".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna J,</span> con el encabezado de nombre "ESPECIALIDAD".
                                </li>
                                <li>
                                    <span class="palabra-resaltada texto-objetivo-leer">Columna L,</span> con el encabezado de nombre "ESTATUS".
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>

                <div class="contenedor-acordeon-informacion contenedor-informacion-leer">
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>
                    <h2 class="titulo-contenedores-informacion titulo-acordeon texto-objetivo-leer">REGLAS POR COLUMNA. <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h2>

                    <div class="contenido-acordeon">
                        <div class="contenedor-texto-acordeon">
                            <p class="titulo-texto texto-objetivo-leer texto-objetivo-leer">
                                Columna de AÑO DE GENERACION<span class="palabra-resaltada"></span>: 
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    Formato permitido: Ejemplo de <span class="palabra-resaltada texto-objetivo-leer">2025, 2026, etc.</span>, cualquier año válido con 4 digitos.
                                </li>
                                <li>
                                    Regla: Debe representar el año académico/generación del estudiante. No debe venir vacío.
                                </li>
                            </ul>

                            <p class="titulo-texto siguiente texto-objetivo-leer">
                                Columna de MES DE INGRESO<span class="palabra-resaltada"></span>: 
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    Formato permitido: Ejemplo de <span class="palabra-resaltada texto-objetivo-leer">ENERO, FEBRERO, MARZO, etc.</span>, en mayusculas. También puede venir como 08, 8, AGOSTO 2025, pero se recomienda que sea en letras.
                                </li>
                                <li>
                                    Regla: Debe permitir que el parser extraiga el número del mes. No debe venir vacío.
                                </li>
                            </ul>

                            <p class="titulo-texto siguiente texto-objetivo-leer">
                                Columna de NO. CONTROL<span class="palabra-resaltada"></span>: 
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    Formato permitido: Ejemplo de <span class="palabra-resaltada texto-objetivo-leer">M25CE001, D24CE015, etc.</span>.
                                </li>
                                <li>
                                    Regla: No debe venir vacío. No debe de llevar espacios internos. Debe ser único por fila dentro de la hoja, idealmente en todo el archivo.
                                </li>
                            </ul>

                            <p class="titulo-texto siguiente texto-objetivo-leer">
                                Columna de ALUMNO<span class="palabra-resaltada"></span>: 
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    Formato permitido: Ejemplo de <span class="palabra-resaltada texto-objetivo-leer">PEREZ LOPEZ JUAN CARLOS.</span>.
                                </li>
                                <li>
                                    Regla: No debe venir vacío. No debe de tener números.
                                </li>
                            </ul>

                            <p class="titulo-texto siguiente texto-objetivo-leer">
                                Columna de ESPECIALIDAD<span class="palabra-resaltada"></span>: 
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    Formato permitido: Se exige colocar su nombre tal cual esta registrado en los catalogos academicos. Solicita el <span class="palabra-resaltada">catalogo de Áreas de especilidad</span> para verficicarlos.
                                </li>
                                <li>
                                    Regla: No debe venir vacío. Sin acentos.
                                </li>
                            </ul>

                            <p class="titulo-texto siguiente texto-objetivo-leer">
                                Columna de ESTATUS<span class="palabra-resaltada"></span>: 
                            </p>
                            <ul class="texto texto-objetivo-leer">
                                <li>
                                    Formato permitido: Tambien se exige colocar su nombre tal cual esta registrado en los catalogos academicos. Solicita el <span class="palabra-resaltada">catalogo de Estatus escolares para el estudiante</span> para verficicarlos.
                                </li>
                                <li>
                                    Regla: No debe venir vacío. Sin acentos.
                                </li>
                            </ul>
                        </div>
                    </div>

                </div>

                <p class="texto">Si deseas registrar mas fuentes de datos, solicita el apoyo del personal Administrativo de la plataforma.</p>
            </div>
        </div>

    </div>

@endsection 
<!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
<script src="{{ asset('js/personal/modulo_importacion/funcion_formulario_importacion_datos_escolares.js') }}"></script>
<script src="{{ asset('js/personal/modulo_importacion/mostrar_requisitos_formulario_importacion_datos_escolares.js') }}"></script>
<script src="{{ asset('js/personal/modulo_importacion/funcion_historial_simple_importaciones_datos_escolares.js') }}"></script>
@endpush