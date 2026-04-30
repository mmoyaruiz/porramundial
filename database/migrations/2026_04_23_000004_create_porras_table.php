<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('porras', function (Blueprint $table) {
            $table->increments('id_porra');

            $table->string('nombre', 100);
            $table->text('descripcion')->nullable();

            $table->unsignedInteger('id_competicion');
            $table->unsignedInteger('id_usuario_creador');

            $table->boolean('es_publica')->default(false);
            $table->unsignedInteger('max_participantes')->nullable();

            $table->integer('puntos_ganador')->default(1);
            $table->integer('puntos_marcador')->default(3);
            $table->integer('puntos_campeon_grupo')->default(0);
            $table->integer('puntos_ganador_torneo')->default(0);

            $table->dateTime('fecha_creacion')->useCurrent();
            $table->string('estado', 20)->default('activa');

            $table->foreign('id_competicion')
                  ->references('id_competicion')->on('competiciones');

            $table->foreign('id_usuario_creador')
                  ->references('id_usuario')->on('usuarios');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('porras');
    }
};