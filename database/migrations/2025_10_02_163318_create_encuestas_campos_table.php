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
        Schema::create('encuestas_campos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('encuesta_id')->constrained('encuestas')->onDelete('cascade');
            $table->string('name');   // clave interna de surveyjs
            $table->string('alias');  // nombre legible
            $table->string('tipo')->nullable(); // opcional: rating, radiogroup...
            $table->string('interpretacion')->nullable(); // ej: promedio, distribucion
            $table->timestamps();
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('encuestas_campos');
    }
};
