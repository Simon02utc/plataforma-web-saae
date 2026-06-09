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
        
        Schema::create('areas_especialidad_estudiantes_saae', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->string('nombre', 150)->unique();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });


        Schema::create('estatus_escolares_estudiantes_saae', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('nombre', 120)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });


        Schema::create('parsers_fuentes_datos_escolares', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 100)->unique();
            $table->string('nombre', 150);
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);
            $table->timestamps();
        });


        Schema::create('fuentes_datos_escolares', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 150)->unique();
            $table->text('descripcion')->nullable();
            $table->boolean('activo')->default(true);

            $table->foreignId('parser_fuente_dato_escolar_id')
                ->constrained('parsers_fuentes_datos_escolares')
                ->restrictOnDelete();  //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->timestamps();
        });


        // =========================
        // TABLA personal_saae se encuentra en las migraciones de: 2026_04_02_194719_create_v4_tablas_logica_acceso_personal_saae.php
        // =========================


        Schema::create('importaciones_datos_escolares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('fuente_datos_escolares_id')
                ->constrained('fuentes_datos_escolares')
                ->restrictOnDelete();  //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->string('archivo_nombre', 255);
            $table->string('archivo_ruta', 500)->nullable();
            $table->char('archivo_hash', 64); // sha256

            $table->enum('tipo_importacion', ['COMPLETA', 'SOLO_ESTUDIANTES'])
                ->default('SOLO_ESTUDIANTES');

            $table->string('parser_clave', 100)->nullable();
            $table->json('hojas_detectadas')->nullable();

            $table->foreignId('importado_por')
                ->nullable()
                ->constrained('personal_saae')
                ->nullOnDelete(); //significa: si borras el resgistro padre los registros hijo se pone NULL (no se borra la relacion de la importación).

            $table->dateTime('importado_en')->useCurrent();

            $table->enum('estado', ['PENDIENTE','PROCESANDO','EXITOSA','ERROR'])->default('PENDIENTE');
            $table->json('advertencias')->nullable();
            $table->json('resultados_importacion')->nullable();
            $table->text('error_detalle')->nullable();

            $table->text('notas')->nullable();
            $table->timestamps();

            $table->unique(
                ['fuente_datos_escolares_id', 'archivo_hash', 'tipo_importacion'],
                'uq_import_datos_escolares_hash_tipo'
            );
        });


        // =========================
        // TABLA estudiantes_saae se encuentra en las migraciones de: 2026_04_02_194907_create_v5_tablas_modulo_importacion_asistencia.php
        // =========================


        Schema::create('estudiantes_con_datos_escolares', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->unsignedSmallInteger('anio_ingreso')->nullable();
            $table->unsignedTinyInteger('mes_ingreso')->nullable();
            $table->string('periodo_ingreso_texto',150)->nullable(); //fecha cruda del periodo de ingreso

            $table->foreignId('especialidad_id')
                ->nullable()
                ->constrained('areas_especialidad_estudiantes_saae')
                ->nullOnDelete(); //significa: si borras el registro hijo se pone NULL (no se borra la relacion del estudiante con sus datos escolares).

            $table->foreignId('estatus_escolar_id')
                ->nullable()
                ->constrained('estatus_escolares_estudiantes_saae')
                ->nullOnDelete(); //significa: si borras el registro hijo se pone NULL (no se borra la relacion del estudiante con sus datos escolares).

            $table->foreignId('ultima_importacion_id')
                ->nullable()
                ->constrained('importaciones_datos_escolares')
                ->nullOnDelete(); //significa: si borras el registro hijo se pone NULL (no se borra la relacion del estudiante con sus datos escolares).

            $table->timestamps();


            $table->unique('estudiante_id', 'uq_estudiante_datos_escolares');


            //PARA LOS FILTROS DE ESTUDIANTES POR especialidad_id y estatus_escolar_id
            $table->index('especialidad_id', 'idx_estudiante_datos_escolares_especialidad');
            $table->index('estatus_escolar_id', 'idx_estudiante_datos_escolares_estatus');
            $table->index('anio_ingreso', 'idx_estudiante_datos_escolares_anio_ingreso');
            $table->index('mes_ingreso', 'idx_estudiante_datos_escolares_mes_ingreso');
            $table->index(['especialidad_id', 'estatus_escolar_id'], 'idx_estudiante_datos_escolares_area_estatus');
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('estudiante_con_personal_saae');
        Schema::dropIfExists('estudiantes_con_datos_escolares');
        Schema::dropIfExists('importaciones_datos_escolares');
        Schema::dropIfExists('fuentes_datos_escolares');
        Schema::dropIfExists('parsers_fuentes_datos_escolares');
        Schema::dropIfExists('estatus_escolares_estudiantes_saae');
        Schema::dropIfExists('areas_especialidad_estudiantes_saae');
    }
};
