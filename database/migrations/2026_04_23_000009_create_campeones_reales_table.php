<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('campeones_reales', function (Blueprint $table) {
            $table->increments('id_campeon_real');

            $table->unsignedInteger('id_competicion');
            $table->enum('tipo', ['grupo', 'competicion']);
            $table->char('grupo', 1)->default('');
            $table->char('equipo_tla', 3);
            $table->string('equipo_shortname', 50)->default('');
            $table->string('fuente', 50)->default('football-data.org');

            $table->timestamp('fecha_actualizacion')
                  ->useCurrent()
                  ->useCurrentOnUpdate();

            $table->unique(['id_competicion', 'tipo', 'grupo']);

            $table->foreign('id_competicion')
                  ->references('id_competicion')->on('competiciones')
                  ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('campeones_reales');
    }
};