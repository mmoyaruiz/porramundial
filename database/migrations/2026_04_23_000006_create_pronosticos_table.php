<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pronosticos', function (Blueprint $table) {
            $table->increments('id_pronostico');

            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_porra');
            $table->unsignedInteger('id_partido');

            $table->unsignedTinyInteger('goles_local_pronosticados');
            $table->unsignedTinyInteger('goles_visitante_pronosticados');
            $table->integer('puntos_obtenidos')->default(0);
            $table->dateTime('fecha_creacion')->useCurrent();

            $table->unique(['id_usuario', 'id_porra', 'id_partido']);

            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $table->foreign('id_porra')->references('id_porra')->on('porras');
            $table->foreign('id_partido')->references('id_partido')->on('partidos');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pronosticos');
    }
};