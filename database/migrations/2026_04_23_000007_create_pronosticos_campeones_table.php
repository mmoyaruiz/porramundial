<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pronosticos_campeones', function (Blueprint $table) {
            $table->increments('id_pronostico_campeon');

            $table->unsignedInteger('id_usuario');
            $table->unsignedInteger('id_porra');

            $table->enum('tipo_pronostico', ['grupo', 'competicion']);
            $table->string('grupo', 10)->nullable();
            $table->string('equipo_pronosticado', 100);
            $table->integer('puntos_obtenidos')->default(0);
            $table->dateTime('fecha_creacion')->useCurrent();

            $table->foreign('id_usuario')->references('id_usuario')->on('usuarios');
            $table->foreign('id_porra')->references('id_porra')->on('porras');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pronosticos_campeones');
    }
};