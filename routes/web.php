<?php

use Illuminate\Support\Facades\Route;


use App\Http\Controllers\Estudiante\LoginEstudiantesController;
use App\Http\Controllers\Estudiante\ActivacionCuentaEstudianteController;

use App\Http\Controllers\Estudiante\PanelEstudiante\PanelEstudianteController;

use App\Http\Controllers\Estudiante\RestablecerContrasenaEstudiante\RestablecerContrasenaEstudianteController;

use App\Http\Controllers\Estudiante\MisAlertas\AlertasAsistenciaEstudianteController;

use App\Http\Controllers\Estudiante\MiAsistenciaEstudiante\MiAsistenciaEstudianteController;

use App\Http\Controllers\Estudiante\Justificantes\JustificantesEstudianteController;




use App\Http\Controllers\Personal\LoginPersonalController;

use App\Http\Controllers\Personal\RestablecerContrasenaPersonal\RestablecerContrasenaPersonalController;

use App\Http\Controllers\Personal\Plantillas\PlantillaDatosEscolaresController;

use App\Http\Controllers\Personal\PanelPersonal\PanelPersonalController;

use App\Http\Controllers\Personal\ModuloImportacion\ImportacionAsistenciaController;
use App\Http\Controllers\Personal\ModuloImportacion\HistorialImportacionesAsistenciaController;
use App\Http\Controllers\Personal\ModuloImportacion\ImportacionDatosEscolaresController;
use App\Http\Controllers\Personal\ModuloImportacion\HistorialImportacionesDatosEscolaresController;

use App\Http\Controllers\Personal\PersonalAcceso\GestionAdministradoresController;

use App\Http\Controllers\Personal\PersonalAcceso\RegistrarPersonalSaaeController;
use App\Http\Controllers\Personal\PersonalAcceso\CrearRolesPersonalSaaeController;
use App\Http\Controllers\Personal\PersonalAcceso\CrearPermisosRolesSaaeController;
use App\Http\Controllers\Personal\PersonalAcceso\ListadoRolesPermisosController;
use App\Http\Controllers\Personal\PersonalAcceso\ListadoPersonalSaaeController;

use App\Http\Controllers\Personal\Estudiantes\RegistrarEstudiantesSaaeController;
use App\Http\Controllers\Personal\Estudiantes\ListadoEstudiantesSaaeController;

use App\Http\Controllers\Personal\AsistenciaEstudiantes\AsistenciaEstudiantesController;

use App\Http\Controllers\Personal\Justificantes\JustificantesPersonalController;

use App\Http\Controllers\Personal\Alertas\AlertasAsistenciaController;

use App\Http\Controllers\Personal\Configuracion\CatalogosAcademicos\ListadoAreasEspecialidadEstatusEscolar;
use App\Http\Controllers\Personal\Configuracion\CatalogosAcademicos\RegistrarAreasEspecialidadEstatusEscolar;

use App\Http\Controllers\Personal\Configuracion\RelojesChecadores\RegistrarRelojesChecadoresParsersController;
use App\Http\Controllers\Personal\Configuracion\RelojesChecadores\ListadoRelojesChecadoresParsersController;

use App\Http\Controllers\Personal\Configuracion\FuentesDatos\RegistrarFuentesDatosParsersController;
use App\Http\Controllers\Personal\Configuracion\FuentesDatos\ListadoFuentesDatosParsersController;

use App\Http\Controllers\Personal\AuditoriaSeguridad\HistorialImportacionesAsistenciaDatosEscolaresController;

use App\Http\Controllers\Personal\Estudiantes\GenerarCorreoInstitucionalEstudianteController;




//==================RUTAS DE LA BARRA DE NAVEGACION PRINCIPAL - PUBLICA==================//

    //===================================PAGINA DE INICIO===================================
    Route::get('/', function () {
        return view('index');
    })->name('name_inicio');
    //=======================================================================================


    //=============================SECCION PUBLICA DE ESTUDIANTES=============================
    //no ulvidar el punto (.) al final del nombre del grupo
    Route::prefix('estudiante')->name('grup_estudiante.')->middleware(['no.cache'])->group(function () {
        Route::view('/informacion_inicial', 'informacion_inicial_estudiantes')->name('name_informacion_inicial_estudiantes');

        Route::view('/login_estudiante', 'estudiantes.login_estudiante')->name('name_login_estudiante');

        Route::post('/iniciar_sesion_estudiante', [LoginEstudiantesController::class, 'iniciar_sesion_estudiante'])->name('name_iniciar_sesion_estudiante');


        //==========Activacion de cuenta del estudiante
        //mostrar formulario para activar la cuenta
        Route::get('/activar_mi_cuenta_estudiante', [ActivacionCuentaEstudianteController::class, 'mostrar_formulario_activacion'])->name('name_mostrar_formulario_activacion_cuenta_estudiante');

        //enviar formulario para activar la cuenta
        Route::post('/enviar_activacion_mi_cuenta_estudiante', [ActivacionCuentaEstudianteController::class, 'activar_cuenta'])->name('name_activar_cuenta_estudiante');


        //==========Recuperar y restablecer la contraseña del estudiante
        Route::post('/enlace_recuperar_contrasena_estudiante',[RestablecerContrasenaEstudianteController::class, 'enviar_correo_enlace_recuperacion_contrasena_estudiante'])->middleware('throttle:recuperar-contrasena-estudiante')->name('enviar_correo_enlace_recuperacion_contrasena_estudiante'); 
        //->middleware('throttle:recuperar-contrasena-estudiante')   es limitador de peticiones, en este caso para que no esten pidiendo enlaces de recuperacion una y otra vez. Se encuentra en app/Providers/AppServiceProvider.php

        Route::get('/restablecer_contrasena_estudiante/{token}',[RestablecerContrasenaEstudianteController::class, 'mostrar_formulario_restablecer_contrasena_estudiante'])->name('mostrar_formulario_restablecer_contrasena_estudiante');

        Route::post('/restablecer_contrasena_estudiante',[RestablecerContrasenaEstudianteController::class, 'actualizar_contrasena_estudiante'])->name('actualizar_contrasena_estudiante');
    });
    //=======================================================================================


    //==============================SECCION PUBLICA DE PERSONAL==============================
        Route::prefix('personal')->name('grup_personal.')->middleware(['no.cache'])->group(function () {
            Route::view('/informacion_inicial', 'informacion_inicial_personal')->name('name_informacion_inicial_personal');

            Route::view('/login_personal', 'personal.login_personal')->name('name_login_personal');

                                                                                    //->middleware('guest:personal') --- bloquea a los logueados (para que no vuelvan al login).
            Route::post('/iniciar_sesion_personal', [LoginPersonalController::class, 'iniciar_sesion_personal'])->middleware('throttle:10,1')->name('name_iniciar_sesion_personal');                        //anti fuerza bruta, (10 intentos por minuto) es ->middleware('throttle:10,1')

            //==========Recuperar y restablecer la contraseña del personal
            Route::post('/enlace_recuperar_contrasena_personal',[RestablecerContrasenaPersonalController::class, 'enviar_correo_enlace_recuperacion_contrasena_personal'])->middleware('throttle:recuperar-contrasena-personal')->name('enviar_correo_enlace_recuperacion_contrasena_personal'); 
            //->middleware('throttle:recuperar-contrasena-personal')   es limitador de peticiones, en este caso para que no esten pidiendo enlaces de recuperacion una y otra vez. Se encuentra en app/Providers/AppServiceProvider.php

            Route::get('/restablecer_contrasena_personal/{token}',[RestablecerContrasenaPersonalController::class, 'mostrar_formulario_restablecer_contrasena_personal'])->name('mostrar_formulario_restablecer_contrasena_personal');

            Route::post('/restablecer_contrasena_personal',[RestablecerContrasenaPersonalController::class, 'actualizar_contrasena_personal'])->name('actualizar_contrasena_personal');
    });
    //=======================================================================================


    //===================================PAGINA DE ALERTAS===================================
    Route::get('alertas', function () {
        // Es la URL publica que ve el usuario en el navegador
        // Puede cambiar sin afectar la lógica interna de Laravel
        // pero debe ser clara y entendible para navegacion

        return view('alertas');
        // Apunta a la vista Blade dentro de [resources/views]. En este caso: alertas.blade.php
        // Si estuviera dentro de carpetas se usa la notación: carpeta.subcarpeta.archivo (el nombre de la carpeta termina con punto . )
        // Representa la ruta completa del archivo Blade

    })->name('name_alertas');
    // Es un identificador interno unico (como una CURP)
    // Se usa para generar Direcciones con route('name_alertas') en los enlaces o en este caso en la Barra de navegacion     
    // Si se cambia, debe actualizarse en todos los lugares donde se utilice
    //=======================================================================================


    //==================================PAGINA DE SEGURIDAD==================================
    Route::get('seguridad', function () {
        return view('seguridad');
    })->name('name_seguridad');
    //=======================================================================================


    //==================================PAGINA DE CONTACTO==================================
    Route::get('contacto', function () {
        return view('contacto');
    })->name('name_contacto');
    //=======================================================================================


    //=================================SECCION DE SOBRE SAAE=================================
    //no ulvidar el punto (.) al final del nombre del Seccion
    Route::prefix('sobre_saae')->name('grup_sobre_saae.')->group(function () {

        Route::view('/descripcion_general', 'sobre_saae.descripcion_general')
            ->name('name_descripcion_general');

        Route::view('/objetivo_academico', 'sobre_saae.objetivo_academico')
            ->name('name_objetivo_academico');

            //Sub Seccion: Tecnologías utilizadas
            Route::prefix('tecnolgias_utilizadas')->name('sub_grup_tecnologias_utilizadas.')->group(function () {

                Route::view('/backend_frontend_bd', 'sobre_saae.tecnologias_utilizadas.backend_frontend_bd')
                    ->name('name_backend_frontend_bd');

                Route::view('/reloj_checador', 'sobre_saae.tecnologias_utilizadas.reloj_checador')
                    ->name('name_reloj_checador');

                Route::view('/wifi_institucional', 'sobre_saae.tecnologias_utilizadas.wifi_institucional')
                    ->name('name_wifi_institucional');
            });

        Route::view('/desarrolador', 'sobre_saae.desarrollador_saae')
            ->name('name_desarrollador_saae');
    });
    //=======================================================================================

//==========FIN DE LAS RUTAS DE LA BARRA DE NAVEGACION PRINCIPAL - PUBLICA===============//



//======================RUTAS DEL PIE DE PAGINA PRINCIPAL - PUBLICA======================//

    //==============================SECCION DE SOBRE NOSOTROS==============================
    Route::prefix('sobre_nosotros')->name('grup_sobre_nosotros.')->group(function () {

        Route::view('/nuestra_historia', 'pie_pagina.nuestra_historia')
            ->name('name_nuestra_historia');
        
        Route::view('/mision_vision', 'pie_pagina.mision_vision')
            ->name('name_mision_vision');
        
        Route::view('/valores', 'pie_pagina.valores')
            ->name('name_valores');
    });
    //=======================================================================================


    //=============================SECCION DE INFORMACION LEGAL=============================
    //no ulvidar el punto (.) al final del nombre del Seccion
    Route::prefix('informacion_legal')->name('grup_informacion_legal.')->group(function () {

        Route::view('/terminos_condiciones', 'pie_pagina.terminos_condiciones')
            ->name('name_terminos_condiciones');

        Route::view('/aviso_privacidad', 'pie_pagina.aviso_privacidad')
            ->name('name_aviso_privacidad');

        Route::view('/politica_cookies', 'pie_pagina.politica_cookies')
            ->name('name_politica_cookies');

        Route::view('/informacion_institucional', 'pie_pagina.informacion_institucional')
            ->name('name_informacion_institucional');
    });
    //=======================================================================================

//==============FIN DE LAS RUTAS DEL PIE DE PAGINA PRINCIPAL - PUBLICA===================//




//==================================SECCION DEL ESTUDIANTE==================================    

    Route::prefix('estudiante')
        ->name('grup_estudiante.')
        ->middleware('auth:estudiante','no.cache')
        ->group(function () {

            //==========Cerrar la sesion del Personar que quiera (sin importar su rol y permiso)
            Route::post('/cerrar_sesion_personal', [LoginEstudiantesController::class, 'cerrar_sesion_estudiante'])
                ->name('name_cerrar_sesion_estudiante');

            //==========Panel del estudiante
            Route::get('/panel_estudiante', [PanelEstudianteController::class, 'ver_panel_estudiante'])->name('name_panel_estudiante');


            //==========Seccion [Mi asistencia]
            Route::prefix('asistencia_estudiante')
                ->name('grup_asistencia_estudiante.')
                ->group(function () {

                    Route::get('/asistencia_reciente', [MiAsistenciaEstudianteController::class, 'ver_asistencia_reciente'])->name('name_asistencia_reciente');

                    Route::get('/historial_asistencia_estudiante', [MiAsistenciaEstudianteController::class, 'ver_hitorial_asistencia_estudiante'])->name('name_historial_asistencia_estudiante');


                    //----------Sub seccion de [Asistencias recientes]
                    Route::get('/tabla_asistencia_reciente', [MiAsistenciaEstudianteController::class, 'tabla_asistencia_reciente'])->name('name_tabla_asistencia_reciente');

                    Route::get('/resumen_asistencia_reciente', [MiAsistenciaEstudianteController::class, 'resumen_asistencia_reciente'])->name('name_resumen_asistencia_reciente');

                    Route::get('/detalle_asistencia_estudiante', [MiAsistenciaEstudianteController::class, 'detalle_asistencia_estudiante'])->name('name_detalle_asistencia_estudiante');

                    //----------Sub seccion de [Historial/consultas de asistencia]
                    Route::get('/tabla_historial_asistencia_estudiante', [MiAsistenciaEstudianteController::class, 'tabla_historial_asistencia_estudiante'])->name('name_tabla_historial_asistencia_estudiante');

            });


            //==========Seccion [Justificantes]
            Route::prefix('justificantes')
                ->name('grup_justificantes.')
                ->group(function () {
                    Route::view('/bandeja_justificantes', 'estudiantes.justificantes.bandeja_justificantes')->name('name_bandeja_justificantes');

                    Route::get('/tabla_justificantes', [JustificantesEstudianteController::class, 'tabla_justificantes'])->name('name_tabla_justificantes');

                    Route::get('/faltas_disponibles', [JustificantesEstudianteController::class, 'faltas_disponibles'])->name('name_faltas_disponibles_justificante');

                    Route::post('/guardar_enviar_justificante', [JustificantesEstudianteController::class, 'guardar_enviar_justificante'])->name('name_guardar_enviar_justificante');

                    Route::get('/ver_justificante/{id}', [JustificantesEstudianteController::class, 'ver_justificante'])
                    ->name('name_ver_justificante');
            });


            //==========Seccion [Mis Alertas]
            Route::prefix('alertas')
                ->name('grup_alertas.')
                ->group(function () {
                    Route::get('/alertas', [AlertasAsistenciaEstudianteController::class, 'alertas'])->name('name_alertas');

                    Route::get('/historial_alertas', [AlertasAsistenciaEstudianteController::class, 'historial_alertas'])->name('name_historial_alertas');


                    //Tabla de Alertas
                    Route::get('/tabla_alertas', [AlertasAsistenciaEstudianteController::class, 'tabla_alertas'])->name('name_alertas_tabla');
                    Route::get('/resumen_alerta', [AlertasAsistenciaEstudianteController::class, 'resumen_alertas'])->name('name_alertas_resumen');
                    Route::get('/ver_alerta/{id}', [AlertasAsistenciaEstudianteController::class, 'ver_alerta'])->name('name_ver_alerta');


                    //Tabla de historial de Alertas
                    Route::get('/tabla_historial_alertas', [AlertasAsistenciaEstudianteController::class, 'tabla_historial_alertas'])->name('name_historial_alertas_tabla');
                    Route::get('/tabla_resumen_historial', [AlertasAsistenciaEstudianteController::class, 'resumen_historial_alertas'])->name('name_historial_alertas_resumen');
            });

        });

//===============================FIN SECCION DEL PERSONAL===============================//




//==================================SECCION DEL PERSONAL==================================    

    //============RUTAS DE TODO EL PARSONAL CON ROL (Y SUS PERMISOS), Y 1 SIN ROL============
    Route::prefix('personal')
        ->name('grup_personal.')
        ->middleware('auth:personal','no.cache')
        ->group(function () {


            //==========Cerrar la sesion del Personar que quiera (sin importar su rol y permiso)
            Route::post('/cerrar_sesion_personal', [LoginPersonalController::class, 'cerrar_sesion_personal'])
                ->name('name_cerrar_sesion_personal');


            //==========Pagina de espera para el PERSONAL SIN ROL
            Route::view('/inicio_personal_sin_rol', 'personal.inicio_personal_sin_rol')->name('name_inicio_personal_sin_rol');


            Route::view('/inicio_personal', 'personal.inicio_personal')->name('name_inicio_personal');


            //==========Panel del PERSONAL CON ROL
            Route::get('/panel_personal', [PanelPersonalController::class, 'ver_panel_personal'])->middleware('permiso:panel_personal.ver')->name('name_panel_personal');
            Route::get('/exportar_excel_dashboard',[PanelPersonalController::class, 'exportar_excel_dashboard'])->name('name_exportar_excel_dashboard');


            //==========Plantilla en Excel para IMPORTACIÓN DE DATOS ESCOLARES: como debe de ser el archivo, orden, datos, etc.
            Route::get('/ver_plantilla_datos_escolares', [PlantillaDatosEscolaresController::class, 'ver_plantilla_datos_escolares'])
                ->name('name_ver_plantilla_datos_escolares');

            Route::get('/descargar_plantilla_datos_escolares', [PlantillaDatosEscolaresController::class, 'descargar_plantilla_datos_escolares'])
                ->name('name_descargar_plantilla_datos_escolares');


            //==========Seccion [Modulo de importacion], no ulvidar el punto (.) al final del nombre del Seccion
            Route::prefix('modulo_importacion')
                ->name('grup_modulo_importacion.')
                ->middleware('permiso:modulo_importacion.ver')
                ->group(function () {

                    //----------Sub seccion de [Importacion de asistencia]
                    Route::get('/importacion_asistencia', [ImportacionAsistenciaController::class, 'ver_importacion_asistencia'])->name('name_importacion_asistencia');
                    Route::post('/ejecutar_importacion_asistencia', [ImportacionAsistenciaController::class, 'ejecutar_importacion_asistencia'])->name('ejecutar_importacion_asistencia');

                    Route::get('/historial_simple_importaciones_asistencia', [HistorialImportacionesAsistenciaController::class, 'historial_simple_importaciones_asistencia'])->name('name_historial_simple_importaciones_asistencia');
                    Route::get('/ver_detalles_importacion_asistencia_simple/{id}', [HistorialImportacionesAsistenciaController::class,'ver_detalles_importacion_asistencia_simple'])->name('name_ver_detalles_importacion_asistencia_simple');
                    Route::get('/descargar_archivo_importacion_asistencia_simple/{id}', [HistorialImportacionesAsistenciaController::class, 'descargar_archivo_importacion_asistencia_simple'])->name('name_descargar_archivo_importacion_asistencia_simple');
                    //----------


                    //----------Sub seccion de [Importacion de datos escolares]
                    Route::get('/importacion_datos_escolares', [ImportacionDatosEscolaresController::class, 'ver_importacion_datos_escolares'])->name('name_importacion_datos_escolares');
                    Route::post('/ejecutar_importacion_datos_escolares', [ImportacionDatosEscolaresController::class, 'ejecutar_importacion_datos_escolares'])->name('ejecutar_importacion_datos_escolares');

                    Route::get('/historial_simple_importaciones_datos_escolares', [HistorialImportacionesDatosEscolaresController::class, 'historial_simple_importaciones_datos_escolares'])->name('name_historial_simple_importaciones_datos_escolares');
                    Route::get('/ver_detalles_importacion_datos_escolares/{id}', [HistorialImportacionesDatosEscolaresController::class,'ver_detalles_importacion_datos_escolares_simple'])->name('name_ver_detalles_importacion_datos_escolares');
                    Route::get('/descargar_archivo_importacion_datos_escolares/{id}', [HistorialImportacionesDatosEscolaresController::class, 'descargar_archivo_importacion_datos_escolares_simple'])->name('name_descargar_archivo_importacion_datos_escolares');
                    //----------
            });

            //==========Seccion [Estudiantes]
            Route::prefix('estudiantes')
                ->name('grup_estudiantes.')
                ->middleware('permiso:estudiantes.ver')//--->Permiso para acceder a este Seccion (seccion en la barra de navegacion)
                ->group(function () {

                    //----------Sub seccion de [Gestion de estudiantes]
                    Route::get('/gestion_estudiantes', [RegistrarEstudiantesSaaeController::class, 'cargar_area_especialidad_estatus_personal_estudiante'])->name('name_gestion_estudiantes');
                    Route::post('/registrar_estudiante', [RegistrarEstudiantesSaaeController::class, 'registrar_estudiante'])->name('name_registrar_estudiante.registrar_estudiante');

                    //listado
                    Route::get('/listado_estudiantes', [ListadoEstudiantesSaaeController::class, 'listado_estudiantes'])
                        ->name('name_tabla_listado_estudiante');

                    //generar correo y enviar enlace de activacion o reenviar
                    Route::post('/generar_correo_institucional_estudiante/{id}', [GenerarCorreoInstitucionalEstudianteController::class, 'generar_correo_institucional_estudiante'])
                        ->name('name_generar_correo_institucional_estudiante');

                    Route::post('/generar_correos_institucionales_pendientes', [GenerarCorreoInstitucionalEstudianteController::class, 'generar_correos_institucionales_pendientes'])
                        ->name('name_generar_correos_institucionales_pendientes');

                    Route::post('/reenviar_activacion_cuenta_estudiante/{id}', [GenerarCorreoInstitucionalEstudianteController::class, 'reenviar_activacion_cuenta_estudiante'])->name('name_reenviar_activacion_cuenta_estudiante');

                    Route::post('/reenviar_activaciones_cuentas_pendientes', [GenerarCorreoInstitucionalEstudianteController::class, 'reenviar_activaciones_cuentas_pendientes'])->name('name_reenviar_activaciones_cuentas_pendientes');

                    //ver sus datos escolares
                    Route::get('/ver_datos_escolares_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'ver_datos_escolares_estudiante'])
                        ->name('name_ver_datos_escolares_estudiante');

                    //asignar estudiantes a personal
                    Route::get('/ver_asignaciones_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'ver_asignaciones_estudiante']) ->name('name_ver_asignaciones_estudiante');
                    Route::post('/guardar_asignacion_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'guardar_asignacion_estudiante'])
                        ->name('name_guardar_asignacion_estudiante');
                    Route::put('/desactivar_asignacion_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'desactivar_asignacion_estudiante'])
                        ->name('name_desactivar_asignacion_estudiante');
                    Route::put('/reactivar_asignacion_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'reactivar_asignacion_estudiante'])->name('name_reactivar_asignacion_estudiante');
                    Route::delete('/eliminar_asignacion_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'eliminar_asignacion_estudiante'])->name('name_eliminar_asignacion_estudiante');

                    //editar estudiante
                    Route::get('/ver_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'ver_estudiante'])
                        ->name('name_ver_estudiante');
                    Route::put('/editar_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'editar_estudiante'])
                        ->name('name_editar_estudiante');

                    //eliminar estudiante
                    Route::delete('/eliminar_estudiante/{id}', [ListadoEstudiantesSaaeController::class, 'eliminar_estudiante'])
                        ->name('name_eliminar_estudiante');

                    //exportar excel
                    Route::get('/exportar_estudiantes_excel',[ListadoEstudiantesSaaeController::class, 'exportar_estudiantes_excel'])->name('name_exportar_estudiantes_excel');

                    Route::view('/dispositivos_autorizados', 'personal.estudiantes.dispositivos_autorizados')->name('name_dispositivos_autorizados');
            });


            //==========Seccion [Asistencia de estudiante]
            Route::prefix('asistencia_estudiantes')
                ->name('grup_asistencia_estudiantes.')
                ->middleware('permiso:asistencia_estudiantes.ver')
                ->group(function () {

                    //----------Sub seccion de [Asistencias recientes]
                    Route::get('/asistencias_recientes', [AsistenciaEstudiantesController::class, 'ver_asistencia_reciente'])->name('name_asistencia_reciente');
                    Route::get('/tabla_asistencia_reciente', [AsistenciaEstudiantesController::class, 'tabla_asistencia_reciente'])->name('name_tabla_asistencia_reciente');
                    Route::get('/resumen_asistencia_reciente', [AsistenciaEstudiantesController::class, 'resumen_asistencia_reciente'])->name('name_resumen_asistencia_reciente');
                    Route::get('/destalle_asistencia_estudiante/{id}', [AsistenciaEstudiantesController::class, 'detalle_asistencia_estudiante'])->name('name_detalle_asistencia_estudiante');
                    Route::get('/exportar_asistencia_reciente', [AsistenciaEstudiantesController::class, 'exportar_asistencia_reciente_excel'])->name('name_exportar_asistencia_reciente_excel');
                    Route::get('/exportar_historial_completo_asistencia', [AsistenciaEstudiantesController::class, 'exportar_historial_completo_asistencia_excel'])->name('name_exportar_historial_completo_asistencia_excel');


                    //----------Sub seccion de [Historial/consultas de asistencia]
                    Route::get('/hitorial_asistencia_estudiante', [AsistenciaEstudiantesController::class, 'ver_hitorial_asistencia_estudiante'])->name('name_hitorial_asistencia_estudiante');
                    Route::get('/listado_estudiantes_asignados', [AsistenciaEstudiantesController::class, 'listado_estudiantes_asignados_tabla_historial_asistencia'])->name('name_listado_estudiantes_asignados_tabla_historial_asistencia');
                    Route::get('/detalle_tabla_historial_asistencia_estudiante/{id}', [AsistenciaEstudiantesController::class, 'detalle_tabla_historial_asistencia_estudiante'])->name('name_detalle_tabla_historial_asistencia_estudiante');
                    Route::get('/exportar_historial_asistencia', [AsistenciaEstudiantesController::class, 'exportar_historial_asistencia_excel'])->name('name_exportar_historial_asistencia_excel');
            });


            //==========Seccion [Justificantes]
            Route::prefix('justificantes')
                ->name('grup_justificantes.')
                ->middleware('permiso:justificantes.ver')
                ->group(function () {
                    Route::view('/bandeja_justificantes', 'personal.justificantes.bandeja_justificantes')->name('name_bandeja_justificantes');

                    Route::get('/tabla_justificantes', [JustificantesPersonalController::class, 'tabla_justificantes'])->name('name_tabla_justificantes');

                    Route::get('/ver_justificante/{id}', [JustificantesPersonalController::class, 'ver_justificante'])->name('name_ver_justificante');

                    Route::post('/aprobar_justificante/{id}', [JustificantesPersonalController::class, 'aprobar_justificante'])->name('name_aprobar_justificante');

                    Route::post('/rechazar_justificante/{id}', [JustificantesPersonalController::class, 'rechazar_justificante'])->name('name_rechazar_justificante');
            });


            //==========Seccion [Alertas] ---FALTA PLANEACION SOBRE ESTO, sobre si dejarlo dentro del admin o en personal
            Route::prefix('alertas')
                ->name('grup_alertas.')
                ->middleware('permiso:alertas.ver')
                ->group(function () {
                    Route::get('/alertas', [AlertasAsistenciaController::class, 'alertas'])->name('name_alertas');

                    Route::get('/historial_alertas', [AlertasAsistenciaController::class, 'historial_alertas'])->name('name_historial_alertas');


                    //Tabla de Alertas
                    Route::get('/tabla_alertas', [AlertasAsistenciaController::class, 'tabla_alertas'])->name('name_alertas_tabla');

                    Route::get('/resumen_alerta', [AlertasAsistenciaController::class, 'resumen_alertas'])->name('name_alertas_resumen');

                    Route::get('/ver_alerta/{id}', [AlertasAsistenciaController::class, 'ver_alerta'])->name('name_ver_alerta');

                    Route::post('/atender_alerta/{id}', [AlertasAsistenciaController::class, 'atender_alerta'])->name('name_atender_alerta');
                    //->middleware('permiso:alertas.gestionar')

                    Route::post('/cerrar_alerta/{id}', [AlertasAsistenciaController::class, 'cerrar_alerta'])->name('name_cerrar_alerta');
                    //->middleware('permiso:alertas.gestionar')


                    //Tabla de historial de Alertas
                    Route::get('/tabla_historial_alertas', [AlertasAsistenciaController::class, 'tabla_historial_alertas'])->name('name_historial_alertas_tabla');

                    Route::get('/tabla_resumen_historial', [AlertasAsistenciaController::class, 'resumen_historial_alertas'])->name('name_historial_alertas_resumen');
            });


            //==========Seccion [Reportes y exportacion]


            //==========Seccion [Auditoria y Seguridad]
            Route::prefix('auditoria_seguridad')
                ->name('grup_auditoria_seguridad.')
                ->middleware('permiso:auditoria_seguridad.ver')
                ->group(function () {
                    //----------Sub seccion de [Importacion de asistencia]
                    Route::get('/importaciones', [HistorialImportacionesAsistenciaDatosEscolaresController::class, 'ver_historial_modulo_importaciones'])->name('name_ver_historial_modulo_importaciones');
                    
                    //Tabla de importaciones de asistencia
                    Route::get('/historial_importaciones_asistencia', [HistorialImportacionesAsistenciaDatosEscolaresController::class, 'historial_importaciones_asistencia'])->name('name_historial_importaciones_asistencia');
                    Route::get('/ver_detalles_importacion_asistencia/{id}', [HistorialImportacionesAsistenciaDatosEscolaresController::class,'ver_detalles_importacion_asistencia'])->name('name_ver_detalles_importacion_asistencia');
                    Route::get('/descargar_archivo_importacion_asistencia/{id}', [HistorialImportacionesAsistenciaDatosEscolaresController::class, 'descargar_archivo_importacion_asistencia'])->name('name_descargar_archivo_importacion_asistencia');

                    //Tabla de importaciones de datos escolares
                    Route::get('/historial_importaciones_datos_escolares', [HistorialImportacionesAsistenciaDatosEscolaresController::class, 'historial_importaciones_datos_escolares'])->name('name_historial_importaciones_datos_escolares');
                    Route::get('/ver_detalles_importacion_datos_escolares/{id}', [HistorialImportacionesAsistenciaDatosEscolaresController::class,'ver_detalles_importacion_datos_escolares'])->name('name_ver_detalles_importacion_datos_escolares');
                    Route::get('/descargar_archivo_importacion_datos_escolares/{id}', [HistorialImportacionesAsistenciaDatosEscolaresController::class, 'descargar_archivo_importacion_datos_escolares'])->name('name_descargar_archivo_importacion_datos_escolares');
                    //----------

            });


            //==========Seccion [Gui/Manual]
            Route::prefix('guia_manual')->
                name('grup_guia_manual.')
                ->middleware('permiso:guia_manual_personal.ver')
                ->group(function () {

                Route::view('/guia_manual_admin', 'personal.guia_manual.guia_manual_admin')->name('name_guia_manual_admin');

                Route::view('/guia_manual_personal', 'personal.guia_manual.guia_manual_personal')->name('name_guia_manual_personal');
            });

    });
    //=======================================================================================


    //===================RUTAS PRIVADAS PARA EL PERSONAL CON EL ROL ADMIN===================
    Route::prefix('admin')
        ->name('grup_admin.') // no ulvidar el punto (.) al final del nombre del grupo
        ->middleware('auth:personal','role:admin','no.cache')
        ->group(function () {   

            //Route::view('/inicio_admin', 'personal.inicio_admin')->name('name_inicio_admin');

            //Route::view('/vista_correo_registro_personal', 'emails.personal.vista_registro')->name('name_vista_correo_registro_personal');

            //==========Seccion [Personal y acceso]
            Route::prefix('personal_acceso')->name('grup_personal_acceso.')->group(function () {

                //----------Seccion de gestion para los administradores
                    Route::get('/gestion_administradores', [GestionAdministradoresController::class, 'ver_tabla_administradores'])->name('name_gestion_administradores');

                    Route::get('/tabla_listado_administradores', [GestionAdministradoresController::class, 'listado_administradores'])->name('name_tabla_listado_administradores');

                    Route::get('/ver_roles_administrador/{id}', [GestionAdministradoresController::class, 'ver_roles_administrador'])->name('name_ver_roles_administrador');

                    Route::get('/ver_administrador/{id}', [GestionAdministradoresController::class, 'ver_administrador'])->name('name_ver_administrador');
                    Route::put('/editar_administrador/{id}', [GestionAdministradoresController::class,'editar_administrador'])->name('name_editar_administrador');
                    Route::delete('/eliminar_administrador/{id}', [GestionAdministradoresController::class,'eliminar_administrador'])->name('name_eliminar_administrador');
                //----------

                //----------Sub seccion de [Roles y permisos]
                    Route::get('/roles_permisos', [ListadoRolesPermisosController::class, 'cargar_roles_permisos_disponibles'])->name('name_roles_permisos');

                    //Tabla de listado de Roles
                    Route::post('/crear_roles', [CrearRolesPersonalSaaeController::class, 'crear_roles'])->name('name_crear_roles_personal.crear_roles');

                    Route::get('/listado_roles', [ListadoRolesPermisosController::class, 'listado_roles'])->name('name_listado_roles');
                    Route::get('/ver_permisos_rol/{id}', [ListadoRolesPermisosController::class, 'ver_permisos_rol'])->name('name_ver_permisos_rol');
                    Route::get('/ver_rol/{id}', [ListadoRolesPermisosController::class, 'ver_rol'])->name('name_ver_rol');
                    Route::put('/editar_rol/{id}', [ListadoRolesPermisosController::class,'editar_rol'])->name('name_editar_rol');
                    Route::delete('/eliminar_rol/{id}', [ListadoRolesPermisosController::class,'eliminar_rol'])->name('name_eliminar_rol');

                    Route::get('/exportar_roles_excel',[ListadoRolesPermisosController::class, 'exportar_roles_excel'])->name('name_exportar_roles_excel');


                    //Tabla de listado de permisos
                    Route::post('/crear_permisos', [CrearPermisosRolesSaaeController::class, 'crear_permisos'])->name('name_crear_permisos_roles.crear_permisos');

                    Route::get('/listado_permisos', [ListadoRolesPermisosController::class, 'listado_permisos'])->name('name_listado_permisos');
                    Route::get('/ver_permiso/{id}', [ListadoRolesPermisosController::class, 'ver_permiso'])->name('name_ver_permiso');
                    Route::put('/editar_permiso/{id}', [ListadoRolesPermisosController::class,'editar_permiso'])->name('name_editar_permiso');
                    Route::delete('/eliminar_permiso/{id}', [ListadoRolesPermisosController::class,'eliminar_permiso'])->name('name_eliminar_permiso');

                    Route::get('exportar_permisos_excel',[ListadoRolesPermisosController::class, 'exportar_permisos_excel'])->name('name_exportar_permisos_excel');
                //----------

                //----------Sub seccion de [Listado del personal]
                    Route::post('/registrar_personal', [RegistrarPersonalSaaeController::class, 'registrar_personal'])->name('name_registrar_personal.registrar_personal');

                    Route::get('/listado_personal', [ListadoPersonalSaaeController::class, 'ver_tabla_personal'])->name('name_listado_personal');
                    Route::get('/tabla_listado_personal', [ListadoPersonalSaaeController::class, 'listado_personal'])->name('name_tabla_listado_personal');

                    Route::get('/ver_roles_personal/{id}', [ListadoPersonalSaaeController::class, 'ver_roles_personal'])->name('name_ver_roles_personal');

                    //ver asignaciones de personal con estudiantes
                    Route::get('/ver_estudiantes_asignados_personal/{id}', [ListadoPersonalSaaeController::class, 'ver_estudiantes_asignados_personal'])->name('name_ver_estudiantes_asignados_personal');
                    Route::put('/desactivar_asignacion_personal/{id}', [ListadoPersonalSaaeController::class, 'desactivar_asignacion_personal'])->name('name_desactivar_asignacion_personal');
                    Route::put('/reactivar_asignacion_personal/{id}', [ListadoPersonalSaaeController::class, 'reactivar_asignacion_personal'])->name('name_reactivar_asignacion_personal');
                    Route::delete('/eliminar_asignacion_personal/{id}', [ListadoPersonalSaaeController::class, 'eliminar_asignacion_personal'])->name('name_eliminar_asignacion_personal');

                    Route::get('/ver_personal/{id}', [ListadoPersonalSaaeController::class, 'ver_personal'])->name('name_ver_personal');
                    Route::put('/editar_personal/{id}', [ListadoPersonalSaaeController::class,'editar_personal'])->name('name_editar_personal');
                    Route::delete('/eliminar_personal/{id}', [ListadoPersonalSaaeController::class,'eliminar_personal'])->name('name_eliminar_personal');

                    Route::get('/personal_exportar_excel',[ListadoPersonalSaaeController::class, 'exportar_personal_excel'])->name('name_exportar_personal_excel');
                //----------
            });



            //==========Seccion [Configuracion]
            Route::prefix('configuracion')->name('grup_configuracion.')->group(function () {

                //----------Sub seccion [Cátalogos academicos]
                Route::prefix('catalogos_academicos')->name('grup_catalogos_academicos.')->group(function () {

                    Route::get('/ver_catalogos_academicos', [ListadoAreasEspecialidadEstatusEscolar::class, 'ver_catalogos_academicos'])->name('name_areas_especialidad_estatus_escolar');

                    Route::post('/registrar_areas_especialidad', [RegistrarAreasEspecialidadEstatusEscolar::class, 'registrar_areas_especialidad'])->name('name_registrar_areas_especialidad');
                    Route::post('/registrar_estatus_escolares', [RegistrarAreasEspecialidadEstatusEscolar::class, 'registrar_estatus_escolares'])->name('name_registrar_estatus_escolares');

                    //Tabla de listado de Areas de Especialidad
                    Route::get('/listado_areas_especialidad', [ListadoAreasEspecialidadEstatusEscolar::class, 'listado_areas_especialidad'])->name('name_listado_areas_especialidad');
                    Route::get('/ver_area_especialidad/{id}', [ListadoAreasEspecialidadEstatusEscolar::class, 'ver_area_especialidad'])->name('name_ver_area_especialidad');
                    Route::put('/editar_area_especialidad/{id}', [ListadoAreasEspecialidadEstatusEscolar::class,'editar_area_especialidad'])->name('name_editar_area_especialidad');
                    Route::delete('/eliminar_area_especialidad/{id}', [ListadoAreasEspecialidadEstatusEscolar::class,'eliminar_area_especialidad'])->name('name_eliminar_area_especialidad');

                    //Tabla de listado de Estatus Escolares
                    Route::get('/listado_estatus_escolares', [ListadoAreasEspecialidadEstatusEscolar::class, 'listado_estatus_escolares'])->name('name_listado_estatus_escolares');
                    Route::get('/ver_estatus_escolar/{id}', [ListadoAreasEspecialidadEstatusEscolar::class, 'ver_estatus_escolar'])->name('name_ver_estatus_escolar');
                    Route::put('/editar_estatus_escolar/{id}', [ListadoAreasEspecialidadEstatusEscolar::class,'editar_estatus_escolar'])->name('name_editar_estatus_escolar');
                    Route::delete('/eliminar_estatus_escolar/{id}', [ListadoAreasEspecialidadEstatusEscolar::class,'eliminar_estatus_escolar'])->name('name_eliminar_estatus_escolar');

                });
    

                Route::view('/integraciones', 'personal.configuracion.integraciones')->name('name_integraciones');

                //----------Sub seccion [Relojes y parsers]
                Route::prefix('relojes_parsers')->name('grup_relojes_checadores_parsers.')->group(function () {
                    Route::get('/relojes_checadores_parsers', [RegistrarRelojesChecadoresParsersController::class, 'cargar_relojes_checadores_parsers'])->name('name_relojes_checadores_parsers');

                    Route::post('/registrar_reloj_checador', [RegistrarRelojesChecadoresParsersController::class, 'registrar_reloj_checador'])->name('name_registrar_reloj_checador');
                    Route::post('/registrar_parser', [RegistrarRelojesChecadoresParsersController::class, 'registrar_parser'])->name('name_registrar_parser');

                    //Tabla de listado de Relojes Checadores
                    Route::get('/listado_relojes', [ListadoRelojesChecadoresParsersController::class, 'listado_relojes'])->name('name_listado_relojes');
                    Route::get('/ver_parsers_reloj/{id}', [ListadoRelojesChecadoresParsersController::class, 'ver_parsers_reloj'])->name('name_ver_parsers_reloj');
                    Route::get('/ver_reloj/{id}', [ListadoRelojesChecadoresParsersController::class, 'ver_reloj'])->name('name_ver_reloj');
                    Route::put('/editar_reloj/{id}', [ListadoRelojesChecadoresParsersController::class,'editar_reloj'])->name('name_editar_reloj');
                    Route::delete('/eliminar_reloj/{id}', [ListadoRelojesChecadoresParsersController::class,'eliminar_reloj'])->name('name_eliminar_reloj');
                    
                    //Tabla de listado de Parsers
                    Route::get('/listado_parsers', [ListadoRelojesChecadoresParsersController::class, 'listado_parsers'])->name('name_listado_parsers');
                    Route::get('/ver_parser/{id}', [ListadoRelojesChecadoresParsersController::class, 'ver_parser'])->name('name_ver_parser');
                    Route::put('/editar_parser/{id}', [ListadoRelojesChecadoresParsersController::class,'editar_parser'])->name('name_editar_parser');
                    Route::delete('/eliminar_parser/{id}', [ListadoRelojesChecadoresParsersController::class,'eliminar_parser'])->name('name_eliminar_parser');
                });
                //----------


                //----------Sub seccion [Fuentes de datos y parsers]
                Route::prefix('fuentes_parsers')->name('grup_fuentes_datos_parsers.')->group(function () {
                    Route::get('/fuentes_datos_parsers', [RegistrarFuentesDatosParsersController::class, 'cargar_fuentes_datos_parsers'])->name('name_fuentes_datos_parsers');

                    Route::post('/registrar_fuente_datos', [RegistrarFuentesDatosParsersController::class, 'registrar_fuente_datos'])->name('name_registrar_fuente_datos');
                    Route::post('/registrar_parser', [RegistrarFuentesDatosParsersController::class, 'registrar_parser'])->name('name_registrar_parser');

                    //Tabla de listado de Fuentes de datos
                    Route::get('/listado_fuentes_datos', [ListadoFuentesDatosParsersController::class, 'listado_fuentes_datos'])->name('name_listado_fuentes_datos');
                    Route::get('/ver_parsers_fuente_datos/{id}', [ListadoFuentesDatosParsersController::class, 'ver_parsers_fuente_datos'])->name('name_ver_parsers_fuente_datos');
                    Route::get('/ver_fuente_datos/{id}', [ListadoFuentesDatosParsersController::class, 'ver_fuente_datos'])->name('name_ver_fuente_datos');
                    Route::put('/editar_fuente_datos/{id}', [ListadoFuentesDatosParsersController::class,'editar_fuente_datos'])->name('name_editar_fuente_datos');
                    Route::delete('/eliminar_fuente_datos/{id}', [ListadoFuentesDatosParsersController::class,'eliminar_fuente_datos'])->name('name_eliminar_fuente_datos');
                    
                    //Tabla de listado de Parsers
                    Route::get('/listado_parsers', [ListadoFuentesDatosParsersController::class, 'listado_parsers'])->name('name_listado_parsers');
                    Route::get('/ver_parser/{id}', [ListadoFuentesDatosParsersController::class, 'ver_parser'])->name('name_ver_parser');
                    Route::put('/editar_parser/{id}', [ListadoFuentesDatosParsersController::class,'editar_parser'])->name('name_editar_parser');
                    Route::delete('/eliminar_parser/{id}', [ListadoFuentesDatosParsersController::class,'eliminar_parser'])->name('name_eliminar_parser');
                });
                //----------

            });

            //==========Seccion [Mantenimiento]
    });
    //=======================================================================================

//===============================FIN SECCION DEL PERSONAL===============================//


//==========Comandos Para limpiar la caché y archivos temporales en Laravel y solucionar errores de configuración o rutas==========
// php artisan optimize:clear  -> Limpia todo (caché, config, rutas, vistas).
// php artisan cache:clear  -> Borra la caché de la aplicación.
// php artisan config:clear  -> Borra la caché de configuración.
// php artisan route:clear  -> Borra la caché de rutas.
// php artisan view:clear  -> Borra la caché de vistas.


Route::get('home', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');


require __DIR__.'/settings.php';
