<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaModel extends Model
{
    //
    protected $table = 'encuestas';
    public $timestamps = true;

    protected $fillable = [
        'jsonEncuesta',
        'fechaInicio',
        'fechaFin',
        'titulo',
        'usuario_id'
    ];

    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
    public function respuestas()
    {
        return $this->hasMany(RespuestaModel::class, 'encuesta_id');
    }
    public function campos()
    {
        return $this->hasMany(EncuestaCamposModel::class, 'encuesta_id');
    }
}
