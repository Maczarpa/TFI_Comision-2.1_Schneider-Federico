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
    public function up()
    {
        DB::transaction(function () {
            // Insertar datos personales
            $datosPersonalesId = DB::table('datos_personales')->insertGetId([
                'nombre' => 'Del Sistema',
                'apellido' => 'Administrador',
                'dni' => '0',
            ]);

            // Insertar usuario
            $usuarioId = DB::table('usuario')->insertGetId([
                'email' => 'admin@admin.com',
                'username' => 'Administrador',
                'password' => Hash::make('administrador'),
                'datos_personales_id' => $datosPersonalesId,
                "estado_usuario_id" => 1,
            ]);

            // Obtener ID del rol SUPERADMIN
            $rolId = DB::table('rol')->where('slug', 'SUPER_ADMIN')->value('id');

            // Asignar rol al usuario
            DB::table('rol_usuario')->insert([
                'usuario_id' => $usuarioId,
                'rol_id' => $rolId,
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        DB::transaction(function () {
            // Buscar usuario
            $usuario = DB::table('usuario')->where('email', 'admin@admin.com')->first();

            if ($usuario) {
                DB::table('rol_usuario')->where('usuario_id', $usuario->id)->delete();
                DB::table('usuario')->where('id', $usuario->id)->delete();
                DB::table('datos_personales')->where('id', $usuario->datos_personales_id)->delete();
            }
        });
    }
};
