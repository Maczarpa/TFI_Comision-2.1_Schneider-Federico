<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        //
        Schema::create('usuario', function (Blueprint $table) {
            $table->id();
            $table->string('username')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable(); // 👈 importante
            $table->string('password');
            //$table->timestamps();
        });
        Schema::create('password_reset_tokens', function (Blueprint $table) {
            $table->string('email')->primary();
            $table->string('token');
            $table->timestamp('created_at')->nullable();
        });

        Schema::dropIfExists('datos_alumno');
        Schema::dropIfExists('facultad');
        Schema::dropIfExists('rol_asignado');
        Schema::dropIfExists('rol');
        Schema::dropIfExists('datos_personales');
        Schema::dropIfExists('estado_usuario');
        //
        Schema::create('estado_usuario', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // activo, bloqueo_intentos, bloqueo_admin
            $table->string('descripcion');
        });
        
        Schema::create('datos_personales', function (Blueprint $table) {
            $table->id();
            $table->string('nombre');
            $table->string('apellido');
            $table->string('dni')->unique();
            $table->date('fecha_nacimiento')->nullable();
            $table->string('telefono')->nullable();
            $table->string('domicilio')->nullable();
            //$table->timestamps();
        });

        Schema::table('usuario', function (Blueprint $table) {
            $table->foreignId('estado_usuario_id')
                ->constrained('estado_usuario')
                ->onUpdate('cascade')
                ->onDelete('restrict')
                ;

            $table->foreignId('datos_personales_id')
                ->constrained('datos_personales')
                ->onUpdate('cascade')
                ->onDelete('restrict');
        });
        
        Schema::create('rol', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique(); // SUPER_ADMIN, ADMIN, OPERARIO, ALUMNO
            $table->string('descripcion');
        });

        Schema::create('rol_usuario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('usuario_id')->constrained('usuario')->onUpdate('cascade')->onDelete('restrict');
            $table->foreignId('rol_id')->constrained('rol')->onUpdate('cascade')->onDelete('restrict');
            $table->timestamp('fecha_alta')->default(DB::raw('CURRENT_TIMESTAMP'));
            $table->timestamp('fecha_baja')->nullable();
        });

        DB::table('estado_usuario')->insert([
            ['slug' => 'ACTIVO', 'descripcion' => 'Cuenta activa'],
            ['slug' => 'BLOQUEO_INTENTOS', 'descripcion' => 'Bloqueada por intentos fallidos'],
            ['slug' => 'BLOQUEO_ADMIN', 'descripcion' => 'Bloqueada por administración'],
        ]);
        
        DB::table('rol')->insert([
            ['slug' => 'SUPER_ADMIN', 'descripcion' => 'Superadministrador del sistema'],
            ['slug' => 'ADMIN', 'descripcion' => 'Administrador general'],
            ['slug' => 'ENCUESTADOR', 'descripcion' => 'Encuestador'],
        ]);
        
        
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rol_usuario');
        Schema::dropIfExists('rol');
        Schema::dropIfExists('usuario');
        Schema::dropIfExists('datos_personales');
        Schema::dropIfExists('estado_usuario');
        Schema::dropIfExists('password_reset_tokens');
        //
    }
};
