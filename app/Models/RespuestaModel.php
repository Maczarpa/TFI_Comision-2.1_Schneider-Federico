<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RespuestaModel extends Model
{
    protected $table = 'respuestas';
    public $timestamps = true;
    
    protected $fillable = [
        'encuesta_id',
        'id_temp',
        'json_data',
        'dispositivo_id',
        'fecha_respuesta',
        'metadatos',
        'usuario_id'
    ];
    protected $casts = [
        'fecha_respuesta' => 'datetime',
        'metadatos' => 'array', // si querés que se guarde como JSON y se recupere como array
    ];

    public function encuesta()
    {
        return $this->belongsTo(EncuestaModel::class, 'encuesta_id');
    }
    public function usuario()
    {
        return $this->belongsTo(Usuario::class, 'usuario_id');
    }
    public function setFechaRespuestaAttribute($value)
    {
        $this->attributes['fecha_respuesta'] = \Carbon\Carbon::parse($value)
            ->timezone(config('app.timezone')) // America/Argentina/Buenos_Aires
            ->format('Y-m-d H:i:s');
    }
}
