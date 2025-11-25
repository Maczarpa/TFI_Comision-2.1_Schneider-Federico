<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RolUsuario extends Model
{
    //
    public $table = "rol_usuario";
    public $timestamps = false;
    public function usuario()
    {
        return $this->belongsTo(Usuario::class);
    }

    public $casts = [
        "fecha_alta" => "datetime",
        "fecha_baja" => "datetime"
    ];

    public function rol()
    {
        return $this->belongsTo(Rol::class);
    }
}
