<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class EncuestaCamposModel extends Model
{
    //
    protected $table = 'encuestas_campos';
    protected $fillable = [
        'encuesta_id',
        'name',
        'alias',
        'tipo',
        'interpretacion'
    ];

    public function encuesta()
    {
        return $this->belongsTo(EncuestaModel::class, 'encuesta_id');
    }
}
