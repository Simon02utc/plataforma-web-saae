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
            <h1>Importación de asistencia</h1>
        </div>

        <!-- <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Importación automatica</span></h2>

            <div class="contenedor-importacion">
                <p class="texto-contenedor">Se muestra un sencillo historial de importaciones por día, que el Reloj checador envia automaticamente.</p>
                <p class="texto-contenedor">Se realizan varias importaciones durante clases, para el seguimiento de asistencia.</p>

                <div class="contenedores-informacion">

                    <div class="contenedor-img">
                        <img src="{{ asset('img_plataforma/ajustes.png') }}" alt="">
                    </div>
                </div>


            </div>
        </div> -->


        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Importación manual</span></h2>

            <div class="contenedor-importacion">
                <p class="texto-contenedor">Sube el archivo <span class="span-tipo-archivo excel">.xls</span> / <span class="span-tipo-archivo">.xlsx</span>, o <span class="span-tipo-archivo csv">.csv</span> para registrar y actualizar el seguimiento de asistencia (ya sea para el día a día, semanas y mes).</p>

                <div class="contenedores-informacion">

                    <form class="formulario-importar-excel" id="formulario-importar-excel" method="POST"
                        action="{{ route('grup_personal.grup_modulo_importacion.ejecutar_importacion_asistencia') }}"
                        enctype="multipart/form-data">
                        @csrf

                        <div class="form-inputs">
                            <div class="input-box">
                                <input class="input-field type-file" id="input-excel" type="file" name="archivo_importacion" accept=".xls,.xlsx,.csv" title="Sube el archivo Excel o CSV" required>
                                <i class="ri-file-line icon"></i>
                            </div>

                            <div class="input-box">
                                <select class="input-field" name="reloj_checador_id" required>
                                    <option class="option-input-field-select" value="">-- Selecciona un reloj checador --</option>
                                    @foreach($relojes as $reloj)
                                        <option class="option-input-field-select" value="{{ $reloj->id }}">{{ $reloj->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="input-box">
                                <select class="input-field" name="tipo_importacion" id="tipo-importacion" required>
                                    <option class="option-input-field-select" value="">-- Selecciona el tipo de importación --</option>
                                    <option class="option-input-field-select" value="COMPLETA">Importación completa</option>
                                    <option class="option-input-field-select" value="SOLO_TURNOS">Solo turnos/calendario</option>
                                    <option class="option-input-field-select" value="SOLO_ASISTENCIA">Solo asistencias</option>
                                </select>
                            </div>

                            <div class="informacion-extra-formulario" id="info-requisitos-1"></div>

                            <div class="input-box" id="box-periodo" style="display: none;">
                                <select class="input-field" name="periodo_id" id="periodo-id">
                                    <option class="option-input-field-select" value="">-- Selecciona un periodo --</option>
                                    @foreach($periodos as $periodo)
                                        <option class="option-input-field-select" value="{{ $periodo->id }}" {{ old('periodo_id') == $periodo->id ? 'selected' : '' }}>
                                            {{ $periodo->nombre }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>


                            <div class="input-box">
                                <textarea class="input-field" id="input-notas" name="notas" placeholder="Escribe una nota sobre la importación." maxlength="500" title="Solo letras, espacios, y signos de puntuacion"></textarea>
                                <i class="ri-text-block icon"></i>
                            </div>


                            <div class="informacion-extra-formulario">
                                <p>Verifica que el archivo corresponda al reloj checador seleccionado antes de importarlo.</p>
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


                    <!-- RESULTADOS -->
                    @if(session('resultado'))
                        @php($res = session('resultado'))
                        <div class="contenedor-mensaje-importacion {{ $res['ok'] ? 'ok' : 'alerta' }}">
                            <p class="mensaje-inicial">
                                @if($res['ok'])
                                    <i class="fa-regular fa-circle-check"></i>
                                    Importación completada
                                @else
                                    <i class="ri-error-warning-line"></i>
                                    Importación no realizada
                                @endif
                            </p>

                            <div class="contenido-mensaje">
                                <p class="mensaje-servidor">{{ $res['mensaje'] ?? '' }}</p>

                                @if(!empty($res['ok']))
                                    <h3 class="titulo-detalle-mensaje">Datos de la importación</h3>
                                    <div class="detalles-mensaje">
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Periodo:</p>
                                            <p class="info-detalle">{{ $res['periodo'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Días del periodo:</p>
                                            <p class="info-detalle">{{ $res['dias_periodo'] ?? '-' }}</p>
                                        </div>
                                    </div>


                                    <h3 class="titulo-detalle-mensaje">Estudiantes y su asistencia <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h3>
                                    <div class="detalles-mensaje">
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Estudiantes seleccionados:</p>
                                            <p class="info-detalle">{{ $res['estudiantes_seleccionados'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Marcaciones insertadas:</p>
                                            <p class="info-detalle">{{ $res['marcaciones_insertadas'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Días esperados detectados:</p>
                                            <p class="info-detalle">{{ $res['dias_esperados_detectados'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Días actualizados:</p>
                                            <p class="info-detalle">{{ $res['dias_actualizados'] ?? '-' }}</p>
                                        </div>
                                    </div>

                                    <h3 class="titulo-detalle-mensaje">Excepciones de la importación <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h3>
                                    <div class="detalles-mensaje">
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Estudiantes sin datos escolares:</p>
                                            <p class="info-detalle">{{ $res['estudiantes_sin_datos_escolares'] ?? '-' }}</p>
                                        </div>
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Estudiantes no registrados:</p>
                                            <p class="info-detalle">{{ $res['estudiantes_no_encontrados'] ?? '-' }}</p>
                                        </div>
                                    </div>


                                    <h3 class="titulo-detalle-mensaje">Alertas de asistencia <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h3>
                                    <div class="detalles-mensaje">
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Estudiantes revisados para alertas:</p>
                                            <p class="info-detalle">{{ $res['alertas_estudiantes_revisados'] ?? '-' }}</p>
                                        </div>

                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Alertas normales (faltas):</p>
                                            <p class="info-detalle">{{ $res['alertas_normales_creadas'] ?? '-' }}</p>
                                        </div>

                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Alertas especiales (suspención de beca):</p>
                                            <p class="info-detalle">{{ $res['alertas_especiales_creadas'] ?? '-' }}</p>
                                        </div>

                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">Total de correos despachados (estudiantes y/o personal):</p>
                                            <p class="info-detalle">{{ $res['total_correos_despachados_jobs'] ?? '-' }}</p>
                                        </div>
                                    </div>

                                    <h3 class="titulo-detalle-mensaje"><i class="ri-error-warning-line"></i> Advertencias de la importación <i class="fa-solid fa-chevron-down flecha-acordeon"></i></h3>
                                    <div class="detalles-mensaje">
                                        <div class="caja-detalle">
                                            <p class="nombre-detalle">¿Qué sucedio duran la importación?:</p>

                                            @if(!empty($res['advertencias']))
                                                <ul class="lista-advertencias">
                                                    @foreach($res['advertencias'] as $advertencia)
                                                        <li>{{ $advertencia }}</li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                <p class="info-detalle">No se detectaron advertencias.</p>
                                            @endif

                                        </div>
                                    </div>
                                @endif
                            </div>

                        </div>

                        <!-- ver el array completo como log -->
                        <details>
                            <summary class="btn-ver-detalles-tecnicos-mensaje">Ver resultados crudos</summary>
                            <pre class="caja-log-mensaje">{{ print_r($res, true) }}</pre>
                        </details>
                    @endif

                    <!-- ERRORES DE VALIDACIÓN -->
                    @if ($errors->any())
                        <div class="contenedor-mensaje-importacion error">
                            <p class="mensaje-inicial"><i class="fa-regular fa-circle-xmark"></i> Error al importar</p>

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


        <!-- TABLA DE HISTORIAL DE IMPORTACIONES -->
        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Historial de importaciones</span></h2>

            <div class="contenedor-importacion">
                <p class="texto-contenedor">Se muestra un sencillo historial de importaciones recientes.</p>

                <div class="contenedores-informacion">

                    <div class="contenedor-tablas-contenido">

                        <div class="contenedor-botones-accion-tabla">
                            <button type="button" class="btn-accion-tabla" id="btn-refrescar-historial">
                                <span><i class="fa-solid fa-rotate"></i></span>
                                <span class="spinner-tabla"></span>
                            </button>
                        </div>

                        
                        <div class="tabla-scroll">
                            <div class="contenedor-tabla">
                                <table class="tabla-contenido" id="tabla-historial-importaciones"
                                    data-url-tabla-listado-historial-simple-importaciones-asistencia="{{ route('grup_personal.grup_modulo_importacion.name_historial_simple_importaciones_asistencia') }}"
                                    data-url-tabla-ver-detalles-importacion-asistencia-simple="{{ route('grup_personal.grup_modulo_importacion.name_ver_detalles_importacion_asistencia_simple', ['id' => '__ID__']) }}"
                                    data-url-tabla-descargar-archivo-importacion-asistencia-simple="{{ route('grup_personal.grup_modulo_importacion.name_descargar_archivo_importacion_asistencia_simple', ['id' => '__ID__']) }}">
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

                    </div>

                    <p class="texto-contenedor">Historial completo en <b>Auditoría y seguridad</b>.</p>

                </div>

            </div>
        </div>


        <!-- INFO DE IMPORTACION ASISTENCIA -->
        <div class="contenedor">
            <h2 class="titulo-contenedor"><span>Información de la importación de asistencia</span></h2>

            <div class="contenedores-informacion">

                <p class="texto">El personal que tenga acceso a [<i class="fa-solid fa-caret-right"></i> Importación de asistencia] podrá realizar la subida de archivos exportados por relojes checadores, para el registro de la asistencia de los estudiantes.</p> 

                <p class="texto">Todo esto por medio de un documento (<span class="span-tipo-archivo excel">.xls</span> / <span class="span-tipo-archivo">.xlsx, CSV, u otro</span>), que <b>no debe de exceder de un tamaño de 10 MB, que es igual a 10240 KB.</b> A continuación se da una breve información que es importante saber:</p>

                <div class="informacion-importante contenedor-informacion-leer">    
                    <button class="btn-leer-contenido">
                        <i class="fa-solid fa-volume-high"></i>
                    </button>

                    <h3 class="titulo-texto texto-objetivo-leer">REGLA GENERAL DEL ARCHIVO:</h3>    

                    <p class="texto texto-objetivo-leer">
                        <b>1.</b> Solo se permiten archivos extraidos de los Relojes checadores.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>2.</b> Los archivos a importar depende de que su Reloj checador este disponible para la Importación de asistencia. <span class="palabra-resaltada">Debido a que cada reloj maneja su propio parsers (analizador), </span> el cual le ayuda a extraer la información de su archivo correspondiente.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>3.</b> El archivo no debe de exceder de un tamaño de 10 MB, que es igual a 10240 KB.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>4.</b> El archivo no debe de estar mal configurado/optimizado, inflado o sucio internamente.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>5.</b> Si el archivo tiene demasiados estilos internos puede agotar recursos y hacer más lento el proceso.
                    </p>

                    <p class="texto texto-objetivo-leer">
                        <b>6.</b> Si el contenido del archivo es grande, el procesamiento podría ser más lento o incluso no se podra efectuar. Por ello verifica que este en orden.
                    </p>
                    
                </div>

                <p class="texto">Si deseas registrar mas Relojes checadores, solicita apoyo del personal Administrativo de la plataforma.</p>
                <p class="texto">Comando para el envio alertar por correo: php artisan queue:work <b>--queue=correos_estudiantes,default -vvv</b></p>
            </div>
        </div>

    </div>

@endsection 
<!--FIN DEL CONTENIDO DE LA PAGINA-->

<!--INDICADOR DE QUE ESTA PAGINA UTILIZARA JS PARA ELLA SOLA-->
@push('scripts')
<script src="{{ asset('js/personal/modulo_importacion/funcion_formulario_importacion_asistencia.js') }}"></script>
<script src="{{ asset('js/personal/modulo_importacion/mostrar_requisitos_formulario_importacion_asistencia.js') }}"></script>
<script src="{{ asset('js/personal/modulo_importacion/funcion_historial_simple_importaciones_asistencia.js') }}"></script>
@endpush