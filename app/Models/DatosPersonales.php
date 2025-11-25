<?php

namespace App\Models;

use App\Helpers\CampoMapper;
use Illuminate\Database\Eloquent\Model;

class DatosPersonales extends Model
{
    //
    public $table = "datos_personales";
    public static $default_prefix ="dp_";
    public $timestamps = false;
    public static array $campos = [
        'nombre',
        'apellido',
        'dni',
        'fecha_nacimiento',
        'telefono',
        'domicilio',
    ];
    // DatosPersonales.php
    public static function rules(string $prefijo = '', $id=''): array
    {
       
        $reglasBase = [
            'nombre' => 'required|string|max:100',
            'apellido' => 'required|string|max:100',
            'dni' => 'required|numeric|unique:datos_personales,dni'.($id ? ",$id" : ''), //,' . $usuario->datos_personales_id,
            'fecha_nacimiento' => 'nullable|date',
            'telefono' => 'nullable|string|max:50',
            'domicilio' => 'nullable|string|max:100',
        ];

        return CampoMapper::aplicarPrefijoAReglas($reglasBase, $prefijo);
    }
    
    protected $casts = [
        'fecha_nacimiento' => 'date',
    ];


    protected $fillable = [
        'nombre',
        'apellido',
        'dni',
        'fecha_nacimiento',
        'telefono',
        'domicilio',
    ]; // opcional si querés seguir usando fillable
    public function usuario()
    {
        return $this->hasOne(Usuario::class, 'datos_personales_id');
    }
}
