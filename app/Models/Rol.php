<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    //
    public $table = "rol";  //Campos id, slug, descripcion
    public $timestamps = false;
    public function usuarios()
    {
        return $this->belongsToMany(Usuario::class, 'rol_usuario')
                    ->withPivot(['fecha_alta', 'fecha_baja'])
                    ->withTimestamps();
    }
}
