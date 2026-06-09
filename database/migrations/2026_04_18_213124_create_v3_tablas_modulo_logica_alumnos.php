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

        Schema::create('estudiante_con_personal_saae', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->foreignId('personal_id')
                ->constrained('personal_saae')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->foreignId('role_id')
                ->constrained('roles_personal_saae')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(
                ['estudiante_id', 'personal_id', 'role_id'],
                'uq_estudiante_personal_rol'
            );
        });


        Schema::create('activaciones_cuenta_estudiantes_saae', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->cascadeOnDelete(); //borra automáticamente todos los registros dependientes (hijos) en una tabla secundaria cuando se elimina el registro principal (padre) relacionado.

            $table->string('email_destino', 190);
            $table->char('token_hash', 64)->unique();

            $table->dateTime('expira_en');
            $table->dateTime('enviado_en')->nullable();
            $table->dateTime('usado_en')->nullable();

            $table->enum('estado', ['PENDIENTE', 'ENVIADO', 'ACTIVADO', 'EXPIRADO', 'ERROR'])
                ->default('PENDIENTE');

            $table->foreignId('generado_por')
                ->nullable()
                ->constrained('personal_saae')
                ->nullOnDelete(); //si un personal se elimina, los registros historicos no se borran; solo se pone generado_por = null

            $table->text('error_detalle')->nullable();
            $table->timestamps();

            $table->index(['estudiante_id', 'estado'], 'idx_act_cuenta_estudiante_estado');
            $table->index(['email_destino', 'estado'], 'idx_act_cuenta_email_estado');
        });


        Schema::create('intentos_recuperacion_contrasena_estudiante', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('estudiante_id')->nullable();

            $table->foreign('estudiante_id', 'fk_int_rec_est_est')
                ->references('id')
                ->on('estudiantes_saae')
                ->nullOnDelete(); //si un pestudiante se elimina, los registros historicos no se borran; solo se pone generado_por = null

            $table->string('email_solicitado');
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->string('accion', 40);
            $table->string('resultado', 30);
            $table->string('motivo', 100)->nullable();

            $table->timestamps();

            $table->index(['email_solicitado', 'created_at'], 'idx_recuperacion_email_fecha');
            $table->index(['estudiante_id', 'created_at'], 'idx_recuperacion_estudiante_fecha');
            $table->index(['resultado', 'created_at'], 'idx_recuperacion_resultado_fecha');
            $table->index(['accion', 'created_at'], 'idx_recuperacion_accion_fecha');
        });



        Schema::create('alertas_asistencia_estudiantes', function (Blueprint $table) {
            $table->id();

            $table->foreignId('estudiante_id')
                ->constrained('estudiantes_saae')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->foreignId('periodo_id')
                ->constrained('periodos')
                ->restrictOnDelete(); //significa: no puedes borrar el registro padre si hay hijos relacionados

            $table->foreignId('asistencia_diaria_id')
                ->nullable()
                ->constrained('asistencia_diaria')
                ->nullOnDelete(); //si una asistencia diaria  se elimina y/o no hace falta un justificante, los registros historicos no se borran; solo se pone generado_por = null

            $table->enum('tipo_alerta', [
                'FALTA_ACUMULADA',
                'SUSPENSION_BECA_ESCOLAR',
            ]);

            $table->string('regla_codigo', 80);
            // Ej:
            // FALTA_1
            // FALTA_2
            // FALTA_3
            // UMBRAL_3_FALTAS

            $table->unsignedInteger('valor_detectado')->default(0);
            $table->unsignedInteger('umbral_configurado')->nullable();

            $table->date('fecha_referencia')->nullable();
            $table->dateTime('fecha_disparo');

            $table->enum('estado', [
                'PENDIENTE',
                'ATENDIDA',
                'CERRADA'
            ])->default('PENDIENTE');

            //para el seguimiento cuendo se realizan importaciones de asistencia CON EL ENVIO DE ALERTAS POR CORREO
            //con este se obtiene registros si fallo internamente
            // se manejan estado como PENDIENTE, ENVIADO, OMITIDO, FALLIDO, los cuales se insertan en EnviarCorreoAlertaAsistenciaJob.php
            $table->string('correo_estado')->default('PENDIENTE');
            $table->timestamp('correo_enviado_at')->nullable();
            $table->timestamp('correo_fallo_at')->nullable();
            $table->text('correo_error')->nullable();
            //

            $table->foreignId('atendida_por')
                ->nullable()
                ->constrained('personal_saae')
                ->nullOnDelete();

            $table->dateTime('atendida_en')->nullable();
            $table->text('observaciones')->nullable();

            $table->timestamps();

            // Para listados
            $table->index(
                ['estudiante_id', 'periodo_id', 'estado'],
                'idx_alerta_estudiante_periodo_estado'
            );

            $table->index(
                ['tipo_alerta', 'estado', 'fecha_disparo'],
                'idx_alerta_tipo_estado_fecha'
            );


            //evita duplicar alertas acumuladas y la alerta especial por estudiante de forma global
            $table->unique(
                ['estudiante_id', 'periodo_id', 'tipo_alerta', 'regla_codigo'],
                'uq_alerta_estudiante_tipo_regla'
            );
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {   
        Schema::dropIfExists('alertas_asistencia_estudiantes');
        Schema::dropIfExists('intentos_recuperacion_contrasena_estudiante');
        Schema::dropIfExists('activaciones_cuenta_estudiantes_saae');
        Schema::dropIdExists('estudiante_con_personal_saae');
    }
};
