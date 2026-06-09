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
        Schema::create('permisos_saae', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique(); //ejemplod de: importacion.ver
            $table->string('nombre'); //ejemplo de: Ver modulo de importacion
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });


        Schema::create('roles_personal_saae', function (Blueprint $table) {
            $table->id();
            $table->string('clave', 60)->unique();
            $table->string('nombre');           
            $table->text('descripcion')->nullable();
            $table->timestamps();
        });

        Schema::create('rol_con_permiso_saae', function (Blueprint $table) {
            $table->foreignId('role_id')
                ->constrained('roles_personal_saae')
                ->cascadeOnDelete(); //borra automáticamente todos los registros dependientes (hijos) en una tabla secundaria cuando se elimina el registro principal (padre) relacionado.

            $table->foreignId('permiso_id')
                ->constrained('permisos_saae')
                ->cascadeOnDelete(); //borra automáticamente todos los registros dependientes (hijos) en una tabla secundaria cuando se elimina el registro principal (padre) relacionado.
            $table->timestamps();

            $table->primary(['role_id', 'permiso_id']);
        });


        Schema::create('personal_saae', function (Blueprint $table) {
            $table->id();
            $table->string('nombre', 120)->nullable();
            $table->string('apellidos', 120)->nullable();
            $table->string('email')->unique();
            $table->string('telefono')->unique();
            $table->string('password');
            $table->boolean('activo')->default(true);
            $table->timestamp('ultimo_acceso_at')->nullable();

            $table->rememberToken();
            $table->timestamps();
        });

        
        Schema::create('personal_con_rol_saae', function (Blueprint $table) {
            $table->foreignId('personal_id')
                ->constrained('personal_saae')
                ->cascadeOnDelete(); //borra automáticamente todos los registros dependientes (hijos) en una tabla secundaria cuando se elimina el registro principal (padre) relacionado.

            $table->foreignId('role_id')
                ->constrained('roles_personal_saae')
                ->cascadeOnDelete(); //borra automáticamente todos los registros dependientes (hijos) en una tabla secundaria cuando se elimina el registro principal (padre) relacionado.

            $table->timestamps();

            $table->primary(['personal_id', 'role_id']);
        });


        Schema::create('intentos_recuperacion_contrasena_personal', function (Blueprint $table) {
            $table->id();

            $table->foreignId('personal_id')
                ->nullable()
                ->constrained('personal_saae')
                ->nullOnDelete(); //si un personal se elimina, los registros historicos no se borran; solo se pone personal_saae_id = null

            $table->string('email_solicitado');//el correo que escribieron
            $table->string('ip_address', 45)->nullable();//IP desde donde se hizo la solicitud
            $table->text('user_agent')->nullable();//navegador/dispositivo

            //que intento hacer: solicitar_enlace | abrir_enlace | restablecer_contrasena
            $table->string('accion', 40);

            // que paso: permitido | bloqueado | fallido | exitoso
            $table->string('resultado', 30);

            // correo_no_registrado | cuenta_admin | cuenta_inactiva | correo_enviado
            // token_valido | token_expirado | token_invalido | contrasena_actualizada
            // limite_excedido | validacion_fallida | error_envio_correo
            $table->string('motivo', 100)->nullable();

            $table->timestamps();

            $table->index(['email_solicitado', 'created_at'], 'idx_recuperacion_email_fecha');
            $table->index(['personal_id', 'created_at'], 'idx_recuperacion_personal_fecha');
            $table->index(['resultado', 'created_at'], 'idx_recuperacion_resultado_fecha');
            $table->index(['accion', 'created_at'], 'idx_recuperacion_accion_fecha');
        });

        // Schema::create('historial_recuperacion_contrasenas_personal_saae', function (Blueprint $table) {
        //     $table->foreingId('');
        // });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('personal_con_rol_saae');
        Schema::dropIfExists('personal_saae');
        Schema::dropIfExists('rol_con_permiso_saae');
        Schema::dropIfExists('roles_personal_saae');
        Schema::dropIfExists('permisos_saae');
    }
};
