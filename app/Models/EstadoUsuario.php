<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EstadoUsuario extends Model
{
    //
    public static $default_prefix ="state_user_";
    public static array $campos = [
        'slug',
        'descripcion'
    ];
    public $table = "estado_usuario";
    public $timestamps = false;

    public function usuarios()
    {
        return $this->hasMany(Usuario::class, 'estado_usuario_id');
    }
}
