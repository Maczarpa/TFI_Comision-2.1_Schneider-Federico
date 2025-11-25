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
        /*
        /*
    
    export interface IRespuestaEncuesta {
  encuesta_id: string; // id de la encuesta que se respondió
  id_temp: string; // ej: dispositivo+timestamp
  json_data: string; // JSON con las respuestas
  dispositivo_id: string; // ID del dispositivo que respondió
  sincronizado: boolean; // Si la respuesta fue sincronizada con el servidor
  fecha_respuesta: string; // Fecha y hora en que se respondió
  metadatos?: string; // Información adicional (opcional)
}
     */
        Schema::create('respuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_id')->constrained('encuestas'); 
            $table->string('id_temp')->unique();
            $table->json('json_data');
            $table->string('dispositivo_id');
            $table->timestamp('fecha_respuesta');
            $table->json('metadatos')->nullable();
            $table->foreignId('usuario_id')->constrained('usuario')->nullable();    //La tabla se llama "usuario" en singular
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('respuestas');
    }
};
