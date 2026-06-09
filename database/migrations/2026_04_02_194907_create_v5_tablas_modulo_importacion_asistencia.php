<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('periodos', function (Blueprint $table) {
            $table->id(); //Equival a ponerse en automatico: id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
                                                                    //UNSIGNED es un atributo para columnas numericas (enteros) que restringe los valores almacenados a numeros positivos y cero, duplicando el rango maximo positivo disponible.atributo para columnas numéricas (enteros) que restringe los valores almacenados a números positivos y cero, duplicando el rango máximo positivo disponible. Al prohibir números negativos, se optimiza el espacio y se permite almacenar valores el doble de grandes sin cambiar el tipo de dato.
                        // Esos elementos extra que se añaden al crear o modificar una tabla en SQL para definir reglas sobre los datos se llaman tecnicamente Restricciones o Constraints. 
            $table->string('nombre', 80); //requivale a: nombre VARCHAR(80) NOT NULL
            $table->date('fecha_inicio'); //equivale a: NOT NULL cuando no hay ->nullable()
            $table->date('fecha_fin');
            $table->boolean('activo')->default(false); //equivale por defecto "falso"
            $table->timestamps();
            $table->unique(['fecha_inicio','fecha_fin'], 'uq_periodo_rango'); //Equivale a: UNIQUE KEY nombre_indice (col1, col2)  que es igual a $table->unique([...], 'nombre_indice');
        });

        //TABLA NUEVA - PARA MEJORAR FUTURAS
        //Con esta los dias que cuentan ya no dependen del Excel
        Schema::create('periodo_fechas', function (Blueprint $table) {
            $table->id();

            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->restrictOnDelete();

            $table->date('fecha');
            $table->boolean('es_clase')->default(true);
            $table->enum('tipo_dia', ['CLASE', 'SUSPENSION', 'VACACIONES', 'FERIADO'])
                ->default('CLASE');
            $table->enum('origen', ['MANUAL', 'IMPORTADO', 'GENERADO'])
                ->default('GENERADO');
            $table->string('observaciones', 255)->nullable();
            $table->timestamps();

            $table->unique(['periodo_id', 'fecha'], 'uq_periodo_fecha');
        });


        Schema::create('estudiantes_saae', function (Blueprint $table) {
            $table->id();
            $table->string('numero_control', 40)->unique();
            $table->string('nombre_completo', 180)->nullable(); // viene directo del Excel
            $table->string('nombre', 120)->nullable();// para el llenado posterior/manual
            $table->string('apellidos', 120)->nullable();// para el llenado posterior/manual

            $table->string('email')->unique()->nullable();
            $table->timestamp('correo_institucional_generado_en')->nullable(); //columna nueva

            $table->string('telefono')->unique()->nullable();
            $table->string('password')->nullable();

            $table->boolean('activo')->default(false); //para que el estudiante active su cuenta
            $table->timestamp('cuenta_activada_en')->nullable();//columna nueva

            $table->timestamp('ultimo_acceso_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });


        //TABLA NUEVA
        //tabla que dice que alumnos estan activos en ese periodo
        //Porque si no se tiene una lista de alumnos esperados, solo se detectara presentes, pero no faltas reales
        Schema::create('periodo_estudiantes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados*/

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados*/

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['periodo_id', 'estudiante_id'], 'uq_periodo_estudiante');
        });


        //TABLA NUEVA
        Schema::create('parsers_relojes_checadores', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });


        Schema::create('relojes_checadores', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            //$table->string('parser_clave', 100)->index();
            $table->text('ubicacion')->nullable();
            $table->boolean('activo')->default(true);
            $table->foreignId('parser_reloj_checador_id')
                ->constrained('parsers_relojes_checadores')
                ->restrictOnDelete();//significa: no puedes borrar el registro padre si hay hijos relacionados*/
            $table->timestamps();
        });


        // =========================
        // TABLA personal_saae se encuentra en las migraciones de: 2026_04_02_194719_create_v4_tablas_logica_acceso_personal_saae.php
        // =========================


        // Auditoria de carga de los ARCHVIVOS
        Schema::create('importaciones_asistencia', function (Blueprint $table) {
            $table->id();

            $table->foreignId('reloj_checador_id')
                ->constrained('relojes_checadores')
                ->restrictOnDelete();;//significa: no puedes borrar el registro padre si hay hijos relacionados*/

            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->restrictOnDelete();;//significa: no puedes borrar el registro padre si hay hijos relacionados*/

            $table->string('archivo_nombre', 255);
            $table->string('archivo_ruta', 500)->nullable();
            $table->char('archivo_hash', 64); // sha256

            $table->enum('tipo_importacion', ['COMPLETA', 'SOLO_ASISTENCIA', 'SOLO_TURNOS'])->default('COMPLETA');
            $table->string('parser_clave', 100)->nullable();
            $table->json('hojas_detectadas')->nullable();

            $table->foreignId('importado_por')//para que importado_por apunte a personal_saae
                ->nullable()
                ->constrained('personal_saae')
                ->nullOnDelete();//significa: si borras el resgistro padre los registros hijo se pone NULL (no se borra la relacion de la importación).

            $table->dateTime('importado_en')->useCurrent();

            $table->enum('estado', ['PENDIENTE','PROCESANDO','EXITOSA','ERROR'])->default('PENDIENTE');
            $table->json('advertencias')->nullable();
            $table->json('resultados_importacion')->nullable();
            $table->text('error_detalle')->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(['reloj_checador_id', 'periodo_id', 'archivo_hash', 'tipo_importacion'], 'uq_import_hash_tipo');
        });


        //mapeo: ID del Reloj con el Estudiante
        Schema::create('reloj_inscripciones', function (Blueprint $table) {
            $table->id();

            //Es una llave foranea FOREIGN KEY
            $table->foreignId('reloj_checador_id') //crea la columna: nombre_llaveforanea_id como BIGINT UNSIGNED 
                ->constrained('relojes_checadores') //crea: indice y FOREIGN KEY (nombre_llaveforanea_id) REFERENCES tabla_solicitada(id)
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados
                //Equivale a: nombre_llaveforane_id BIGINT UNSIGNED NOT NULL,
                            //CONSTRAINT fk_xxx FOREIGN KEY (nombre_llaveforane_id) REFERENCES tabla_solicitada(id)
                            //ON DELETE RESTRICT ON UPDATE CASCADE

            $table->string('reloj_usuario_id', 40)->nullable(); // ID del reloj (string por si trae ceros)

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->boolean('activo')->default(true);
            $table->timestamps();

            //el unique refleja que un estudiante, un reloj
            $table->unique(['reloj_checador_id','estudiante_id'], 'uq_reloj_usuario');

            // Index para busqueda rapida cuando el reloj si tiene su propio ID del estudiante
            $table->index(['reloj_checador_id', 'reloj_usuario_id'], 'ix_reloj_usuario_id');
        });


        // Checadas (horas)
        Schema::create('marcaciones_asistencia', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->foreignId('reloj_checador_id')
                ->constrained('relojes_checadores')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->foreignId('importacion_id')
                ->constrained('importaciones_asistencia')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->dateTime('ocurrio_en');
            $table->string('celda_cruda', 255)->nullable();
            $table->timestamps();

            $table->unique(['estudiante_id','reloj_checador_id','ocurrio_en'], 'uq_marcacion_unica');
            $table->index(['estudiante_id','ocurrio_en'], 'ix_marcacion_estudiante_fecha');
        });


        Schema::create('justificantes_estudiantes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->restrictOnDelete();

            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->restrictOnDelete();

            $table->string('folio', 40)->unique();

            $table->string('motivo', 150);
            $table->text('descripcion')->nullable();

            $table->string('archivo_ruta', 500)->nullable();
            $table->string('archivo_nombre', 255)->nullable();

            $table->enum('estado', [
                'PENDIENTE',
                'APROBADO',
                'RECHAZADO',
                'CANCELADO'
            ])->default('PENDIENTE');

            $table->foreignId('revisado_por')
                ->nullable()
                ->constrained('personal_saae')
                ->nullOnDelete();

            $table->dateTime('revisado_en')->nullable();
            $table->text('comentario_revision')->nullable();

            $table->timestamps();

            $table->index(['estudiante_id', 'periodo_id', 'estado'], 'ix_just_est_per_estado');
        });



        // Resultado final por dia (la tabla principal inicial)
        Schema::create('asistencia_diaria', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->restrictOnDelete();

            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->restrictOnDelete();

            $table->foreignId('reloj_checador_id')
                ->constrained('relojes_checadores')
                ->restrictOnDelete();

            $table->date('fecha');
            $table->boolean('esperado')->default(true);

            $table->enum('estatus', ['PRESENTE','FALTA','RETARDO','NO_APLICA'])
                ->default('FALTA');

            // NUEVO: para saber si una FALTA fue justificada
            $table->boolean('justificada')
                ->default(false);

            // NUEVO: referencia al justificante aprobado
            $table->foreignId('justificante_id')
                ->nullable()
                ->constrained('justificantes_estudiantes')
                ->nullOnDelete();

            $table->enum('fuente', ['RELOJ','MANUAL','WIFI'])->default('RELOJ');

            $table->dateTime('primera_entrada')->nullable();
            $table->dateTime('ultima_salida')->nullable();
            $table->unsignedInteger('conteo_marcaciones')->default(0);

            $table->foreignId('importacion_id')
                ->nullable()
                ->constrained('importaciones_asistencia')
                ->nullOnDelete();

            $table->timestamps();

            $table->unique(
                ['estudiante_id', 'periodo_id', 'fecha', 'reloj_checador_id'],
                'uq_asistencia_dia_reloj'
            );

            $table->index(
                ['reloj_checador_id', 'periodo_id','fecha'],
                'ix_asistencia_reloj_periodo_fecha'
            );

            // NUEVO: ayuda para reportes/consultas de faltas justificadas
            $table->index(
                ['periodo_id', 'estatus', 'justificada'],
                'ix_asistencia_periodo_estatus_justificada'
            );
        });


        Schema::create('justificantes_estudiantes_detalles', function (Blueprint $table) {
            $table->id();

            $table->foreignId('justificante_id')
                ->constrained('justificantes_estudiantes')
                ->cascadeOnDelete();

            $table->foreignId('asistencia_diaria_id')
                ->constrained('asistencia_diaria')
                ->restrictOnDelete();

            $table->date('fecha');
            $table->string('estatus_original', 30)->default('FALTA');

            $table->timestamps();

            $table->unique(['justificante_id', 'asistencia_diaria_id'], 'uq_just_asistencia');
        });




    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('justificantes_estudiantes_detalles');
        Schema::dropIfExists('asistencia_diaria');
        Schema::dropIfExists('justificantes_estudiantes');
        Schema::dropIfExists('marcaciones_asistencia');
        Schema::dropIfExists('reloj_inscripciones');
        Schema::dropIfExists('importaciones_asistencia');
        Schema::dropIfExists('relojes_checadores');
        Schema::dropIfExists('parsers_relojes_checadores');
        Schema::dropIfExists('periodo_estudiantes');
        Schema::dropIfExists('estudiantes_saae');
        Schema::dropIfExists('periodo_fechas');
        Schema::dropIfExists('periodos');
    }
};